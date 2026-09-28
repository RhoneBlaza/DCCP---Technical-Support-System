<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    protected const MAX_ATTEMPTS = 3;

    public function __construct(protected AuditLogger $audit) {}

    public function showLinkRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(ForgotPasswordRequest $request): RedirectResponse
    {
        $key = 'password-reset:'.strtolower($request->string('email')).':'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('auth.throttle', ['seconds' => $seconds])]);
        }

        RateLimiter::hit($key, 3600);

        Password::sendResetLink($request->only('email'));

        $this->audit->log('password_reset_requested', null, 'Password reset link requested for '.$request->string('email'));

        return back()->with('status', __('passwords.sent'));
    }
}
