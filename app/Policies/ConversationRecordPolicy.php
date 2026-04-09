<?php

namespace App\Policies;

use App\Models\ConversationRecord;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConversationRecordPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'leader', 'advisor']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ConversationRecord $conversationRecord): bool
    {
        // Advisor can view their own records
        if ($user->role === 'advisor' && $user->id === $conversationRecord->advisor_id) {
            return true;
        }

        // Student can view their own records
        if ($user->role === 'student' && $user->id === $conversationRecord->student_id) {
            return true;
        }

        // Department leader can view records from their department
        if ($user->role === 'leader' && $user->department_id === $conversationRecord->classModel->department_id) {
            return true;
        }

        // Admin can view all records
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'advisor';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ConversationRecord $conversationRecord): bool
    {
        return $user->role === 'advisor' && $user->id === $conversationRecord->advisor_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ConversationRecord $conversationRecord): bool
    {
        return $user->role === 'advisor' && $user->id === $conversationRecord->advisor_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ConversationRecord $conversationRecord): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ConversationRecord $conversationRecord): bool
    {
        return false;
    }
}
