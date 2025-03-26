<?php

namespace App\Models;

use App\Models\UnverifiedStudent;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['id', 'department', 'grade', 'date_of_birth', 'address' ];

}
