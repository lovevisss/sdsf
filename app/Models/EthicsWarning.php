<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EthicsWarning extends Model
{
    /** @use HasFactory<\Database\Factories\EthicsWarningFactory> */
    use HasFactory;

    protected $fillable = [
        'ethics_profile_id',
        'assignee_id',
        'warning_level',
        'source_type',
        'reason',
        'status',
        'detected_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(EthicsProfile::class, 'ethics_profile_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}

