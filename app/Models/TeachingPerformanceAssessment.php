<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class TeachingPerformanceAssessment extends Model
{
    protected $connection = 'staff_db';

    protected $table = 't_ejxyybt_jxyjkh';

    protected $primaryKey = 'DID';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (): void {
            throw new LogicException('TeachingPerformanceAssessment model is read-only.');
        });

        static::updating(function (): void {
            throw new LogicException('TeachingPerformanceAssessment model is read-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('TeachingPerformanceAssessment model is read-only.');
        });
    }
}
