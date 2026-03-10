<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResetQueryMonitoring
{
    /**
     * Reset query monitoring counters at the start of each request.
     * 
     * This ensures clean state for N+1 detection, especially important in:
     * - Laravel Octane where request objects are reused
     * - Long-running processes where state might persist
     * - Exception scenarios where cleanup might not occur
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Reset query monitoring attributes at the start of each request
        $request->attributes->set('_query_count', 0);
        $request->attributes->set('_n1_logged', false);

        return $next($request);
    }
}
