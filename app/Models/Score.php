<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    protected $table = 'scores';
    protected $fillable = [
        'teacher_id', 'dimension', 'current_score', 'updated_at'
    ];
}
