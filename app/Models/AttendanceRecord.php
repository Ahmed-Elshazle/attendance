<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $fillable =[
        'student_id',
        'schedule_id',
        'attendance_status',
        'session_type',
        'attend_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
    public $timestamps = false;
}
