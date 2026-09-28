<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function showChangeForm(): View
    {
        return view('auth.change-password');
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->string('password')),
            'must_change_password' => false,
        ])->save();

        // Session ID rotation defeats fixation attacks, and rotating the
        // remember token invalidates the previously issued "remember me" cookie
        // so a leaked cookie cannot survive a password change.
        $request->session()->regenerate();
        $user->setRememberToken(Str::random(60));
        $user->save();

        $this->audit->log('password_changed', $user, 'User changed their password');

        return redirect()->route('dashboard')->with('status', 'Your password has been updated.');
    }
}
