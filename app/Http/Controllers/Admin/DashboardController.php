<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard with Section 1A metrics.
     */
    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $metricsByScope = [
            'personal' => $this->buildRequestedMetrics($user),
        ];

        $availableScopes = ['personal'];

        if ($user->isMasterAdmin()) {
            $metricsByScope['global'] = $this->buildRequestedMetrics();
            $availableScopes[] = 'global';
        }

        return Inertia::render('Dashboard', [
            'metricsByScope' => $metricsByScope,
            'meta' => [
                'views_tracking_enabled' => false,
                'available_scopes' => $availableScopes,
                'default_scope' => 'personal',
            ],
        ]);
    }

    /**
     * Build Section 1A metric set.
     */
    private function buildRequestedMetrics(?User $user = null): array
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
