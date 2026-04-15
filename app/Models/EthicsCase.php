<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EthicsCase extends Model
{
    /** @use HasFactory<\Database\Factories\EthicsCaseFactory> */
    use HasFactory;

    protected $fillable = [
        'ethics_profile_id',
        'reporter_id',
        'department_id',
        'channel',
        'is_anonymous',
        'title',
        'content',
        'risk_level',
        'status',
        'reported_at',
        'accepted_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'reported_at' => 'datetime',
            'accepted_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(EthicsProfile::class, 'ethics_profile_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(EthicsCaseAction::class);
    }
}

