<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class StaffMonthlyAttendanceStatistic extends Model
{
    protected $connection = 'staff_db';

    protected $table = 't_ejxyybt_jzgydkqtjb';

    public $timestamps = false;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (): void {
            throw new LogicException('Staff monthly attendance statistics are read-only.');
        });

        static::updating(function (): void {
            throw new LogicException('Staff monthly attendance statistics are read-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('Staff monthly attendance statistics are read-only.');
        });
    }
}
