<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Violation extends Model
{
    protected $table = 'violations';
    protected $fillable = [
        'teacher_id', 'dimension', 'description', 'points_deducted', 'created_at'
    ];
}
