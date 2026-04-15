<?php

namespace App\Policies;

use App\Models\EthicsProfile;
use App\Models\User;

class EthicsProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || in_array($user->role, ['leader', 'advisor'], true);
    }

    public function view(User $user, EthicsProfile $profile): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->role === 'leader' && $user->department_id === $profile->department_id) {
            return true;
        }

        return $profile->user_id === $user->id;
    }

    public function update(User $user, EthicsProfile $profile): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $user->role === 'leader' && $user->department_id === $profile->department_id;
    }

    private function isAdmin(User $user): bool
    {
        return $user->role === 'admin' || $user->is_admin === true;
    }
}

