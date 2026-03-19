# Admin Dashboard Metrics Specification

## Purpose
This document defines a practical first version of admin dashboard metrics using:
1. Data already captured in the current database schema
2. Requested view-based metrics that require adding view tracking

It is structured to be implementation-ready for backend and frontend tasks.

---

## 1) Metric Inventory and Data Availability

### A. Requested Metrics

| Metric | Status | Source |
|---|---|---|
| Total blog post views | Not currently capturable | Requires new `blog_post_views` tracking |
| Average views per blog post | Not currently capturable | Requires new `blog_post_views` tracking |
| Total Number of Followers | Capturable now | `user_follows` |
| Comments per blog post | Capturable now | `comments`, `blog_posts` |
| 30-day view graph for posted articles | Not currently capturable | Requires new `blog_post_views` tracking |

### B. High-Value Metrics Already Capturable

| Metric | Source |
|---|---|
| Published posts | `blog_posts` (`is_published`, `published_at`) |
| Draft posts | `blog_posts` (`is_published = false`) |
| Featured posts | `blog_posts` (`is_featured = true`) |
| Total comments on published posts | `comments` + published `blog_posts` |
| Total likes on published posts | `blog_post_likes` + published `blog_posts` |
| Average comments per published post | derived from comments/posts |
| Average likes per published post | derived from likes/posts |
| Active authors (30d) | distinct `blog_posts.user_id` on published posts in last 30 days |
| Follower growth (30d) | `user_follows.created_at` |
| Posts published (30d trend) | `blog_posts.published_at` |
| Comments created (30d trend) | `comments.created_at` |
| Likes created (30d trend) | `blog_post_likes.created_at` |
| 2FA adoption rate | `users.two_factor_confirmed_at` |
| Invitation funnel (pending/accepted/expired) | `users.invitation_*` fields |

---

## 2) Dashboard V1 Layout (Recommended)

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
$totalComments = Comment::whereHas('blogPost', fn ($q) => $q->published())->count();
```

### 3.5 Comments per Blog Post
```php
$commentsPerPost = BlogPost::published()
    ->withCount('comments')
    ->get(['id', 'title', 'slug']);
```

### 3.6 Total Likes on Published Posts
```php
$totalLikes = BlogPostLike::whereHas('blogPost', fn ($q) => $q->published())->count();
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

---

## 4) View Tracking Addition (Required for Requested View Metrics)

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
  "kpis": {
    "published_posts": 123,
    "draft_posts": 14,
    "total_followers": 982,
    "total_comments": 4560,
    "total_likes": 7391,
    "avg_comments_per_post": 37.07,
    "avg_likes_per_post": 60.09,
    "two_factor_rate": 92.4,
    "total_views": null,
    "avg_views_per_post": null
  },
  "trends": {
    "activity_30d": {
      "posts": [{"day": "2026-02-19", "count": 2}],
      "comments": [{"day": "2026-02-19", "count": 14}],
      "likes": [{"day": "2026-02-19", "count": 26}],
      "views": []
    },
    "followers_30d": [{"day": "2026-02-19", "count": 4}]
  },
  "tables": {
    "top_posts_by_comments": [
      {
        "id": 1,
        "title": "...",
        "slug": "...",
        "comments_count": 120,
        "likes_count": 98
      }
    ],
    "top_authors_30d": [
      {"id": 4, "name": "...", "published_posts_count": 8}
    ]
  },
  "meta": {
    "views_tracking_enabled": false,
    "generated_at": "2026-03-19T00:00:00Z"
  }
}
```

---

## 6) Rollout Plan

### Phase 1 (No schema change)
- Implement all currently capturable metrics.
- Update `Dashboard.vue` from placeholder to real cards/charts/tables.
- Keep view metrics visible as "Not available yet".

### Phase 2 (View tracking)
- Add `blog_post_views` migration + model/service logic.
- Track views on public blog post page hit.
- Enable requested view KPIs and 30-day view trend.

### Phase 3 (Hardening)
- Add caching for dashboard aggregates (5–15 min TTL).
- Add background rollups if traffic grows.
- Add tests for metric queries and trend shape consistency.

---

## 7) Suggested Acceptance Criteria

1. Admin dashboard displays all Phase 1 KPIs from live DB data.
2. Trends render 30-day data with missing dates backfilled to `0`.
3. Query time remains acceptable (< 300ms typical with current indexes).
4. View metrics become active only when `views_tracking_enabled = true`.
5. Dashboard endpoint and UI have test coverage for KPI/trend payload shape.
