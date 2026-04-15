<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $table = 'alerts';
    protected $fillable = [
        'teacher_id', 'alert_level', 'message', 'created_at'
    ];
}
