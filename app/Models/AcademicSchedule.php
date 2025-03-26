<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicSchedule extends Model
{
    protected $fillable = [
        'term', 'year','course_id', 'lecture_hall_id', 'doctor_id', 'assistant_id',
        'lecture_day', 'lecture_start_hour', 'lecture_end_hour',
        'section_hall_id', 'section_day', 'section_start_hour', 'section_end_hour' ,'is_lecture_attendance_open' ,'is_section_attendance_open'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function lectureHall()
    {
        return $this->belongsTo(Hall::class, 'lecture_hall_id');
    }

    public function sectionHall()
    {
        return $this->belongsTo(Hall::class, 'section_hall_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function assistant()
    {
        return $this->belongsTo(Assistant::class, 'assistant_id');
    }
}
