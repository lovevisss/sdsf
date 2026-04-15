<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EthicsAssessment extends Model
{
    /** @use HasFactory<\Database\Factories\EthicsAssessmentFactory> */
    use HasFactory;

    protected $fillable = [
        'ethics_profile_id',
        'year',
        'evaluator_id',
        'evaluator_role',
        'political_literacy_score',
        'education_score',
        'academic_integrity_score',
        'role_model_score',
        'integrity_score',
        'service_score',
        'total_score',
        'grade',
        'comment',
        'assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'assessed_at' => 'datetime',
            'political_literacy_score' => 'decimal:2',
            'education_score' => 'decimal:2',
            'academic_integrity_score' => 'decimal:2',
            'role_model_score' => 'decimal:2',
            'integrity_score' => 'decimal:2',
            'service_score' => 'decimal:2',
            'total_score' => 'decimal:2',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(EthicsProfile::class, 'ethics_profile_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}

