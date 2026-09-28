<?php

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    /**
     * Whether the user may approve a pending registration request.
     */
    public function approve(User $user, User $target): bool
    {
        return $user->isAdmin()
            && $target->role === UserRole::Requester
            && $target->account_status === AccountStatus::Pending;
    }

    /**
     * Whether the user may reject a pending registration request.
     */
    public function reject(User $user, User $target): bool
    {
        return $user->isAdmin()
            && $target->role === UserRole::Requester
            && $target->account_status === AccountStatus::Pending;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->isAdmin();
    }
}
