<?php

namespace App\Models;

use LogicException;
use Illuminate\Database\Eloquent\Model;

class TeacherEvaluation extends Model
{
    protected $connection = 'staff_db';

    protected $table = 't_ejxyybt_jspjxxb';

    protected $primaryKey = 'WYBS';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (): void {
            throw new LogicException('TeacherEvaluation model is read-only.');
        });

        static::updating(function (): void {
            throw new LogicException('TeacherEvaluation model is read-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('TeacherEvaluation model is read-only.');
        });
    }
}
