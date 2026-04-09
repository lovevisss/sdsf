<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationRecord extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'advisor_id',
        'student_id',
        'class_model_id',
        'conversation_form',
        'conversation_method',
        'conversation_reason',
        'topic',
        'content',
        'conversation_at',
        'location',
    ];

    protected function casts(): array
    {
        return [
            'conversation_at' => 'datetime',
        ];
    }

    /**
     * Get the advisor who conducted this conversation.
     */
    public function advisor()
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    /**
     * Get the student involved in this conversation.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the class this conversation record belongs to.
     */
    public function classModel()
    {
        return $this->belongsTo(ClassModel::class);
    }
}
