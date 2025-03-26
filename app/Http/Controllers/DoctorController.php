<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use ReflectionClass;
use App\Helpers\TermHelper;
use Illuminate\Http\Request;
use App\Jobs\CloseAttendance;
use App\Models\AcademicSchedule;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;
use App\Models\StudentRegistration;

class DoctorController extends Controller
{
    public function getSchedules(){
        $user = auth()->user();
        $userId = $user->id;
        $userType = $user->role;

        $term = TermHelper::getCurrentTerm();
        $year = now()->year;
        $month = now()->month;
        if($term == 'first' && $month == 1)
            $year--;

        $academicSchedules = AcademicSchedule::where($userType.'_id', $userId)
        ->where('term', $term)
        ->where('year', $year)
        ->with(['course:id,name', 'lectureHall:id,name,number_of_chairs_or_benches_or_computers', 'sectionHall:id,name,number_of_chairs_or_benches_or_computers'])
        ->get();

        
        return response()->json([
            'schedules' => $academicSchedules
        ]);
    }
    public function openAttendance(Request $request, int $id){
        $request->validate([
            'time' => 'required|integer'
        ]);

        $user = auth()->user();
        $userId = $user->id;
        $userType = $user->role;

        $scheduleId = $id;

        if(!AcademicSchedule::where('id', $id)->exists()){
            return response()->json(['message' => 'schedule not found'], 404);
        }

        $session_type = ($userType == 'doctor') ? 'lecture' : 'section';

        $is_attendance_open_type = 'is_'.$session_type.'_attendance_open';

        $academicSchedule = AcademicSchedule::where('id', $scheduleId)
            ->where($userType.'_id', $userId)
            ->first();

        if (!$academicSchedule) {
            return response()->json(['message' => 'course not found or not associated with this doctor'], 404);
        }

        if ($academicSchedule->$is_attendance_open_type == 1) {
            return response()->json(['message' => 'attendance already opened'], 409);
        }

        $academicSchedule->$is_attendance_open_type = 1;
        $academicSchedule->save();

        $time = $request->time;

        $students = StudentRegistration::where('academic_schedule_id', $scheduleId)->pluck('student_id');

        $existingAttendance = AttendanceRecord::where('schedule_id', $scheduleId)
            ->where('session_type', $session_type)
            ->whereDate('attend_at', Carbon::today())
            ->pluck('student_id')
            ->toArray();

        $studentsToInsert = $students->filter(function ($studentId) use ($existingAttendance) {
            return !in_array($studentId, $existingAttendance);
        });

        if ($studentsToInsert->isNotEmpty()) {
            $attendanceData = $studentsToInsert->map(function ($studentId) use ($scheduleId, $session_type) {
                return [
                    'schedule_id'       => $scheduleId,
                    'student_id'        => $studentId,
                    'attendance_status' => 0, 
                    'session_type'      => $session_type, 
                    'attend_at'       => Carbon::now(), 
                ];
            })->toArray();

            AttendanceRecord::insert($attendanceData);
        }

        // dispatch(new CloseAttendance($academicSchedule, $time, $session_type));

        dispatch(new CloseAttendance($academicSchedule->id, $session_type))
        ->delay(now()->addSeconds((int) $time));

        $message = "attendance opened and will be closed after " . $time . " seconds";
        return response()->json(['message' => $message]);
    }

    public function closeAttendance(int $id){
        $user = auth()->user();
    
        $userType = $user->role;
        $scheduleId = $id;
        $sessionType = 'is_' . ($user->role == 'doctor' ? 'lecture' : 'section') . '_attendance_open';
    
        $academicSchedule = AcademicSchedule::where('id', $scheduleId)
            ->where($userType . '_id', $user->id)
            ->first();
    
        if (!$academicSchedule) {
            return response()->json(['message' => 'Course not found or not associated with this doctor'], 404);
        }
    
        if ($academicSchedule->$sessionType == 0) {
            return response()->json(['message' => 'Attendance is already closed'], 409);
        }
    
        $academicSchedule->$sessionType = 0;
        $academicSchedule->save();
    
        // البحث عن جميع الـ Jobs الخاصة بـ CloseAttendance
        $jobs = DB::table('jobs')->where('payload', 'LIKE', '%CloseAttendance%')->get();
    
        foreach ($jobs as $job) {
            $payloadArray = json_decode($job->payload, true);
    
            if (!isset($payloadArray['data']['command'])) {
                continue;
            }
    
            // فك الترميز للحصول على الكائن الفعلي
            $payloadData = unserialize($payloadArray['data']['command']);
    
            // استخدام ReflectionClass لاستخراج القيم المحمية
            $reflection = new ReflectionClass($payloadData);
    
            if ($reflection->hasProperty('scheduleId') && $reflection->hasProperty('sessionType')) {
                $scheduleIdProperty = $reflection->getProperty('scheduleId');
                $scheduleIdProperty->setAccessible(true);
                $jobScheduleId = $scheduleIdProperty->getValue($payloadData);
    
                $sessionTypeProperty = $reflection->getProperty('sessionType');
                $sessionTypeProperty->setAccessible(true);
                $jobSessionType = $sessionTypeProperty->getValue($payloadData);
    
                // حذف الـ Job إذا كانت تخص نفس `scheduleId` ونفس `sessionType`
                if ($jobScheduleId == $scheduleId && 'is_'.$jobSessionType.'_attendance_open' === $sessionType) {
                    DB::table('jobs')->where('id', $job->id)->delete();
                }
            }
        }
    
        return response()->json(['message' => 'Attendance closed successfully']);
    }
    

    public function getTodayAttendance(int $id){
        $scheduleId = $id;
        $user_role = auth()->user()->role;
        $userId = auth()->user()->id;

        $academic_schedule = AcademicSchedule::find($id);
        

        if (!$academic_schedule) {
            return response()->json(['message' => 'schedule not found'], 404);
        }

        $user_type_id = $user_role.'_id';

        
        if($academic_schedule->$user_type_id != $userId){
            return response()->json(['message' => 'schedule not associated with this doctor'], 403);
        }

        $session_type = ($user_role == 'doctor') ? 'lecture' : 'section';


        $attendanceStats = AttendanceRecord::where('schedule_id', $scheduleId)
            ->where('session_type', $session_type)
            ->whereDate('attend_at', Carbon::today())
            ->selectRaw('SUM(attendance_status = 1) as total_present, SUM(attendance_status = 0) as total_absent')
            ->first();
        
        $attendanceRecords = AttendanceRecord::where('schedule_id', $scheduleId)
            ->where('session_type', $session_type)
            ->whereDate('attend_at', Carbon::today())
            ->with(['user:id,name', 'student:id,grade,department'])
            ->get();
        
        return response()->json([
            'total_present' => (int) $attendanceStats->total_present,
            'total_absent' => (int) $attendanceStats->total_absent,
            'students_attendance_records' => $attendanceRecords
        ]);
    }

    public function getTermAttendance(int $id){        
        $scheduleId = $id;
        $user_role = auth()->user()->role;
        $userId = auth()->user()->id;

        $academic_schedule = AcademicSchedule::find($id);
        

        if (!$academic_schedule) {
            return response()->json(['message' => 'schedule not found'], 404);
        }

        $user_type_id = $user_role . '_id';

        
        if($academic_schedule->$user_type_id != $userId){
            return response()->json(['message' => 'schedule not associated with this doctor'], 403);
        }

        $session_type = ($user_role == 'doctor') ? 'lecture' : 'section';

        $termattendance = AttendanceRecord::where('schedule_id', $scheduleId)
        ->where('session_type', $session_type)
        ->groupBy('student_id')
        ->selectRaw('student_id, COUNT(*) as total_sessions, SUM(attendance_status) as total_present')
        ->with(['user:id,name', 'student:id,grade,department'])
        ->get()
        ->map(function ($record) {
            return [
                'student_id' => $record->student_id,
                'name' => $record->user->name,
                'grade' => $record->student->grade,
                'department' => $record->student->department,
                'total_present' => (int) $record->total_present,
                'total_absent' => (int) ($record->total_sessions - $record->total_present)
            ];
        });

        return response()->json(['term_attendance' => $termattendance]);
    }

    public function markAbsent(Request $request, int $scheduleId){        
        $request->validate([
            'student_id' => 'required|integer'
        ]);
        $studentId = $request->student_id;

        $user_role = auth()->user()->role;
        $session_type = ($user_role == 'doctor') ? 'lecture' : 'section';

        $student_record = AttendanceRecord::where('student_id', $studentId)
        ->where('schedule_id', $scheduleId)
        ->where('session_type', $session_type)
        ->whereDate('attend_at', Carbon::today())
        ->first();

        if(!$student_record){
            return response()->json(['message' => 'student_record not found'], 404);
        }

        if($student_record->attendance_status == 0){
            return response()->json(['message' => 'student already absent'], 409);
        }

        $student_record->attendance_status = 0;
        $student_record->save();
        return response()->json(['message' => 'student mark absent']);
    }
}
