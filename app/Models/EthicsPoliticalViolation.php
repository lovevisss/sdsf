<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EthicsPoliticalViolation extends Model
{
    /** @use HasFactory<\Database\Factories\EthicsPoliticalViolationFactory> */
    use HasFactory;

    protected $fillable = [
        'ethics_profile_id',
        'violator_user_id',
        'recorder_user_id',
        'staff_no',
        'staff_name',
        'staff_unit_name',
        'violation_type',
        'violation_at',
        'deduction_points',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'violation_at' => 'datetime',
            'deduction_points' => 'decimal:2',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(EthicsProfile::class, 'ethics_profile_id');
    }

    public function violator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'violator_user_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorder_user_id');
    }
}

