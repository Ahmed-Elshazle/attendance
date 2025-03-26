<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $fillable =[
        'id',
        'department',
        ];
    public function academicSchedules()
    {
        return $this->hasMany(AcademicSchedule::class, 'doctor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id');
    }
}
