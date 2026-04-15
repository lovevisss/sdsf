<?php

namespace App\Policies;

use App\Models\EthicsPoliticalViolation;
use App\Models\User;

class EthicsPoliticalViolationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || in_array($user->role, ['leader', 'advisor'], true);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->role === 'leader';
    }

    public function view(User $user, EthicsPoliticalViolation $violation): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->role === 'leader') {
            return $user->department_id === optional($violation->profile)->department_id;
        }

        return $user->role === 'advisor' && $violation->violator_user_id === $user->id;
    }

    private function isAdmin(User $user): bool
    {
        return $user->role === 'admin' || $user->is_admin === true;
    }
}

