# Admin Dashboard Metrics Specification

## Purpose
This document defines a practical first version of admin dashboard metrics using:
1. Data already captured in the current database schema
2. Requested view-based metrics that require adding view tracking

It is structured to be implementation-ready for backend and frontend tasks.

## Last Updated Implementation Status

- Date: 2026-03-21
- Section 1.A requested metrics: Implemented (active from `blog_post_views` when tracking is available).
- Section 1.B high-value metrics: Implemented in dashboard payload and UI.
- Section 2 Dashboard V1 layout: Implemented (KPI row, charts row, and tables row).
- Section 3 query mapping: Implemented in service layer with role-aware scope behavior.
- Section 4 view tracking addition: Implemented (`blog_post_views` schema, public post tracking, and dashboard metric activation).
- Section 5 API contract: Implemented (`metricsByScope` + `meta` with `views_tracking_enabled`, `available_scopes`, `default_scope`, `generated_at`).
- Section 6 hardening: Implemented (TTL cache for scoped dashboard aggregates + scheduled cache warming command).
- 30-day capturable trends (`posts_published_30d`, `comments_created_30d`, `likes_created_30d`, `follower_growth_30d`): Implemented with fixed 30-point zero-filled backfilling.
- Scope behavior: `admin` = personal only; `master_admin` = personal + global.
- Global-only metrics in personal scope: `two_factor_adoption_rate` and `invitation_funnel` remain `null` by design.
- Coverage status: feature, unit, and Vitest dashboard tests are in place and passing.

### Update Checklist

- [x] Section 1.A requested metrics payload is wired and active from tracked data.
- [x] Section 1.B high-value metrics are implemented and rendered.
- [x] Section 2 Dashboard V1 layout is implemented (KPI/Charts/Tables rows).
- [x] Section 3 metric query mapping is implemented in backend services.
- [x] Role scope behavior is implemented (`admin` personal-only, `master_admin` personal + global).
- [x] Capturable 30-day trends are backfilled to fixed 30-day arrays.
- [x] View tracking schema + ingestion (`blog_post_views`) is implemented.
- [x] Requested view KPIs/trends are activated from tracked data.
- [x] Section 5 API contract and metadata (`generated_at`, scope + tracking flags) are implemented.
- [x] Dashboard aggregate caching/rollups hardening is implemented.

---

## 1) Metric Inventory and Data Availability

### A. Requested Metrics

| Metric | Status | Source |
|---|---|---|
| Total blog post views | Implemented | `blog_post_views` |
| Average views per blog post | Implemented | `blog_post_views` + published `blog_posts` |
| Total Number of Followers | Capturable now | `user_follows` |
| Comments per blog post | Capturable now | `comments`, `blog_posts` |
| 30-day view graph for posted articles | Implemented (data payload active) | `blog_post_views` + published `blog_posts` |

### B. High-Value Metrics Already Capturable

| Metric | Status | Source |
|---|---|---|
| Published posts | Implemented | `blog_posts` (`is_published`, `published_at`) |
| Draft posts | Implemented | `blog_posts` (`is_published = false`) |
| Featured posts | Implemented | `blog_posts` (`is_featured = true`) |
| Total comments on published posts | Implemented | `comments` + published `blog_posts` |
| Total likes on published posts | Implemented | `blog_post_likes` + published `blog_posts` |
| Average comments per published post | Implemented | derived from comments/posts |
| Average likes per published post | Implemented | derived from likes/posts |
| Active authors (30d) | Implemented | distinct `blog_posts.user_id` on published posts in last 30 days |
| Follower growth (30d) | Implemented | `user_follows.created_at` |
| Posts published (30d trend) | Implemented + backfilled to 30 days | `blog_posts.published_at` |
| Comments created (30d trend) | Implemented + backfilled to 30 days | `comments.created_at` |
| Likes created (30d trend) | Implemented + backfilled to 30 days | `blog_post_likes.created_at` |
| 2FA adoption rate | Implemented (global scope only) | `users.two_factor_confirmed_at` |
| Invitation funnel (pending/accepted/expired) | Implemented (global scope only) | `users.invitation_*` fields |

### C. Metric Scope by Role (Implemented)

| Role | Available Scopes | Scope Meaning |
|---|---|---|
| `admin` | `personal` only | Metrics computed from blog posts authored by the authenticated admin |
| `master_admin` | `personal`, `global` | `personal` = authored by master admin; `global` = all published posts/users |

Current implementation details:
- Scope selection metadata is returned as `meta.available_scopes` with `meta.default_scope = "personal"`.
- Scoped metric payloads are returned in `metricsByScope.personal` and (for master admins) `metricsByScope.global`.
- High-value metrics are returned under `metricsByScope.{scope}.high_value_metrics`.
- Capturable 30-day trends (`posts_published_30d`, `comments_created_30d`, `likes_created_30d`, `follower_growth_30d`) are backfilled to exactly 30 day/count points with missing days set to `0`.
- In `personal` scope, global user metrics (`two_factor_adoption_rate`, `invitation_funnel`) are intentionally `null` placeholders.

---

## 2) Dashboard V1 Layout (Implemented)

Current implementation notes:
- KPI row is implemented with: Published Posts, Draft Posts, Total Followers, Total Comments, Total Likes, 2FA Adoption Rate.
- Charts row is implemented with: 30-day content activity (posts/comments/likes) and 30-day follower growth.
- Tables row is implemented with: Top 10 posts by comments (with likes) and Top 10 authors by published posts (last 30d).
- Requested view metrics are now activated from tracked `blog_post_views` data.

## KPI Row (Top)
1. Published Posts
2. Draft Posts
3. Total Followers
4. Total Comments
5. Total Likes
6. 2FA Adoption Rate

## Charts Row
1. 30-Day Content Activity (multi-series line):
   - Posts/day
   - Comments/day
   - Likes/day
2. 30-Day Follower Growth (line)

## Tables Row
1. Top 10 Posts by Comments (with likes)
2. Top 10 Authors by Published Posts (last 30d)

## Requested View Metrics Placement (after tracking is added)
1. Add KPI: Total Post Views
2. Add KPI: Avg Views/Post
3. Replace/augment activity chart with 30-day Views chart

---

## 3) Metric Definitions + Eloquent Query Mapping

All examples assume timezone-consistent `now()` usage and “published” means:
- `is_published = true`
- `published_at <= now()`

### 3.1 Published Posts
```php
$publishedPosts = BlogPost::published()->count();
```

### 3.2 Draft Posts
```php
$draftPosts = BlogPost::where('is_published', false)->count();
```

### 3.3 Total Followers
```php
$totalFollowers = DB::table('user_follows')->count();
```

### 3.4 Total Comments on Published Posts
```php
$totalComments = DB::table('comments as c')
  ->join('blog_posts as p', 'p.id', '=', 'c.blog_post_id')
  ->where('p.is_published', true)
  ->whereNotNull('p.published_at')
  ->where('p.published_at', '<=', now())
  ->when($user ?? null, fn ($q) => $q->where('p.user_id', $user->id))
  ->count();
```

### 3.5 Comments per Blog Post
```php
$commentsPerPost = BlogPost::published()
  ->when($user ?? null, fn ($q) => $q->where('user_id', $user->id))
  ->withCount(['comments', 'likes'])
  ->orderByDesc('comments_count')
  ->orderByDesc('likes_count')
  ->orderByDesc('published_at')
  ->limit(10)
    ->get(['id', 'title', 'slug']);
```

### 3.6 Total Likes on Published Posts
```php
$totalLikes = DB::table('blog_post_likes as l')
  ->join('blog_posts as p', 'p.id', '=', 'l.blog_post_id')
  ->where('p.is_published', true)
  ->whereNotNull('p.published_at')
  ->where('p.published_at', '<=', now())
  ->when($user ?? null, fn ($q) => $q->where('p.user_id', $user->id))
  ->count();
```

### 3.7 Average Comments per Published Post
```php
$publishedCount = BlogPost::published()->count();
$avgCommentsPerPost = $publishedCount > 0 ? round($totalComments / $publishedCount, 2) : 0;
```

### 3.8 Average Likes per Published Post
```php
$avgLikesPerPost = $publishedCount > 0 ? round($totalLikes / $publishedCount, 2) : 0;
```

### 3.9 30-Day Posts/Comments/Likes Trend
```php
$start = now()->subDays(29)->startOfDay();

$postsTrend = BlogPost::published()
    ->where('published_at', '>=', $start)
    ->selectRaw('DATE(published_at) as day, COUNT(*) as count')
    ->groupBy('day')
    ->orderBy('day')
    ->get();

$commentsTrend = Comment::where('created_at', '>=', $start)
    ->whereHas('blogPost', fn ($q) => $q->published())
    ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
    ->groupBy('day')
    ->orderBy('day')
    ->get();

$likesTrend = BlogPostLike::where('created_at', '>=', $start)
    ->whereHas('blogPost', fn ($q) => $q->published())
    ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
    ->groupBy('day')
    ->orderBy('day')
    ->get();

// Then backfill each trend to always include the full 30-day window
// [{ day: 'YYYY-MM-DD', count: int }, ... 30 entries total]
```

### 3.10 Active Authors (30d)
```php
$activeAuthors30d = BlogPost::published()
    ->where('published_at', '>=', now()->subDays(30))
    ->distinct('user_id')
    ->count('user_id');
```

### 3.11 Follower Growth (30d)
```php
$followerGrowthTrend = DB::table('user_follows')
    ->where('created_at', '>=', now()->subDays(29)->startOfDay())
    ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
    ->groupBy('day')
    ->orderBy('day')
    ->get();

  // Then backfill to 30 days with zeroes for dates without activity
```

### 3.12 2FA Adoption Rate
```php
$totalUsers = User::count();
$twoFactorUsers = User::whereNotNull('two_factor_confirmed_at')->count();
$twoFactorRate = $totalUsers > 0 ? round(($twoFactorUsers / $totalUsers) * 100, 1) : 0;
```

### 3.13 Invitation Funnel
```php
$pendingInvites = User::whereNotNull('invitation_token')
    ->whereNull('invitation_accepted_at')
    ->where('invitation_sent_at', '>=', now()->subHours(48))
    ->count();

$acceptedInvites = User::whereNotNull('invitation_accepted_at')->count();

$expiredInvites = User::whereNotNull('invitation_token')
    ->whereNull('invitation_accepted_at')
    ->where('invitation_sent_at', '<', now()->subHours(48))
    ->count();
```

### 3.14 Top Authors by Published Posts (last 30d)
```php
$topAuthorsByPublishedPosts30d = DB::table('blog_posts as p')
  ->join('users as u', 'u.id', '=', 'p.user_id')
  ->where('p.is_published', true)
  ->whereNotNull('p.published_at')
  ->where('p.published_at', '<=', now())
  ->where('p.published_at', '>=', now()->subDays(29)->startOfDay())
  ->when($user ?? null, fn ($q) => $q->where('p.user_id', $user->id))
  ->groupBy('p.user_id', 'u.name')
  ->selectRaw('p.user_id as id, u.name, COUNT(*) as published_posts_count')
  ->orderByDesc('published_posts_count')
  ->orderBy('u.name')
  ->limit(10)
  ->get();
```

---

## 4) View Tracking Addition (Implemented)

To support requested metrics, add a `blog_post_views` table.

## Proposed schema
```php
Schema::create('blog_post_views', function (Blueprint $table) {
    $table->id();
    $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('session_id', 128)->nullable();
    $table->string('ip_hash', 64)->nullable();
    $table->string('user_agent_hash', 64)->nullable();
    $table->timestamp('viewed_at')->index();
    $table->timestamps();

    $table->index(['blog_post_id', 'viewed_at']);
    $table->index(['viewed_at']);
});
```

## Deduping recommendation
Count one view per (`blog_post_id`, `session_id`) per rolling 30 minutes to reduce refresh inflation.

## Requested metric queries after this table exists

### Total blog post views
```php
$totalViews = DB::table('blog_post_views')->count();
```

### Average views per published blog post
```php
$publishedCount = BlogPost::published()->count();
$avgViewsPerPost = $publishedCount > 0 ? round($totalViews / $publishedCount, 2) : 0;
```

### 30-day view graph (published posts)
```php
$viewsTrend = DB::table('blog_post_views as v')
    ->join('blog_posts as p', 'p.id', '=', 'v.blog_post_id')
    ->where('p.is_published', true)
    ->where('p.published_at', '<=', now())
    ->where('v.viewed_at', '>=', now()->subDays(29)->startOfDay())
    ->selectRaw('DATE(v.viewed_at) as day, COUNT(*) as count')
    ->groupBy('day')
    ->orderBy('day')
    ->get();
```

---

## 5) API Contract Proposal for `Dashboard.vue`

`GET /admin/dashboard` Inertia props (example):
```json
{
  "metricsByScope": {
    "personal": {
      "total_blog_post_views": 42,
      "average_views_per_blog_post": 7,
      "total_followers": 12,
      "comments_per_blog_post": [
        {"id": 10, "title": "Understanding Laravel Queues", "slug": "understanding-laravel-queues", "comments_count": 8, "likes_count": 3}
      ],
      "views_30d": [{"day": "2026-02-20", "count": 0}, {"day": "2026-03-06", "count": 2}, {"day": "2026-03-21", "count": 5}],
      "high_value_metrics": {
        "published_posts": 6,
        "draft_posts": 2,
        "featured_posts": 1,
        "total_comments_on_published_posts": 24,
        "total_likes_on_published_posts": 40,
        "avg_comments_per_published_post": 4,
        "avg_likes_per_published_post": 6.67,
        "active_authors_30d": 1,
        "top_authors_by_published_posts_30d": [{"id": 10, "name": "Ryan Dandrow", "published_posts_count": 6}],
        "follower_growth_30d": [{"day": "2026-02-20", "count": 0}, {"day": "2026-03-06", "count": 1}, {"day": "2026-03-21", "count": 2}],
        "posts_published_30d": [{"day": "2026-02-20", "count": 0}, {"day": "2026-03-06", "count": 0}, {"day": "2026-03-21", "count": 1}],
        "comments_created_30d": [{"day": "2026-02-20", "count": 0}, {"day": "2026-03-06", "count": 1}, {"day": "2026-03-21", "count": 3}],
        "likes_created_30d": [{"day": "2026-02-20", "count": 0}, {"day": "2026-03-06", "count": 2}, {"day": "2026-03-21", "count": 4}],
        "two_factor_adoption_rate": null,
        "invitation_funnel": {"pending": null, "accepted": null, "expired": null}
      }
    },
    "global": {
      "total_blog_post_views": 5240,
      "average_views_per_blog_post": 43.67,
      "total_followers": 982,
      "comments_per_blog_post": [
        {"id": 1, "title": "Laravel Performance Tuning Guide", "slug": "laravel-performance-tuning-guide", "comments_count": 120, "likes_count": 180}
      ],
      "views_30d": [{"day": "2026-02-20", "count": 0}, {"day": "2026-03-06", "count": 41}, {"day": "2026-03-21", "count": 88}],
      "high_value_metrics": {
        "published_posts": 120,
        "draft_posts": 24,
        "featured_posts": 12,
        "total_comments_on_published_posts": 1860,
        "total_likes_on_published_posts": 2450,
        "avg_comments_per_published_post": 15.5,
        "avg_likes_per_published_post": 20.42,
        "active_authors_30d": 18,
        "top_authors_by_published_posts_30d": [{"id": 1, "name": "Avery Chen", "published_posts_count": 24}],
        "follower_growth_30d": [{"day": "2026-02-20", "count": 0}, {"day": "2026-03-06", "count": 8}, {"day": "2026-03-21", "count": 15}],
        "posts_published_30d": [{"day": "2026-02-20", "count": 0}, {"day": "2026-03-06", "count": 3}, {"day": "2026-03-21", "count": 6}],
        "comments_created_30d": [{"day": "2026-02-20", "count": 0}, {"day": "2026-03-06", "count": 22}, {"day": "2026-03-21", "count": 45}],
        "likes_created_30d": [{"day": "2026-02-20", "count": 0}, {"day": "2026-03-06", "count": 28}, {"day": "2026-03-21", "count": 52}],
        "two_factor_adoption_rate": 76.2,
        "invitation_funnel": {"pending": 4, "accepted": 56, "expired": 3}
      }
    }
  },
  "meta": {
    "views_tracking_enabled": true,
    "available_scopes": ["personal", "global"],
    "default_scope": "personal",
    "generated_at": "2026-03-21T00:00:00Z"
  }
}
```

Dashboard UI currently consumes these `high_value_metrics` fields:
- `published_posts`
- `draft_posts`
- `total_comments_on_published_posts`
- `total_likes_on_published_posts`
- `two_factor_adoption_rate`
- `posts_published_30d`
- `comments_created_30d`
- `likes_created_30d`
- `follower_growth_30d`
- `top_authors_by_published_posts_30d`

Fields currently present but not rendered in the Dashboard UI:
- Top-level: `views_30d`
- `high_value_metrics`: `featured_posts`, `avg_comments_per_published_post`, `avg_likes_per_published_post`, `active_authors_30d`, `invitation_funnel`

---

## 6) Rollout Plan

### Phase 1 (No schema change)
- Status: ✅ Completed
- Implement all currently capturable metrics.
- Update `Dashboard.vue` from placeholder to real cards/charts/tables.
- Keep view metrics visible as "Not available yet".

### Phase 2 (View tracking)
- Status: ✅ Completed
- Add `blog_post_views` migration + model/service logic.
- Track views on public blog post page hit.
- Enable requested view KPIs and 30-day view trend.

### Phase 3 (Hardening)
- Status: ✅ Completed
- Add caching for dashboard aggregates (5–15 min TTL).
- Add background cache warming for admin scope payloads (scheduled command).
- Add tests for metric queries and trend shape consistency.
- Edge-case coverage is in place for TTL bypass, cache-key separation on tracking availability changes, no-admin warm runs, and targeted master-admin warming.

---

## 7) Test Coverage Plan (Backend + Vitest)

### Backend (Feature tests)
- ✅ `tests/Feature/Admin/DashboardTest.php`
  - guest redirect
  - authenticated access
  - admin receives personal scope only
  - master admin receives personal + global scopes
  - end-to-end `meta.generated_at` validity assertions for both roles
- ✅ `tests/Feature/WarmDashboardMetricsCacheCommandTest.php`
  - reports disabled-cache mode
  - warms admin + master admin scope payloads
  - supports targeted warming via `--user-id`
  - reports no-admin-users mode
  - supports targeted warming for master admin (both scopes)
- ✅ `tests/Feature/DashboardScheduleTest.php`
  - verifies scheduler registration for `dashboard:warm-metrics-cache`

### Backend (Unit tests)
- ✅ `tests/Unit/Services/DashboardMetricsServiceTest.php`
  - role/scope resolution
  - scoped aggregation for comments/followers/high-value metrics
  - 30-day trend backfilling to fixed 30-point arrays with zero-fill
  - scoped metrics cache behavior (stable until cache clear/expiry)
  - cache bypass when `dashboard.cache.ttl_seconds = 0`
  - cache-key separation when views tracking availability changes
- ✅ `tests/Unit/Http/Controllers/Admin/DashboardControllerTest.php`
  - Inertia payload contract for admin
  - Inertia payload contract for master admin

### Frontend (Vitest)
- ✅ `tests/frontend/pages/Dashboard.test.ts`
  - renders requested metric cards
  - hides scope switch for personal-only payload
  - shows scope switch when both scopes are available
  - toggles personal/global and updates rendered values
  - renders comments table rows for active scope
  - renders trend activity messaging correctly with zero-filled 30-day arrays
  - renders enabled view-tracking message and numeric requested view metrics when available

---

## 8) Suggested Acceptance Criteria

1. Admin dashboard displays all Phase 1 KPIs from live DB data.
2. Trends render 30-day data with missing dates backfilled to `0`.
3. Query time remains acceptable (< 300ms typical with current indexes).
4. View metrics become active only when `views_tracking_enabled = true`.
5. Dashboard endpoint and UI have test coverage for KPI/trend payload shape.
