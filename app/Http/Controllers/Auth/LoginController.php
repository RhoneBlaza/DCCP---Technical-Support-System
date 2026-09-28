<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public const MAX_ATTEMPTS = 5;

    public const LOCKOUT_MINUTES = 1;

    private static ?string $dummyHash = null;

    public function __construct(protected AuditLogger $audit) {}

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $this->ensureNotRateLimited($request);

        $email = strtolower((string) $request->string('email'));
        $password = (string) $request->string('password');

        $candidate = User::query()->where('email', $email)->first();

        // Always verify exactly one password hash so response timing does not
        // reveal whether the account exists.
        $passwordMatches = $candidate !== null && Hash::check($password, $candidate->password);

        // Only approved accounts may sign in. A pending, rejected or suspended
        // account whose credentials are correct is sent to a clear status page
        // instead of receiving a generic "invalid credentials" error.
        if ($candidate !== null && $passwordMatches && ! $candidate->isApproved()) {
            $this->audit->log('login_blocked', $candidate, 'Sign-in blocked because the account is not approved.');

            return redirect()->signedRoute(
                'account.status',
                ['user' => $candidate->id],
                now()->addMinutes(15)
            );
        }

        $credentialsMatch = $candidate !== null
            && $passwordMatches
            && $candidate->is_active;

        if (! $credentialsMatch) {
            // For nonexistent or deactivated accounts, apply a sacrificial hash
            // so the request still costs one bcrypt round before failing.
            if ($candidate === null || ! $passwordMatches) {
                $this->normalizeHashTiming($password);
            }

            RateLimiter::hit($this->throttleKey($request), self::LOCKOUT_MINUTES * 60);

            $this->audit->log('failed_login', null, 'Failed login attempt for '.$email);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ])->redirectTo(route('login'));
        }

        RateLimiter::clear($this->throttleKey($request));

        Auth::login($candidate, $request->boolean('remember'));

        $request->session()->regenerate();

        $candidate->forceFill(['last_login_at' => now()])->save();

        $this->audit->log('login', $candidate, 'User logged in');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->audit->log('logout', $request->user(), 'User logged out');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function ensureNotRateLimited(LoginRequest $request): void
    {
        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', ['seconds' => $seconds]),
            ])->redirectTo(route('login'));
        }
    }

    protected function throttleKey(LoginRequest $request): string
    {
        return 'login:'.strtolower($request->string('email')).':'.$request->ip();
    }

    /**
     * Perform a faucet password verification against a throwaway hash generated
     * at the configured bcrypt cost, so a login for an unknown email does not
     * complete measurably faster than one against a real account.
     */
    protected function normalizeHashTiming(string $password): void
    {
        if (self::$dummyHash === null) {
            self::$dummyHash = Hash::make(
                'timing-normalization-only',
                [
                    'rounds' => (int) config('hashing.bcrypt.rounds', 12),
                ]
            );
        }

        Hash::check($password, self::$dummyHash);
    }
}
