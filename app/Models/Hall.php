<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hall extends Model
{
    protected $fillable = [
        'hall_type',
        'name',
        'number_of_chairs_or_benches_or_computers',
    ];
}
