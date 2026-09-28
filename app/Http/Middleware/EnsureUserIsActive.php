<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsActive
{
    /**
     * Terminate sessions belonging to users who have been deactivated.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user !== null && (! $user->is_active || ! $user->isApproved())) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is not authorized to sign in. Please contact an administrator.',
            ]);
        }

        return $next($request);
    }
}
