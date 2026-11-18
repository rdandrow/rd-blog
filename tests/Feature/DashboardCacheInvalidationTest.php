<?php

use App\Services\DashboardMetricsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

describe('DashboardMetricsService cache invalidation', function () {
    test('increments the runtime cache version once per invalidation on stores without tags', function () {
        $path = sys_get_temp_dir().'/dashboard-cache-'.uniqid();

        config([
            'dashboard.cache.enabled' => true,
            'cache.default' => 'file',
            'cache.stores.file.path' => $path,
        ]);

        $service = app(DashboardMetricsService::class);
        $service->invalidateDashboardCache();
        $first = Cache::get('dashboard:metrics:cache_version');
        $service->invalidateDashboardCache();
        $second = Cache::get('dashboard:metrics:cache_version');

        File::deleteDirectory($path);

        expect($second - $first)->toBe(1)
            ->and($first)->toBeGreaterThan(1);
    });

    test('never throws when the cache backend fails and logs a warning instead', function () {
        config(['dashboard.cache.enabled' => true]);

        Cache::shouldReceive('supportsTags')->andThrow(new RuntimeException('cache down'));
        Log::shouldReceive('warning')->once()->withArgs(
            fn (string $message, array $context) => str_contains($message, 'invalidate dashboard metrics cache')
                && $context['message'] === 'cache down'
        );

        app(DashboardMetricsService::class)->invalidateDashboardCache();

        expect(true)->toBeTrue();
    });
});
