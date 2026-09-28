<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectIfMustChangePassword
{
    /**
     * Redirect users with must_change_password=true to the change password page.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        $allowedRoutes = [
            'profile.change-password',
            'profile.change-password.store',
            'logout',
        ];

        if ($user !== null && $user->must_change_password && ! in_array($request->route()?->getName(), $allowedRoutes, true)) {
            return redirect()->route('profile.change-password');
        }

        return $next($request);
    }
}
