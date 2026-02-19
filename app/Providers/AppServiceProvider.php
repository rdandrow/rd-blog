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
     */
    protected function enableQueryMonitoring(): void
    {
        // Only enable in local and staging environments
        if (!app()->environment(['local', 'staging'])) {
            return;
        }

        // Track query count per request for N+1 detection without memory overhead
        $enableN1Detection = config('app.debug');
        $queryCount = 0;

        DB::listen(function ($query) use ($enableN1Detection, &$queryCount) {
            // Increment query counter
            if ($enableN1Detection) {
                $queryCount++;
            }

            // Log slow queries (>100ms)
            if ($query->time > 100) {
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                    'connection' => $query->connectionName,
                ]);
            }

            // Log extremely slow queries with stack trace (>500ms)
            if ($query->time > 500) {
                Log::error('Extremely slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                    'connection' => $query->connectionName,
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
                ]);
            }

            // Detect N+1 query problems (>50 queries in a request)
            // Uses a simple counter instead of query log to avoid memory accumulation
            if ($enableN1Detection && $queryCount > 50) {
                // Log only once when threshold is crossed
                static $n1Logged = false;
                if (!$n1Logged) {
                    Log::warning('Potential N+1 query problem detected', [
                        'query_count' => $queryCount,
                        'last_query' => $query->sql,
                        'url' => request()->fullUrl(),
                    ]);
                    $n1Logged = true;
                }
            }
        });
    }
}
