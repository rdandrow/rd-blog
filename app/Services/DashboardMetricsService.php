<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardMetricsService
{
    private const TREND_DAYS = 30;

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

        $highValueMetrics = $this->getHighValueMetrics($user);

        return [
            'total_blog_post_views' => null,
            'average_views_per_blog_post' => null,
            'total_followers' => $totalFollowers,
            'comments_per_blog_post' => $commentsPerPost,
            'views_30d' => [],
            'high_value_metrics' => $highValueMetrics,
        ];
    }

    /**
     * Build Section 1B high-value metric set.
     */
    public function getHighValueMetrics(?User $user = null): array
    {
        $publishedPostsQuery = BlogPost::published();
        $draftPostsQuery = BlogPost::query()->where('is_published', false);
        $featuredPostsQuery = BlogPost::published()->where('is_featured', true);

        if ($user !== null) {
            $publishedPostsQuery->where('user_id', $user->id);
            $draftPostsQuery->where('user_id', $user->id);
            $featuredPostsQuery->where('user_id', $user->id);
        }

        $publishedPosts = $publishedPostsQuery->count();
        $draftPosts = $draftPostsQuery->count();
        $featuredPosts = $featuredPostsQuery->count();

        $publishedPostIds = BlogPost::published()
            ->when($user !== null, fn ($query) => $query->where('user_id', $user->id))
            ->pluck('id');

        $totalCommentsOnPublished = $publishedPostIds->isEmpty()
            ? 0
            : DB::table('comments')->whereIn('blog_post_id', $publishedPostIds)->count();

        $totalLikesOnPublished = $publishedPostIds->isEmpty()
            ? 0
            : DB::table('blog_post_likes')->whereIn('blog_post_id', $publishedPostIds)->count();

        $avgCommentsPerPublished = $publishedPosts > 0
            ? round($totalCommentsOnPublished / $publishedPosts, 2)
            : 0;

        $avgLikesPerPublished = $publishedPosts > 0
            ? round($totalLikesOnPublished / $publishedPosts, 2)
            : 0;

        $activeAuthors30d = BlogPost::published()
            ->where('published_at', '>=', now()->subDays(30))
            ->when($user !== null, fn ($query) => $query->where('user_id', $user->id))
            ->distinct('user_id')
            ->count('user_id');

        $postsPublished30d = BlogPost::published()
            ->where('published_at', '>=', now()->subDays(29)->startOfDay())
            ->when($user !== null, fn ($query) => $query->where('user_id', $user->id))
            ->selectRaw('DATE(published_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $commentsCreated30d = DB::table('comments as c')
            ->join('blog_posts as p', 'p.id', '=', 'c.blog_post_id')
            ->where('p.is_published', true)
            ->where('p.published_at', '<=', now())
            ->when($user !== null, fn ($query) => $query->where('p.user_id', $user->id))
            ->where('c.created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(c.created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $likesCreated30d = DB::table('blog_post_likes as l')
            ->join('blog_posts as p', 'p.id', '=', 'l.blog_post_id')
            ->where('p.is_published', true)
            ->where('p.published_at', '<=', now())
            ->when($user !== null, fn ($query) => $query->where('p.user_id', $user->id))
            ->where('l.created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(l.created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $followerGrowth30d = DB::table('user_follows')
            ->when($user !== null, fn ($query) => $query->where('following_id', $user->id))
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $twoFactorAdoptionRate = null;
        $invitationFunnel = [
            'pending' => null,
            'accepted' => null,
            'expired' => null,
        ];

        if ($user === null) {
            $totalUsers = User::count();
            $twoFactorUsers = User::whereNotNull('two_factor_confirmed_at')->count();

            $twoFactorAdoptionRate = $totalUsers > 0
                ? round(($twoFactorUsers / $totalUsers) * 100, 1)
                : 0;

            $invitationFunnel = [
                'pending' => User::whereNotNull('invitation_token')
                    ->whereNull('invitation_accepted_at')
                    ->where('invitation_sent_at', '>=', now()->subHours(48))
                    ->count(),
                'accepted' => User::whereNotNull('invitation_accepted_at')->count(),
                'expired' => User::whereNotNull('invitation_token')
                    ->whereNull('invitation_accepted_at')
                    ->where('invitation_sent_at', '<', now()->subHours(48))
                    ->count(),
            ];
        }

        return [
            'published_posts' => $publishedPosts,
            'draft_posts' => $draftPosts,
            'featured_posts' => $featuredPosts,
            'total_comments_on_published_posts' => $totalCommentsOnPublished,
            'total_likes_on_published_posts' => $totalLikesOnPublished,
            'avg_comments_per_published_post' => $avgCommentsPerPublished,
            'avg_likes_per_published_post' => $avgLikesPerPublished,
            'active_authors_30d' => $activeAuthors30d,
            'follower_growth_30d' => $this->backfill30DayTrend($followerGrowth30d),
            'posts_published_30d' => $this->backfill30DayTrend($postsPublished30d),
            'comments_created_30d' => $this->backfill30DayTrend($commentsCreated30d),
            'likes_created_30d' => $this->backfill30DayTrend($likesCreated30d),
            'two_factor_adoption_rate' => $twoFactorAdoptionRate,
            'invitation_funnel' => $invitationFunnel,
        ];
    }

    private function backfill30DayTrend(iterable $rows): array
    {
        $countsByDay = [];

        foreach ($rows as $row) {
            $day = is_array($row) ? ($row['day'] ?? null) : ($row->day ?? null);
            $count = is_array($row) ? ($row['count'] ?? null) : ($row->count ?? null);

            if ($day === null || $count === null) {
                continue;
            }

            $dayKey = substr((string) $day, 0, 10);
            $countsByDay[$dayKey] = (int) $count;
        }

        $start = now()->subDays(self::TREND_DAYS - 1)->startOfDay();
        $backfilled = [];

        for ($offset = 0; $offset < self::TREND_DAYS; $offset++) {
            $day = $start->copy()->addDays($offset)->toDateString();

            $backfilled[] = [
                'day' => $day,
                'count' => $countsByDay[$day] ?? 0,
            ];
        }

        return $backfilled;
    }
}
