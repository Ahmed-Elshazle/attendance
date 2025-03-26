<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentAffairsController;

    Route::middleware('throttle:30,1')->group(function () {
        // Route::post('/login', [AuthController::class,'login']);
    });

    Route::post('/login', [AuthController::class,'login']);
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/verify-otp-reset-password', [AuthController::class, 'verifyOtpAndResetPassword']);

    Route::get('/add-users', [AuthController::class, 'add_users']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class,'logout']);

        //StudentAffairs
        Route::prefix('studentaffairs')->middleware('ability:student_affairs')->group(function () {
            Route::post('/add-students', [StudentAffairsController::class, 'storeStudents']);
            Route::post('/add-user', [StudentAffairsController::class, 'storeUser']);

            Route::post('/courses', [StudentAffairsController::class, 'storeCourse']);
            Route::put('/courses/{id}', [StudentAffairsController::class, 'updateCourse']);
            Route::delete('/courses/{id}', [StudentAffairsController::class, 'destroyCourse']);
            Route::get('/courses', [StudentAffairsController::class, 'indexCourses']);

            Route::post('/schedules', [StudentAffairsController::class, 'storeSchedule']);
            Route::put('/schedules/{id}', [StudentAffairsController::class, 'updateSchedule']);
            Route::delete('/schedules/{id}', [StudentAffairsController::class, 'destroySchedule']);
            Route::get('/schedules', [StudentAffairsController::class, 'indexSchedules']);

            Route::post('/halls', [StudentAffairsController::class, 'storeHall']);
            Route::put('/halls/{id}', [StudentAffairsController::class, 'updateHall']);
            Route::delete('/halls/{id}', [StudentAffairsController::class, 'destroyHall']);
            Route::get('/halls', [StudentAffairsController::class, 'indexHalls']);

            Route::get('/doctors', [StudentAffairsController::class, 'indexDoctors']);
            Route::get('/assistants', [StudentAffairsController::class, 'indexAssistants']);

            Route::get('/term-attendance/{id}', [StudentAffairsController::class, 'getTermAttendance']);

        });

        //Doctors&Assistants
        Route::prefix('doctor')->middleware('ability:doctor')->group(function () {
            Route::get('/schedules', [DoctorController::class, 'getSchedules']);
            Route::patch('/open-attendance/{id}', [DoctorController::class, 'openAttendance']);
            Route::get('/today-attendance/{id}', [DoctorController::class, 'getTodayAttendance']);
            Route::get('/term-attendance/{id}', [DoctorController::class, 'getTermAttendance']);
            Route::patch('/close-attendance/{id}', [DoctorController::class, 'closeAttendance']);
            Route::patch('/mark-absent/{scheduleId}', [DoctorController::class, 'markAbsent']);

        });

        //Students
        Route::prefix('student')->middleware('ability:student')->group(function () {
            Route::get('/schedules', [StudentController::class, 'getSchedules']);
            Route::get('/is-attendance-available/{id}', [StudentController::class, 'isAttendanceAvailable']);
            Route::patch('/attend/{id}', [StudentController::class, 'attend']);
            Route::get('/attendance-history/{id}', [StudentController::class, 'attendanceHistory']);
            Route::get('/avilable-schedules', [StudentController::class, 'avilableSchedules']);
            Route::post('/register-schedule', [StudentController::class, 'registerSchedule']);
            Route::delete('/unregister-schedule/{id}', [StudentController::class, 'unregisterSchedule']);

        });

        //all
    });

