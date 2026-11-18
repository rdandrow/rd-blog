<?php

namespace App\Console\Commands;

use App\Models\BlogPostView;
use App\Services\DashboardMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PruneBlogPostViews extends Command
{
    protected $signature = 'dashboard:prune-views
                            {--days= : Delete views older than this many days (defaults to dashboard.views.retention_days)}';

    protected $description = 'Delete blog post views older than the configured retention window';

    public function handle(DashboardMetricsService $dashboardMetricsService): int
    {
        $days = $this->option('days') ?? config('dashboard.views.retention_days');

        if ($days === null || $days === '' || ! ctype_digit((string) $days) || (int) $days < 1) {
            $this->info('No valid view retention window configured; nothing was pruned.');

            return self::SUCCESS;
        }

        if (! Schema::hasTable('blog_post_views')) {
            $this->info('blog_post_views table does not exist; nothing to prune.');

            return self::SUCCESS;
        }

        $deleted = BlogPostView::query()
            ->where('viewed_at', '<', now()->subDays((int) $days))
            ->delete();

        // Total view counts changed, so drop any cached dashboard payloads.
        if ($deleted > 0) {
            $dashboardMetricsService->invalidateDashboardCache();
        }

        $this->info(sprintf('Pruned %d blog post view(s) older than %d day(s).', $deleted, (int) $days));

        return self::SUCCESS;
    }
}
