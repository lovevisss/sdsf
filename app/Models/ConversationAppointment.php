<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationAppointment extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationAppointmentFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id',
        'advisor_id',
        'appointment_type',
        'remarks',
        'status',
        'appointed_at',
    ];

    protected function casts(): array
    {
        return [
            'appointed_at' => 'datetime',
        ];
    }

    /**
     * Get the student who requested the appointment.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the advisor who received the appointment request.
     */
    public function advisor()
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    /**
     * Check if appointment is pending confirmation.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Confirm the appointment.
     */
    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
    }

    /**
     * Complete the appointment.
     */
    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }
}
