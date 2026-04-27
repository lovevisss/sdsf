<?php

namespace App\Policies;

use App\Models\EthicsAcademicViolation;
use App\Models\User;

class EthicsAcademicViolationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || in_array($user->role, ['leader', 'advisor'], true);
    }

    public function view(User $user, EthicsAcademicViolation $ethicsAcademicViolation): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->role === 'leader') {
            return $user->department_id === optional($ethicsAcademicViolation->profile)->department_id;
        }

        return $user->role === 'advisor' && $ethicsAcademicViolation->violator_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->role === 'leader';
    }

    private function isAdmin(User $user): bool
    {
        return $user->role === 'admin' || $user->is_admin === true;
    }
}
