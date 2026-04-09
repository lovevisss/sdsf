<?php

namespace App\Policies;

use App\Models\ConversationAppointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConversationAppointmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'advisor']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ConversationAppointment $conversationAppointment): bool
    {
        return $user->id === $conversationAppointment->advisor_id || $user->id === $conversationAppointment->student_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'student';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ConversationAppointment $conversationAppointment): bool
    {
        return $user->role === 'advisor' && $user->id === $conversationAppointment->advisor_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ConversationAppointment $conversationAppointment): bool
    {
        return $user->id === $conversationAppointment->advisor_id || $user->id === $conversationAppointment->student_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ConversationAppointment $conversationAppointment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ConversationAppointment $conversationAppointment): bool
    {
        return false;
    }
}
