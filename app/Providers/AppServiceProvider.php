<?php

namespace App\Providers;

use App\Services\BlogImageService;
use App\Services\BlogPostService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services as singletons for better performance
        $this->app->singleton(BlogImageService::class);
        $this->app->singleton(BlogPostService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure rate limiters
        RateLimiter::for('invitation-show', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('invitation-accept', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('admin-invitation-resend', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?? $request->ip());
        });

        // Enable query performance monitoring
        $this->enableQueryMonitoring();
    }

    /**
     * Enable query performance monitoring for development and staging.
     * 
     * Note: Query counters and flags are reset per request via ResetQueryMonitoring
     * middleware to ensure clean state in Octane and long-running processes.
     */
    protected function enableQueryMonitoring(): void
    {
        // Only enable in local and staging environments
        if (!app()->environment(['local', 'staging'])) {
            return;
        }

        // Track query count per request for N+1 detection without memory overhead
        // Counter is reset at the start of each request by ResetQueryMonitoring middleware
        $enableN1Detection = config('app.debug');

        // Captured once here rather than inside the closure to avoid a container
        // lookup on every query. Console commands and tests share a single synthetic
        // request for their entire lifetime, so the counter would never be reset and
        // would trigger false N+1 warnings — skip counter/N+1 logic in those contexts.
        $isHttpRequest = ! app()->runningInConsole();
        $logBindings = $this->shouldLogQueryBindings();

        DB::listen(function ($query) use ($enableN1Detection, $isHttpRequest, $logBindings) {
            $request = request();

            $bindings = $logBindings
                ? $this->sanitizeBindings($query->bindings)
                : '[REDACTED]';

            // Increment query counter (scoped to current HTTP request only)
            if ($enableN1Detection && $isHttpRequest && $request) {
                $queryCount = $request->attributes->get('_query_count', 0) + 1;
                $request->attributes->set('_query_count', $queryCount);
            }

            // Log slow queries (>100ms)
            if ($query->time > 100) {
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $bindings,
                    'binding_count' => count($query->bindings),
                    'time' => $query->time . 'ms',
                    'connection' => $query->connectionName,
                ]);
            }

            // Log extremely slow queries with stack trace (>500ms)
            if ($query->time > 500) {
                Log::error('Extremely slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $bindings,
                    'binding_count' => count($query->bindings),
                    'time' => $query->time . 'ms',
                    'connection' => $query->connectionName,
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
                ]);
            }

            // Detect N+1 query problems (>50 queries in a request)
            // Uses a simple counter instead of query log to avoid memory accumulation
            if ($enableN1Detection && $isHttpRequest && $request && isset($queryCount) && $queryCount > 50) {
                // Log only once per request when threshold is crossed
                if (!$request->attributes->get('_n1_logged', false)) {
                    Log::warning('Potential N+1 query problem detected', [
                        'query_count' => $queryCount,
                        'last_query' => $query->sql,
                        'url' => $request->fullUrl(),
                    ]);
                    $request->attributes->set('_n1_logged', true);
                }
            }
        });
    }

    /**
     * Whether query bindings should be included in logs.
     */
    protected function shouldLogQueryBindings(): bool
    {
        return (bool) env('LOG_QUERY_BINDINGS', false);
    }

    /**
     * Sanitize query bindings to avoid logging sensitive values directly.
     */
    protected function sanitizeBindings(array $bindings): array
    {
        return array_map(function ($binding) {
            if (is_null($binding) || is_bool($binding) || is_int($binding) || is_float($binding)) {
                return $binding;
            }

            if ($binding instanceof \DateTimeInterface) {
                return $binding->format(DATE_ATOM);
            }

            if (is_string($binding)) {
                return '[REDACTED]';
            }

            return '[REDACTED]';
        }, $bindings);
    }
}
