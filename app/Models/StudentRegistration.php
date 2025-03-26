<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentRegistration extends Model
{
    protected $fillable = ['student_id', 'academic_schedule_id', 'created_at'];

    public $timestamps = false;
    public function schedule()
    {
        return $this->belongsTo(AcademicSchedule::class, 'academic_schedule_id');
    }
}
