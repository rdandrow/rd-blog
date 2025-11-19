<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If user is authenticated but doesn't have 2FA enabled
        if ($user && !$user->hasEnabledTwoFactorAuthentication()) {
            // Check if they're already on the 2FA setup page
            if (!$request->is('two-factor-challenge/*') && !$request->routeIs('auth.setup-two-factor')) {
                // Redirect to 2FA setup route
                return redirect()->route('register.setup-two-factor');
            }
        }

        return $next($request);
    }
}