<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EthicsEducationViolation extends Model
{
    /** @use HasFactory<\Database\Factories\EthicsEducationViolationFactory> */
    use HasFactory;

    protected $fillable = [
        'ethics_profile_id',
        'violator_user_id',
        'recorder_user_id',
        'staff_no',
        'staff_name',
        'staff_unit_name',
        'academic_year',
        'violation_type',
        'severity_level',
        'violation_at',
        'deduction_points',
        'data_source',
        'handler_department',
        'handler_user_id',
        'deduction_basis',
        'evidence_attachments',
        'verification_status',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'violation_at' => 'datetime',
            'deduction_points' => 'decimal:2',
            'evidence_attachments' => 'array',
            'verified_at' => 'datetime',
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

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handler_user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeForAnnualYear(Builder $query, int $year): Builder
    {
        $yearString = (string) $year;

        return $query->where(function (Builder $builder) use ($year, $yearString): void {
            $builder->where('academic_year', $yearString)
                ->orWhere('academic_year', 'like', $yearString.'-%')
                ->orWhere(function (Builder $fallbackQuery) use ($year): void {
                    $fallbackQuery->whereNull('academic_year')
                        ->whereYear('violation_at', $year);
                });
        });
    }
}
