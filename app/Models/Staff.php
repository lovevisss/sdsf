<?php

namespace App\Models;

use LogicException;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $connection = 'staff_db';

    protected $table = 't_ejxyybt_jzgjbxx';

    public $timestamps = false;

    protected $primaryKey = 'gh';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'gh',
        'xm',
        'dwmc',
        'bmmc',
        'gw',
        'sflx',
        'sjh',
        'zt',
    ];

    protected static function booted(): void
    {
        static::creating(function (): void {
            throw new LogicException('Staff model is read-only.');
        });

        static::updating(function (): void {
            throw new LogicException('Staff model is read-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('Staff model is read-only.');
        });
    }

    public function getStaffNoAttribute(): ?string
    {
        return $this->gh;
    }

    public function getNameAttribute(): ?string
    {
        return $this->xm;
    }

    public function getUnitNameAttribute(): ?string
    {
        return $this->dwmc ?? $this->bmmc;
    }

    /**
     * @return array{staff_no: string|null, name: string|null, unit_name: string|null}
     */
    public function toArchiveArray(): array
    {
        return [
            'staff_no' => $this->staff_no,
            'name' => $this->name,
            'unit_name' => $this->unit_name,
        ];
    }
}
