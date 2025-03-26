<?php

namespace App\Http\Controllers;
use App\Models\Hall;
use App\Models\User;

use App\Models\Course;
use App\Models\Doctor;

use App\Models\Student;
use Shuchkin\SimpleXLSX;

use App\Models\Assistant;
use App\Helpers\TermHelper;
// use SimpleXLSX;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\AcademicSchedule;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UnverifiedStudentsImport;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class StudentAffairsController extends Controller
{
    public function storeStudents(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx',
        ]);

        require_once app_path('Helpers/SimpleXLSX.php');
        
        $file = $request->file('file');
        $xlsx = new SimpleXLSX($file->getPathname());
        
        if ($xlsx->success()) {
            foreach ($xlsx->rows() as $row) {
                $date = null;
                if (is_numeric($row[5])) {
                    // تحويل الرقم التسلسلي إلى صيغة التاريخ
                    $date = Date::excelToDateTimeObject($row[5])->format('Y-m-d');
                } else {
                    // إذا لم يكن رقمًا، اعتبره نصًا وقم بتحويله إلى تاريخ
                    $date = Carbon::parse($row[5])->format('Y-m-d');
                }   
                $existingStudent = User::where('email', $row[1])->first();
                // dd($existingStudent); 
                if ($existingStudent) {
                    continue;
                }
                // إنشاء الطالب في جدول unverified_students
                $phone = 
                $student = User::create([
                    'name' => $row[0],
                    'email' => $row[1],
                    'password' => bcrypt('123456'),
                    'phone' => '0'.$row[2],
                ]);
                // return response()->json(['message' => $row[3]]);
                // dd($student); 
                
                Student::create([
                    'id' => $student->id,
                    'department' => $row[3],
                    'grade' => $row[4],
                    // 'department' => trim($row[3]),
                    // 'phone' => $row[4], 
                    'date_of_birth' => $date,
                    'address' => $row[6], 
                ]);
            }
    
            return response()->json(['message'=>'imported successful'],200);
        } else {
            return response()->json(['message'=>'error occurred'],501);
        }
    }

    public function storeUser(Request $request)
    {
        $rules = [
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users,email',
            'phone' => 'required|numeric|digits:11',
            'role' => 'required|string|in:student,doctor,assistant,student_affairs,admin',
        ];

        if (in_array($request->role, ['doctor', 'assistant', 'student'])) {
            $rules['department'] = 'required|string';
            if($request->role == 'student'){
                $rules['grade'] = 'required|string';
                $rules['date_of_birth'] = 'required|string';
                $rules['address'] = 'required|string';
                
            }
                }

        $fields = $request->validate($rules);

        $fields['password'] = Hash::make('123456'); 

        $user = User::create($fields);

        if (isset($fields['department'])) {
            if($fields['role'] == 'doctor'){
                Doctor::create([
                    'id' => $user->id,
                    'department' => $fields['department']
                ]);
            }elseif($fields['role'] == 'assistant'){
                Assistant::create([
                    'id' => $user->id,
                    'department' => $fields['department']
                ]);
            }elseif($fields['role'] == 'student'){
                Student::create([
                    'id' => $user->id,
                    'department' => $fields['department'],
                    'grade' => $fields['grade'],
                    'date_of_birth' => $fields['date_of_birth'],
                    'address' => $fields['address'],
                ]);
            }
        }
    
        return response()->json(['message' => 'User added successfully'], 201);
    }

    public function storeCourse(Request $request){
        $fields = $request->validate([
            'name'=>'required|string',
            'code'=>'required|string'
        ]);

        $course_name = Course::where('name', $request->name)->first();
        if($course_name){
            return response()->json(['message' => 'the course name is already added'], 409);
        }

        $course_code = Course::where('code', $request->code)->first();
        if($course_code){
            return response()->json(['message' => 'the course code is already added'], 409);
        }

        Course::create($fields);
        return response()->json(['message' => 'course created successfully'], 201);
    }

    public function updateCourse(Request $request, int $id){
        $fields = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string'
        ]);
        
        $course = Course::find($id);
        
        if (!$course) {
            return response()->json(['message' => 'course not found'], 404);
        }
        
        $course->update($fields);
        
        return response()->json(['message' => 'course updated successfully'], 200);
    }

    public function destroyCourse(int $id){
        $deleted = Course::where('id', $id)->delete();
        if (!$deleted) {
            return response()->json(['message' => 'course not found'], 404);
        }

        return response()->json(['message' => 'course deleted successfully'], 200);
    }

    public function indexCourses(){
        $allcourses = Course::all();
        return response()->json(['allcourses' => $allcourses]);
    }

    public function storeSchedule(Request $request)
    {
        $fields = $request->validate([
            'course_id'=>'required|integer|exists:courses,id',
            'doctor_id'=>'required|integer|exists:doctors,id',
            'lecture_hall_id'=>'required|integer|exists:halls,id',
            'lecture_day'=>'required|string',
            'lecture_start_hour'=>'required|string',
            'lecture_end_hour'=>'required|string',
            'assistant_id'=>'required|integer|exists:assistants,id',
            'section_hall_id'=>'required|integer|exists:halls,id',
            'section_day'=>'required|string',
            'section_start_hour'=>'required|string',
            'section_end_hour'=>'required|string',
        ]);

        $term = TermHelper::getCurrentTerm();
        $year = now()->year;
        $month = now()->month;
        // $term = 'first';$month=1;
        if($term == 'first' && $month == 1)
            $year--;
        // return response()->json(['message' =>$year ], 409);
        
        $academicSchedule = AcademicSchedule::where('term', $term)
        ->where('year', $year)
        ->where('course_id', $request->course_id)
        ->first();

        if($academicSchedule){
            return response()->json(['message' => 'academic schedule already added in this term'], 409);
        }

        // $year = now()->year;
        //ايوا هنضيفوا السنة اللى قبلها فاهممممم صح كدا
        $fields['term'] = $term;
        $fields['year'] = $year;

        AcademicSchedule::create($fields);

        return response()->json(['message' => 'academic schedule created successfully'], 201);
    }

    public function updateSchedule(Request $request, int $id)
    {
        $fields = $request->validate([
            'doctor_id'=>'required|integer|exists:doctors,id',
            'lecture_hall_id'=>'required|integer|exists:halls,id',
            'lecture_day'=>'required|string',
            'lecture_start_hour'=>'required|string',
            'lecture_end_hour'=>'required|string',
            'assistant_id'=>'required|integer|exists:assistants,id',
            'section_hall_id'=>'required|integer|exists:halls,id',
            'section_day'=>'required|string',
            'section_start_hour'=>'required|string',
            'section_end_hour'=>'required|string',
        ]);

        $term = TermHelper::getCurrentTerm();
        $year = now()->year;
        $month = now()->month;
        // $term = 'first';$month=1;
        if($term == 'first' && $month == 1)
            $year--;
        // return response()->json(['message' =>$year ], 409);
        
        $schedule = AcademicSchedule::find($id);
        if (!$schedule) {
            return response()->json(['message' => 'academic schedule not found'], 404);
        }

        $fields['term'] = $term;
        $fields['year'] = $year;

        $schedule->update($fields);

        return response()->json(['message' => 'academic schedule updated successfully'], 201);
    }

    public function destroySchedule(int $id){
        $deleted = AcademicSchedule::where('id', $id)->delete();
        if (!$deleted) {
            return response()->json(['message' => 'schedule not found'], 404);
        }

        return response()->json(['message' => 'schedule deleted successfully'], 200);
    }
    public function indexSchedules(){
        $term = TermHelper::getCurrentTerm();
        $year = now()->year;
        $month = now()->month;
        // $term = 'first';
        if($term == 'first' && $month == 1)
            $year--;

        // return response()->json(['current_term' => $term]);
        $academicSchedules = AcademicSchedule::where('term', $term)
        ->where('year', $year)
        ->with(['course:id,name', 'lectureHall:id,name', 'sectionHall:id,name', 'doctor.user:id,name', 'assistant.user:id,name'])
        ->get();
        return response()->json(['all_schedules' => $academicSchedules]);
    }

    public function storeHall(Request $request){
        $fields = $request->validate([
            'hall_type'=>'required|string|in:hall,lab',
            'name'=>'required|string',
            'number_of_chairs_or_benches_or_computers'=>'integer'
        ]);

        $hall = Hall::where('name', $request->name)
        ->where('hall_type', $request->hall_type)
        ->first();
        if($hall){
            return response()->json(['message' => 'the '.$request->hall_type.' name is already added'], 409);
        }

        Hall::create($fields);
        return response()->json(['message' => 'hall added successfully'], 201);
    }

    public function updateHall(Request $request, int $id){
        $fields = $request->validate([
            'hall_type'=>'required|string|in:hall,lab',
            'name'=>'required|string',
            'number_of_chairs_or_benches_or_computers'=>'integer'
        ]);
        
        $hall = Hall::find($id);
        
        if (!$hall) {
            return response()->json(['message' => 'hall not found'], 404);
        }
        
        $hall->update($fields);
        
        return response()->json(['message' => 'hall updated successfully'], 200);
    }

    public function destroyHall(int $id){
        $deleted = Hall::where('id', $id)->delete();
        if (!$deleted) {
            return response()->json(['message' => 'hall not found'], 404);
        }

        return response()->json(['message' => 'hall deleted successfully'], 200);
    }

    public function indexHalls(){
        $all_halls = Hall::all();
        return response()->json(['all_halls' => $all_halls]);
    }

    public function indexDoctors(){
        $all_doctors = Doctor::with(['user:id,email,name,phone'])->get();
        return response()->json(['all_doctors' => $all_doctors]);
    }

    public function indexAssistants(){
        $all_assistants = Assistant::with(['user:id,email,name,phone'])->get();
        return response()->json(['all_assistants' => $all_assistants]);
    }

    public function getTermAttendance(Request $request, int $id){    
        $request->validate([
            'session_type' => 'required|string|in:section,lecture'
        ]);

        $scheduleId = $id;

        $academic_schedule = AcademicSchedule::find($scheduleId);
        if (!$academic_schedule) {
            return response()->json(['message' => 'schedule not found'], 404);
        }

        $session_type = $request->session_type;

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
}

