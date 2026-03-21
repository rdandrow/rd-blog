<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(Tests\TestCase::class, RefreshDatabase::class);

describe('DashboardController', function () {
    test('returns inertia payload from dashboard metrics service contract', function () {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $scopePayload = [
            'metricsByScope' => [
                'personal' => [
                    'total_blog_post_views' => 0,
                    'average_views_per_blog_post' => 0,
                    'total_followers' => 2,
                    'comments_per_blog_post' => [],
                    'views_30d' => [],
                ],
            ],
            'availableScopes' => ['personal'],
            'defaultScope' => 'personal',
            'viewsTrackingEnabled' => true,
        ];

        $service = Mockery::mock(DashboardMetricsService::class);
        $service->shouldReceive('getScopedMetricsForUser')
            ->once()
            ->withArgs(fn (User $user) => $user->is($admin))
            ->andReturn($scopePayload);

        $controller = new DashboardController($service);
        $response = $controller->index();

        $request = Request::create('/admin/dashboard', 'GET');
        $request->headers->set('X-Inertia', 'true');

        $httpResponse = $response->toResponse($request);
        $payload = $httpResponse->getData(true);

        expect($payload['component'])->toBe('Dashboard')
            ->and($payload['props']['metricsByScope'])->toBe($scopePayload['metricsByScope'])
            ->and($payload['props']['meta']['available_scopes'])->toBe(['personal'])
            ->and($payload['props']['meta']['default_scope'])->toBe('personal')
            ->and($payload['props']['meta']['views_tracking_enabled'])->toBeTrue();
    })->group('dashboard', 'dashboard-contract', 'unit', 'controller');

    test('returns inertia payload with personal and global scopes for master admin contract', function () {
        $masterAdmin = User::factory()->masterAdmin()->create();
        $this->actingAs($masterAdmin);

        $scopePayload = [
            'metricsByScope' => [
                'personal' => [
                    'total_blog_post_views' => 0,
                    'average_views_per_blog_post' => 0,
                    'total_followers' => 1,
                    'comments_per_blog_post' => [],
                    'views_30d' => [],
                ],
                'global' => [
                    'total_blog_post_views' => 0,
                    'average_views_per_blog_post' => 0,
                    'total_followers' => 9,
                    'comments_per_blog_post' => [],
                    'views_30d' => [],
                ],
            ],
            'availableScopes' => ['personal', 'global'],
            'defaultScope' => 'personal',
            'viewsTrackingEnabled' => true,
        ];

        $service = Mockery::mock(DashboardMetricsService::class);
        $service->shouldReceive('getScopedMetricsForUser')
            ->once()
            ->withArgs(fn (User $user) => $user->is($masterAdmin))
            ->andReturn($scopePayload);

        $controller = new DashboardController($service);
        $response = $controller->index();

        $request = Request::create('/admin/dashboard', 'GET');
        $request->headers->set('X-Inertia', 'true');

        $httpResponse = $response->toResponse($request);
        $payload = $httpResponse->getData(true);

        expect($payload['component'])->toBe('Dashboard')
            ->and($payload['props']['metricsByScope'])->toBe($scopePayload['metricsByScope'])
            ->and($payload['props']['meta']['available_scopes'])->toBe(['personal', 'global'])
            ->and($payload['props']['meta']['default_scope'])->toBe('personal')
            ->and($payload['props']['meta']['views_tracking_enabled'])->toBeTrue();
    })->group('dashboard', 'dashboard-contract', 'unit', 'controller');
});
