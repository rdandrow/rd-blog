<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardMetricsService
{
    private const TREND_DAYS = 30;
    private const CACHE_KEY_VERSION = 1;
    private const DEFAULT_CACHE_TTL_SECONDS = 600;

    /**
     * Get role-aware dashboard scope payload.
     *
     * @return array{metricsByScope: array<string, array<string, mixed>>, availableScopes: array<int, string>, defaultScope: string, viewsTrackingEnabled: bool}
     */
    public function getScopedMetricsForUser(User $user): array
    {
        $viewsTrackingEnabled = $this->isViewsTrackingEnabled();

        $metricsByScope = [
            'personal' => $this->getCachedRequestedMetrics($user, $viewsTrackingEnabled),
        ];

        $availableScopes = ['personal'];

        if ($user->isMasterAdmin()) {
            $metricsByScope['global'] = $this->getCachedRequestedMetrics(null, $viewsTrackingEnabled);
            $availableScopes[] = 'global';
        }

        return [
            'metricsByScope' => $metricsByScope,
            'availableScopes' => $availableScopes,
            'defaultScope' => 'personal',
            'viewsTrackingEnabled' => $viewsTrackingEnabled,
        ];
    }

    /**
     * Build Section 1A metric set.
     */
    public function getRequestedMetrics(?User $user = null, ?bool $viewsTrackingEnabled = null): array
    {
        $viewsTrackingEnabled ??= $this->isViewsTrackingEnabled();

        $commentsQuery = BlogPost::published();

        if ($user !== null) {
            $commentsQuery->where('user_id', $user->id);
        }

        $commentsPerPost = $commentsQuery
            ->withCount(['comments', 'likes'])
            ->orderByDesc('comments_count')
            ->orderByDesc('likes_count')
            ->orderByDesc('published_at')
            ->limit(10)
            ->get(['id', 'title', 'slug']);

        $totalFollowers = $user !== null
            ? $user->followers()->count()
            : DB::table('user_follows')->count();

        $totalBlogPostViews = null;
        $averageViewsPerBlogPost = null;
        $views30d = [];

        if ($viewsTrackingEnabled) {
            $viewsBaseQuery = DB::table('blog_post_views as v')
                ->join('blog_posts as p', 'p.id', '=', 'v.blog_post_id')
                ->where('p.is_published', true)
                ->whereNotNull('p.published_at')
                ->where('p.published_at', '<=', now())
                ->when($user !== null, fn ($query) => $query->where('p.user_id', $user->id));

            $totalBlogPostViews = (clone $viewsBaseQuery)->count();

            $publishedPostsForScope = BlogPost::published()
                ->when($user !== null, fn ($query) => $query->where('user_id', $user->id))
                ->count();

            $averageViewsPerBlogPost = $publishedPostsForScope > 0
                ? round($totalBlogPostViews / $publishedPostsForScope, 2)
                : 0;

            $rawViews30d = (clone $viewsBaseQuery)
                ->where('v.viewed_at', '>=', now()->subDays(29)->startOfDay())
                ->selectRaw('DATE(v.viewed_at) as day, COUNT(*) as count')
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $views30d = $this->backfill30DayTrend($rawViews30d);
        }

        $highValueMetrics = $this->getHighValueMetrics($user);

        return [
            'total_blog_post_views' => $totalBlogPostViews,
            'average_views_per_blog_post' => $averageViewsPerBlogPost,
            'total_followers' => $totalFollowers,
            'comments_per_blog_post' => $commentsPerPost,
            'views_30d' => $views30d,
            'high_value_metrics' => $highValueMetrics,
        ];
    }

    private function getCachedRequestedMetrics(?User $user, bool $viewsTrackingEnabled): array
    {
        if (!config('dashboard.cache.enabled', true)) {
            return $this->getRequestedMetrics($user, $viewsTrackingEnabled);
        }

        $ttlSeconds = $this->dashboardCacheTtlSeconds();

        if ($ttlSeconds <= 0) {
            return $this->getRequestedMetrics($user, $viewsTrackingEnabled);
        }

        $cacheKey = $this->dashboardCacheKey($user, $viewsTrackingEnabled);

        return Cache::remember(
            $cacheKey,
            now()->addSeconds($ttlSeconds),
            fn () => $this->getRequestedMetrics($user, $viewsTrackingEnabled)
        );
    }

    private function dashboardCacheKey(?User $user, bool $viewsTrackingEnabled): string
    {
        $scope = $user !== null ? 'personal' : 'global';
        $userId = $user?->id ?? 0;
        $role = $user?->role ?? 'none';
        $viewsFlag = $viewsTrackingEnabled ? 1 : 0;

        return sprintf(
            'dashboard:metrics:v%d:scope:%s:user:%d:role:%s:views:%d',
            self::CACHE_KEY_VERSION,
            $scope,
            $userId,
            $role,
            $viewsFlag,
        );
    }

    private function dashboardCacheTtlSeconds(): int
    {
        return max(0, (int) config('dashboard.cache.ttl_seconds', self::DEFAULT_CACHE_TTL_SECONDS));
    }

    public function isViewsTrackingEnabled(): bool
    {
        return Schema::hasTable('blog_post_views');
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

        $totalCommentsOnPublished = DB::table('comments as c')
            ->join('blog_posts as p', 'p.id', '=', 'c.blog_post_id')
            ->where('p.is_published', true)
            ->whereNotNull('p.published_at')
            ->where('p.published_at', '<=', now())
            ->when($user !== null, fn ($query) => $query->where('p.user_id', $user->id))
            ->count();

        $totalLikesOnPublished = DB::table('blog_post_likes as l')
            ->join('blog_posts as p', 'p.id', '=', 'l.blog_post_id')
            ->where('p.is_published', true)
            ->whereNotNull('p.published_at')
            ->where('p.published_at', '<=', now())
            ->when($user !== null, fn ($query) => $query->where('p.user_id', $user->id))
            ->count();

        $avgCommentsPerPublished = $publishedPosts > 0
            ? round($totalCommentsOnPublished / $publishedPosts, 2)
            : 0;

        $avgLikesPerPublished = $publishedPosts > 0
            ? round($totalLikesOnPublished / $publishedPosts, 2)
            : 0;

        $activeAuthors30d = null;

        $topAuthorsByPublishedPosts30d = DB::table('blog_posts as p')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->where('p.is_published', true)
            ->whereNotNull('p.published_at')
            ->where('p.published_at', '<=', now())
            ->where('p.published_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('p.user_id', 'u.name')
            ->selectRaw('p.user_id as id, u.name, COUNT(*) as published_posts_count')
            ->orderByDesc('published_posts_count')
            ->orderBy('u.name')
            ->limit(10)
            ->get();

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
        $userTrends = null;

        if ($user === null) {
            $activeAuthors30d = BlogPost::published()
                ->where('published_at', '>=', now()->subDays(29)->startOfDay())
                ->distinct('user_id')
                ->count('user_id');

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

            $userTrends = [
                'total_users' => $totalUsers,
                'daily_active_users' => User::where('last_active_at', '>=', now()->subDay())->count(),
                'weekly_active_users' => User::where('last_active_at', '>=', now()->subWeek())->count(),
                'monthly_active_users' => User::where('last_active_at', '>=', now()->subDays(30))->count(),
                'total_inactive_users' => User::where(function ($q): void {
                    $q->whereNull('last_active_at')
                        ->orWhere('last_active_at', '<', now()->subDays(30));
                })->count(),
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
            'top_authors_by_published_posts_30d' => $topAuthorsByPublishedPosts30d,
            'follower_growth_30d' => $this->backfill30DayTrend($followerGrowth30d),
            'posts_published_30d' => $this->backfill30DayTrend($postsPublished30d),
            'comments_created_30d' => $this->backfill30DayTrend($commentsCreated30d),
            'likes_created_30d' => $this->backfill30DayTrend($likesCreated30d),
            'two_factor_adoption_rate' => $twoFactorAdoptionRate,
            'invitation_funnel' => $invitationFunnel,
            'user_trends' => $userTrends,
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
