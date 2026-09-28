<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationRequest;

class VerificationRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, VerificationRequest $request): bool
    {
        return $user->isAdmin();
    }

    /**
     * Viewing the uploaded ID document itself.
     */
    public function viewIdImage(User $user, VerificationRequest $request): bool
    {
        return $user->isAdmin();
    }

    public function approve(User $user, VerificationRequest $request): bool
    {
        return $user->isAdmin() && $request->isOpen();
    }

    public function reject(User $user, VerificationRequest $request): bool
    {
        return $user->isAdmin() && $request->isOpen();
    }

    public function requestResubmission(User $user, VerificationRequest $request): bool
    {
        return $user->isAdmin() && $request->isOpen();
    }
}
