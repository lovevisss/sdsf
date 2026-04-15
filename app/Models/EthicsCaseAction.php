<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EthicsCaseAction extends Model
{
    /** @use HasFactory<\Database\Factories\EthicsCaseActionFactory> */
    use HasFactory;

    protected $fillable = [
        'ethics_case_id',
        'actor_id',
        'action_type',
        'notes',
        'attachments',
        'happened_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'happened_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(EthicsCase::class, 'ethics_case_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

