<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assistant extends Model
{
    protected $fillable =[
        'id',
        'department',
        ];
    public function user()
    {
        return $this->belongsTo(User::class, 'id');
    }
}
