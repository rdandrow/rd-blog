<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Console\Command;

class WarmDashboardMetricsCache extends Command
{
    protected $signature = 'dashboard:warm-metrics-cache
                            {--user-id=* : Limit warming to specific admin user IDs}';

    protected $description = 'Warm cached dashboard metrics for admin and master admin users';

    public function handle(DashboardMetricsService $dashboardMetricsService): int
    {
        if (!config('dashboard.cache.enabled', true)) {
            $this->info('Dashboard metrics cache is disabled.');

            return self::SUCCESS;
        }

        $query = User::query()->whereIn('role', ['admin', 'master_admin'])->orderBy('id');

        $requestedIds = collect($this->option('user-id'))
            ->map(fn (string $id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values();

        if ($requestedIds->isNotEmpty()) {
            $query->whereIn('id', $requestedIds->all());
        }

        $users = $query->get(['id', 'role']);

        if ($users->isEmpty()) {
            $this->info('No admin users found to warm dashboard metrics cache.');

            return self::SUCCESS;
        }

        $warmedScopes = 0;

        foreach ($users as $user) {
            $payload = $dashboardMetricsService->getScopedMetricsForUser($user);
            $warmedScopes += count($payload['availableScopes']);
        }

        $this->info(sprintf(
            'Warmed dashboard metrics cache for %d admin user(s) across %d scope payload(s).',
            $users->count(),
            $warmedScopes
        ));

        return self::SUCCESS;
    }
}
