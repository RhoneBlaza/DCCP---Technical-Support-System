<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class AccountStatusController extends Controller
{
    /**
     * Show a pending/rejected/suspended account its verification status.
     */
    public function show(User $user): View
    {
        // Approved accounts must sign in normally; this page is low
        // information and should not reveal whether an approved account exists.
        abort_if($user->isApproved(), 404);

        $latestRequest = $user->verificationRequests()
            ->latest('submitted_at')
            ->first();

        return view('auth.account-status', ['user' => $user, 'latestRequest' => $latestRequest]);
    }
}
