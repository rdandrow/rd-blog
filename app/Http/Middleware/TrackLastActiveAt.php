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

            // Carbon 3's diffInSeconds() is signed (negative when $lastActive is in the past),
            // so compare timestamps directly instead.
            if ($lastActive === null || $lastActive->lte(now()->subSeconds(self::THROTTLE_SECONDS))) {
                $user->timestamps = false;

                try {
                    $user->last_active_at = now();
                    $user->save();
                } finally {
                    $user->timestamps = true;
                }
            }
        }

        return $next($request);
    }
}
