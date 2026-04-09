<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    /** @use HasFactory<\Database\Factories\ClassModelFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'department_id',
        'grade',
        'description',
    ];

    /**
     * Get the department this class belongs to.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get all conversation records for this class.
     */
    public function conversationRecords()
    {
        return $this->hasMany(ConversationRecord::class);
    }
}
