<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    /**
     * Routes that should be excluded from 2FA enforcement.
     */
    protected array $except = [
        'login',
        'logout',
        'register',
        'register/*',
        'password/*',
        'email/*',
        'two-factor-challenge',
        'two-factor-challenge/*',
        'user/two-factor-authentication',
        'user/two-factor-qr-code',
        'user/two-factor-secret-key',
        'user/two-factor-recovery-codes',
        'user/confirmed-two-factor-authentication',
        'settings/two-factor',
        'admin/login',
        'admin/register',
        'admin/logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Skip if user is not authenticated
        if (!$user) {
            return $next($request);
        }

        // Skip if route is in the exception list
        foreach ($this->except as $pattern) {
            if ($request->is($pattern) || $request->routeIs($pattern)) {
                return $next($request);
            }
        }

        // If user is authenticated but doesn't have 2FA enabled
        if (!$user->hasEnabledTwoFactorAuthentication()) {
            // Redirect to 2FA setup route
            return redirect()->route('register.setup-two-factor');
        }

        return $next($request);
    }
}