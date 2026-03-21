<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackLastActiveAt
{
    /**
     * Only refresh the timestamp at most once per 5 minutes to avoid a DB write on every request.
     */
    private const THROTTLE_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $lastActive = $user->last_active_at;

            if ($lastActive === null || now()->diffInSeconds($lastActive) >= self::THROTTLE_SECONDS) {
                $user->timestamps = false;
                $user->last_active_at = now();
                $user->save();
                $user->timestamps = true;
            }
        }

        return $next($request);
    }
}
