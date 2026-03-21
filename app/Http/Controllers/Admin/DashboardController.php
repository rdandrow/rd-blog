<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardMetricsService $dashboardMetricsService,
    ) {
    }

    /**
     * Display the admin dashboard with Section 1A metrics.
     */
    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $scopePayload = $this->dashboardMetricsService->getScopedMetricsForUser($user);

        return Inertia::render('Dashboard', [
            'metricsByScope' => $scopePayload['metricsByScope'],
            'meta' => [
                'views_tracking_enabled' => $scopePayload['viewsTrackingEnabled'],
                'available_scopes' => $scopePayload['availableScopes'],
                'default_scope' => $scopePayload['defaultScope'],
            ],
        ]);
    }
}
