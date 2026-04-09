<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
    ];

    /**
     * Get all records under this conversation.
     */
    public function records()
    {
        return $this->hasMany(ConversationRecord::class);
    }
}
