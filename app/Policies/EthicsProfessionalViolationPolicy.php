<?php

namespace App\Policies;

use App\Models\EthicsProfessionalViolation;
use App\Models\User;

class EthicsProfessionalViolationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || in_array($user->role, ['leader', 'advisor'], true);
    }

    public function view(User $user, EthicsProfessionalViolation $ethicsProfessionalViolation): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->role === 'leader') {
            return $user->department_id === optional($ethicsProfessionalViolation->profile)->department_id;
        }

        return $user->role === 'advisor' && $ethicsProfessionalViolation->violator_user_id === $user->id;
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
