<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
// use App\Models\AcademicSchedule;
use App\Models\Course;
use App\Helpers\TermHelper;
use Illuminate\Http\Request;
use App\Models\AcademicSchedule;
use App\Models\AttendanceRecord;
use App\Models\StudentRegistration;

class StudentController extends Controller
{
    public function getSchedules(){
        $studentId = auth()->user()->id;
        $term = TermHelper::getCurrentTerm();
        $year = now()->year;
        $month = now()->month;
        // $term = 'first';
        if($term == 'first' && $month == 1)
            $year--;

        $schedules = StudentRegistration::where('student_id', $studentId)
        ->whereHas('schedule', function ($query) use ($year, $term) {
            $query->where('term', $term)
                ->where('year', $year);
        })
        ->with([
            'schedule.course:id,name', 
            'schedule.lectureHall:id,name', 
            'schedule.sectionHall:id,name',
            'schedule.doctor.user:id,name', 
            'schedule.assistant.user:id,name',
        ])
        ->get();

        return response()->json(['schedules' =>$schedules]);
    }

    public function isAttendanceAvailable(int $id, Request $request){
        $request->validate([
            'session_type' => 'required|string|in:section,lecture'
        ]);

        // $schedule = AcademicSchedule::find($id);

        $scheduleId = $id;

        $studentId = auth()->user()->id;
        
        if (!AcademicSchedule::where('id', $id)->exists()) {
            return response()->json(['message' => 'schedule not found'], 404);
        }
        
        $is_student_registered = StudentRegistration::where('student_id', $studentId)
        ->where('academic_schedule_id', $scheduleId)
        ->exists();

        if(!$is_student_registered){
            return response()->json(['message' => 'the student has not registered for this course'], 404);
        }

        $sessionType = $request->query('session_type');
    
        $is_schedule_opened = AcademicSchedule::where('id', $id)
            ->where('is_'.$sessionType.'_attendance_open', 1)
            ->exists();
    
        return response()->json([
            'message' => $is_schedule_opened ? 'attendance is available for this course now' : 'attendance is not allowed for this course now'
        ], $is_schedule_opened ? 200 : 403);
    }
    

    public function attend(Request $request, int $id){
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'session_type' => 'required|string|in:section,lecture'
        ]);
        $scheduleId = $id;
        // $schedule = AcademicSchedule::find($scheduleId);

        $sessionType = $request->session_type;

        $studentId = auth()->user()->id;

        // if(!$schedule)return response()->json(['message' => "schedule not found"], 404);

        if (!AcademicSchedule::where('id', $id)->exists()) {
            return response()->json(['message' => 'schedule not found'], 404);
        }

        $is_student_registered = StudentRegistration::where('student_id', $studentId)
        ->where('academic_schedule_id', $scheduleId)
        ->exists();

        if(!$is_student_registered){
            return response()->json(['message' => 'the student has not registered for this course'], 404);
        }


        $is_schedule_opened = AcademicSchedule::where('id', $scheduleId)
        ->where('is_'.$sessionType.'_attendance_open', 1)
        ->first();

        if(!$is_schedule_opened)return response()->json(['message' => "attendance is not allowed for this course now"], 403 );

        $polygon = [
            ['lat' => 30.668684415007466, 'lng' => 30.07127425557614],
            ['lat' => 30.668724943577924, 'lng' => 30.070399187195438],
            ['lat' => 30.668885127761424, 'lng' => 30.070374505779576],
            ['lat' => 30.66890562064026, 'lng' => 30.070057071639575],
            ['lat' => 30.669182995363492, 'lng' => 30.07008718657393],
            ['lat' => 30.669210656829943, 'lng' => 30.06999716169365],
            ['lat' => 30.669377988273585, 'lng' => 30.070000939227576],
            ['lat' => 30.669342099181193, 'lng' => 30.07041160775724],
            ['lat' => 30.66953436422808, 'lng' => 30.070445137505175],
            ['lat' => 30.669500460008692, 'lng' => 30.070918173383674],
            ['lat' => 30.66936884071469, 'lng' => 30.070920681966225],
            ['lat' => 30.669327844504526, 'lng' => 30.071537793273738],
            ['lat' => 30.668924354560545,'lng' =>  30.071515216030775],
            ['lat' => 30.66890529095589, 'lng' => 30.071304329856908],
        ];

        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $isAttend = AttendanceRecord::where('student_id',$studentId )
        ->where('schedule_id',$scheduleId)
        ->where('attendance_status',1)
        ->where('session_type',$sessionType)
        ->whereDate('attend_at',Carbon::today())
        ->first();

        // return response()->json(['message' => $isAttend]);

        if($isAttend){
            return response()->json(['message' => 'attendance is already registered'], 409);
        }

        if ($this->isPointInPolygon($latitude, $longitude, $polygon)) {
            $is_registered = AttendanceRecord::where('student_id',$studentId )
            ->where('schedule_id',$scheduleId)
            ->where('attendance_status',0)
            ->where('session_type',$sessionType)
            ->whereDate('attend_at',Carbon::today())->update([
                                                                    'attendance_status' => 1,
                                                                    'attend_at' => Carbon::now()
        ]);
            if($is_registered){
                return response()->json(['message' => 'attendance is registered'], 200);
            }else{
                return response()->json(['message' => 'the student did not register for this course'], 404);
            }
        } else {
            return response()->json(['message' => 'you are not in college'], 403);
        }
    }
    private function isPointInPolygon($lat, $lng, $polygon)
    {
        $intersections = 0;
        $points = count($polygon);

        for ($i = 0; $i < $points; $i++) {
            $j = ($i + 1) % $points;

            $vertex1 = $polygon[$i];
            $vertex2 = $polygon[$j];

            if (
                ($vertex1['lat'] > $lat) != ($vertex2['lat'] > $lat) &&
                ($lng < ($vertex2['lng'] - $vertex1['lng']) * ($lat - $vertex1['lat']) / ($vertex2['lat'] - $vertex1['lat']) + $vertex1['lng'])
            ) {
                $intersections++;
            }
        }
        return $intersections % 2 == 1;
    }

    public function attendanceHistory(Request $request, int $id) {
        $request->validate([
            'session_type' => 'required|string|in:lecture,section'
        ]);

        if (!AcademicSchedule::where('id', $id)->exists()) {
            return response()->json(['message' => 'schedule not found'], 404);
        }

        $sessionType = $request->query('session_type');

        
        $scheduleId = $id;
        $studentId = auth()->user()->id;

        $attendanceStats = AttendanceRecord::where('student_id', $studentId)
            ->where('schedule_id', $scheduleId)
            ->where('session_type', $sessionType)
            ->selectRaw('SUM(attendance_status = 1) as total_present, SUM(attendance_status = 0) as total_absent')
            ->first();

        $attendance_records = AttendanceRecord::where('student_id', $studentId)
            ->where('schedule_id', $scheduleId)
            ->where('session_type', $sessionType)
            ->get();

        return response()->json([
            'total_present' => (int) $attendanceStats->total_present,
            'total_absent' => (int) $attendanceStats->total_absent,
            'attendance_records' => $attendance_records,
        ], 200);
    }

    public function avilableSchedules() {
        $term = TermHelper::getCurrentTerm();
        $year = now()->year;
        $month = now()->month;
        // $term = 'first';
        if($term == 'first' && $month == 1)
            $year--;

        // return response()->json(['current_term' => $term]);
        $avilableSchedules = AcademicSchedule::where('term', $term)
        ->where('year', $year)
        ->with(['course:id,name', 'lectureHall:id,name', 'sectionHall:id,name', 'doctor.user:id,name', 'assistant.user:id,name'])
        ->get();
        return response()->json(['avilable_schedules' => $avilableSchedules]);
    }

    public function registerSchedule(Request $request) {
        $request->validate([
            'schedule_id' => 'required|integer|exists:academic_schedules,id'
        ]);

        $studentId = auth()->user()->id;

        $is_registered = StudentRegistration::where('student_id', $studentId)
        ->where('academic_schedule_id', $request->schedule_id)
        ->first();

        if($is_registered){
            return response()->json(['message' => 'academic schedule already registered'], 403);
        }

        StudentRegistration::create([
            'student_id' => $studentId,
            'academic_schedule_id' => $request->schedule_id,
            'created_at' => Carbon::now()
        ]);

        return response()->json(['message' => 'academic schedule registered successfully'], 201);
    }

    public function unregisterSchedule(int $id) {
        $studentId = auth()->user()->id;
    
        $is_registered = StudentRegistration::where('student_id', $studentId)
            ->where('academic_schedule_id', $id)
            ->first();
    
        if (!$is_registered) {
            return response()->json(['message' => 'You are not registered in this schedule'], 404);
        }
    
        $is_registered->delete();
    
        return response()->json(['message' => 'Schedule unregistered successfully'], 200);
    }
}
