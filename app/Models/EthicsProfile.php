<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EthicsProfile extends Model
{
    /** @use HasFactory<\Database\Factories\EthicsProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'staff_no',
        'department_id',
        'position',
        'identity_type',
        'hired_at',
        'status',
        'metadata',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'hired_at' => 'date',
            'last_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(EthicsAssessment::class);
    }

    public function warnings(): HasMany
    {
        return $this->hasMany(EthicsWarning::class);
    }

    public function cases(): HasMany
    {
        return $this->hasMany(EthicsCase::class);
    }

    public function politicalViolations(): HasMany
    {
        return $this->hasMany(EthicsPoliticalViolation::class);
    }

    public function educationViolations(): HasMany
    {
        return $this->hasMany(EthicsEducationViolation::class);
    }

    public function academicViolations(): HasMany
    {
        return $this->hasMany(EthicsAcademicViolation::class);
    }

    public function professionalViolations(): HasMany
    {
        return $this->hasMany(EthicsProfessionalViolation::class);
    }
}

