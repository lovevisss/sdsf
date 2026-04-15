<?php

namespace App\Policies;

use App\Models\EthicsCase;
use App\Models\User;

class EthicsCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || in_array($user->role, ['leader', 'advisor', 'student'], true);
    }

    public function view(User $user, EthicsCase $case): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->role === 'leader' && $user->department_id === $case->department_id) {
            return true;
        }

        if ($case->reporter_id === $user->id) {
            return true;
        }

        return $user->role === 'advisor' && optional($case->profile)->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || in_array($user->role, ['student', 'advisor', 'leader'], true);
    }

    public function updateStatus(User $user, EthicsCase $case): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $user->role === 'leader' && $user->department_id === $case->department_id;
    }

    public function addAction(User $user, EthicsCase $case): bool
    {
        if ($this->updateStatus($user, $case)) {
            return true;
        }

        return $user->role === 'advisor' && optional($case->profile)->user_id === $user->id;
    }

    private function isAdmin(User $user): bool
    {
        return $user->role === 'admin' || $user->is_admin === true;
    }
}

