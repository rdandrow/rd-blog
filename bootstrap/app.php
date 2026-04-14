<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResetQueryMonitoring;
use App\Http\Middleware\TrackLastActiveAt;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            ResetQueryMonitoring::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            TrackLastActiveAt::class,
        ]);

        $middleware->alias([
            'ensure.2fa' => \App\Http\Middleware\EnsureTwoFactorEnabled::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'master.admin' => \App\Http\Middleware\EnsureUserIsMasterAdmin::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Cleanup expired invitations daily at 2am
        $schedule->command('invitations:cleanup --force')->dailyAt('02:00');

        // Warm dashboard metrics cache for admin scopes every 5 minutes.
        $schedule->command('dashboard:warm-metrics-cache')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->when(fn (): bool => config('dashboard.warm.enabled', true));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
