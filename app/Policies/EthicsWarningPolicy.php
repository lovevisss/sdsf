<?php

namespace App\Policies;

use App\Models\EthicsWarning;
use App\Models\User;

class EthicsWarningPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || in_array($user->role, ['leader', 'advisor'], true);
    }

    public function view(User $user, EthicsWarning $warning): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->role === 'leader' && $user->department_id === optional($warning->profile)->department_id) {
            return true;
        }

        return $user->role === 'advisor' && optional($warning->profile)->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $user->role === 'leader';
    }

    public function close(User $user, EthicsWarning $warning): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $user->role === 'leader' && $user->department_id === optional($warning->profile)->department_id;
    }

    private function isAdmin(User $user): bool
    {
        return $user->role === 'admin' || $user->is_admin === true;
    }
}

