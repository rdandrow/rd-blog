<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardMetricsService
{
    /**
     * Get role-aware dashboard scope payload.
     *
     * @return array{metricsByScope: array<string, array<string, mixed>>, availableScopes: array<int, string>, defaultScope: string}
     */
    public function getScopedMetricsForUser(User $user): array
    {
        $metricsByScope = [
            'personal' => $this->getRequestedMetrics($user),
        ];

        $availableScopes = ['personal'];

        if ($user->isMasterAdmin()) {
            $metricsByScope['global'] = $this->getRequestedMetrics();
            $availableScopes[] = 'global';
        }

        return [
            'metricsByScope' => $metricsByScope,
            'availableScopes' => $availableScopes,
            'defaultScope' => 'personal',
        ];
    }

    /**
     * Build Section 1A metric set.
     */
    public function getRequestedMetrics(?User $user = null): array
    {
        $commentsQuery = BlogPost::published();

        if ($user !== null) {
            $commentsQuery->where('user_id', $user->id);
        }

        $commentsPerPost = $commentsQuery
            ->withCount('comments')
            ->orderByDesc('comments_count')
            ->orderByDesc('published_at')
            ->limit(10)
            ->get(['id', 'title', 'slug']);

        $totalFollowers = $user !== null
            ? $user->followers()->count()
            : DB::table('user_follows')->count();

        return [
            'total_blog_post_views' => null,
            'average_views_per_blog_post' => null,
            'total_followers' => $totalFollowers,
            'comments_per_blog_post' => $commentsPerPost,
            'views_30d' => [],
        ];
    }
}
