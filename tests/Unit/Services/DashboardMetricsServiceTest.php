<?php

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    config(['dashboard.cache.enabled' => false]);
    $this->service = new DashboardMetricsService();
});

describe('DashboardMetricsService', function () {
    test('admin receives personal scope only', function () {
        $admin = User::factory()->admin()->create();

        $result = $this->service->getScopedMetricsForUser($admin);

        expect($result['metricsByScope'])->toHaveKeys(['personal'])
            ->and($result['availableScopes'])->toBe(['personal'])
            ->and($result['defaultScope'])->toBe('personal');
    });

    test('master admin receives personal and global scopes', function () {
        $masterAdmin = User::factory()->masterAdmin()->create();

        $result = $this->service->getScopedMetricsForUser($masterAdmin);

        expect($result['metricsByScope'])->toHaveKeys(['personal', 'global'])
            ->and($result['availableScopes'])->toBe(['personal', 'global'])
            ->and($result['defaultScope'])->toBe('personal');
    });

    test('scoped metrics cache is invalidated when blog post data changes', function () {
        config([
            'dashboard.cache.enabled' => true,
            'dashboard.cache.ttl_seconds' => 600,
        ]);

        $admin = User::factory()->admin()->create();

        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $first = $this->service->getScopedMetricsForUser($admin);

        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $second = $this->service->getScopedMetricsForUser($admin);

        expect($first['metricsByScope']['personal']['high_value_metrics']['published_posts'])->toBe(1)
            ->and($second['metricsByScope']['personal']['high_value_metrics']['published_posts'])->toBe(2);
    });

    test('scoped metrics caching is bypassed when ttl is zero', function () {
        config([
            'dashboard.cache.enabled' => true,
            'dashboard.cache.ttl_seconds' => 0,
        ]);

        $admin = User::factory()->admin()->create();

        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $first = $this->service->getScopedMetricsForUser($admin);

        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $second = $this->service->getScopedMetricsForUser($admin);

        expect($first['metricsByScope']['personal']['high_value_metrics']['published_posts'])->toBe(1)
            ->and($second['metricsByScope']['personal']['high_value_metrics']['published_posts'])->toBe(2);
    });

    test('cache keys separate payloads when views tracking availability changes', function () {
        config([
            'dashboard.cache.enabled' => true,
            'dashboard.cache.ttl_seconds' => 600,
        ]);

        $admin = User::factory()->admin()->create();

        $post = BlogPost::factory()->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        DB::table('blog_post_views')->insert([
            'blog_post_id' => $post->id,
            'user_id' => null,
            'session_id' => 'cache-views-1',
            'ip_hash' => 'hash-a',
            'user_agent_hash' => 'ua-a',
            'viewed_at' => now()->subHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $withTracking = $this->service->getScopedMetricsForUser($admin);

        expect($withTracking['viewsTrackingEnabled'])->toBeTrue()
            ->and($withTracking['metricsByScope']['personal']['total_blog_post_views'])->toBe(1)
            ->and($withTracking['metricsByScope']['personal']['total_anonymous_blog_post_views'])->toBe(1)
            ->and($withTracking['metricsByScope']['personal']['anonymous_view_share_percentage'])->toBe(100.0)
            ->and(collect($withTracking['metricsByScope']['personal']['views_30d'])->sum('count'))->toBe(1);

        Schema::dropIfExists('blog_post_views');

        // Fresh instance required: the memoized table-check is correct for a single request
        // lifetime, but this test simulates the table being dropped between requests.
        $withoutTracking = app(DashboardMetricsService::class)->getScopedMetricsForUser($admin);

        expect($withoutTracking['viewsTrackingEnabled'])->toBeFalse()
            ->and($withoutTracking['metricsByScope']['personal']['total_blog_post_views'])->toBeNull()
            ->and($withoutTracking['metricsByScope']['personal']['total_anonymous_blog_post_views'])->toBeNull()
            ->and($withoutTracking['metricsByScope']['personal']['average_views_per_blog_post'])->toBeNull()
            ->and($withoutTracking['metricsByScope']['personal']['anonymous_view_share_percentage'])->toBeNull()
            ->and($withoutTracking['metricsByScope']['personal']['views_30d'])->toBe([]);
    });

    test('personal metrics include only authored published posts in comments table', function () {
        $admin = User::factory()->admin()->create();
        $otherAuthor = User::factory()->create();
        $commenter = User::factory()->create();

        $adminsPost = BlogPost::factory()->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $othersPost = BlogPost::factory()->create([
            'user_id' => $otherAuthor->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        Comment::factory()->count(2)->create([
            'blog_post_id' => $adminsPost->id,
            'user_id' => $commenter->id,
        ]);

        Comment::factory()->count(3)->create([
            'blog_post_id' => $othersPost->id,
            'user_id' => $commenter->id,
        ]);

        $metrics = $this->service->getRequestedMetrics($admin);

        expect($metrics['comments_per_blog_post'])->toHaveCount(1)
            ->and($metrics['comments_per_blog_post']->first()->id)->toBe($adminsPost->id)
            ->and($metrics['comments_per_blog_post']->first()->comments_count)->toBe(2)
            ->and($metrics['comments_per_blog_post']->first()->likes_count)->toBe(0);
    });

    test('global metrics include all published posts in comments table', function () {
        $authorOne = User::factory()->create();
        $authorTwo = User::factory()->create();
        $commenter = User::factory()->create();

        $postOne = BlogPost::factory()->create([
            'user_id' => $authorOne->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $postTwo = BlogPost::factory()->create([
            'user_id' => $authorTwo->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        Comment::factory()->count(1)->create([
            'blog_post_id' => $postOne->id,
            'user_id' => $commenter->id,
        ]);

        Comment::factory()->count(4)->create([
            'blog_post_id' => $postTwo->id,
            'user_id' => $commenter->id,
        ]);

        $metrics = $this->service->getRequestedMetrics();

        expect($metrics['comments_per_blog_post'])->toHaveCount(2)
            ->and($metrics['comments_per_blog_post']->first()->id)->toBe($postTwo->id)
            ->and($metrics['comments_per_blog_post']->first()->comments_count)->toBe(4)
            ->and($metrics['comments_per_blog_post']->first()->likes_count)->toBe(0);
    });

    test('follower count is scoped correctly for personal and global', function () {
        $admin = User::factory()->admin()->create();
        $followerOne = User::factory()->create();
        $followerTwo = User::factory()->create();

        $admin->followers()->attach($followerOne->id);
        $admin->followers()->attach($followerTwo->id);

        $otherUser = User::factory()->create();
        $otherFollower = User::factory()->create();
        $otherUser->followers()->attach($otherFollower->id);

        $personal = $this->service->getRequestedMetrics($admin);
        $global = $this->service->getRequestedMetrics();

        expect($personal['total_followers'])->toBe(2)
            ->and($global['total_followers'])->toBe(3);
    });

    test('global high-value metrics include user-wide metrics and published post aggregates', function () {
        $author = User::factory()->create();
        $commenter = User::factory()->create();

        User::factory()->create(['two_factor_confirmed_at' => now()]);
        User::factory()->create(['two_factor_confirmed_at' => null]);

        $published = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'is_featured' => true,
            'published_at' => now()->subDay(),
        ]);

        BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => false,
            'published_at' => null,
        ]);

        Comment::factory()->count(2)->create([
            'blog_post_id' => $published->id,
            'user_id' => $commenter->id,
        ]);

        DB::table('blog_post_likes')->insert([
            ['blog_post_id' => $published->id, 'user_id' => $author->id, 'created_at' => now(), 'updated_at' => now()],
            ['blog_post_id' => $published->id, 'user_id' => $commenter->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $metrics = $this->service->getRequestedMetrics();
        $high = $metrics['high_value_metrics'];

        expect($high['published_posts'])->toBe(1)
            ->and($high['draft_posts'])->toBe(1)
            ->and($high['featured_posts'])->toBe(1)
            ->and($high['total_comments_on_published_posts'])->toBe(2)
            ->and($high['total_likes_on_published_posts'])->toBe(2)
            ->and($high['avg_comments_per_published_post'])->toBe(2.0)
            ->and($high['avg_likes_per_published_post'])->toBe(2.0)
            ->and($high['two_factor_adoption_rate'])->not->toBeNull()
            ->and($high['invitation_funnel']['pending'])->not->toBeNull()
            ->and($high['posts_published_30d'])->toHaveCount(30)
            ->and($high['comments_created_30d'])->toHaveCount(30)
            ->and($high['likes_created_30d'])->toHaveCount(30)
            ->and($high['follower_growth_30d'])->toHaveCount(30)
            ->and($high['top_authors_by_published_posts_30d'])->toHaveCount(1)
            ->and($high['top_authors_by_published_posts_30d'][0]->id)->toBe($author->id)
            ->and(collect($high['posts_published_30d'])->where('count', '>', 0)->count())->toBe(1)
            ->and(collect($high['comments_created_30d'])->where('count', '>', 0)->count())->toBe(1)
            ->and(collect($high['likes_created_30d'])->where('count', '>', 0)->count())->toBe(1);
    });

    test('personal high-value metrics are scoped to authored posts and hide global-only user metrics', function () {
        $admin = User::factory()->admin()->create();
        $otherAuthor = User::factory()->create();
        $commenter = User::factory()->create();

        $adminPost = BlogPost::factory()->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $otherPost = BlogPost::factory()->create([
            'user_id' => $otherAuthor->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        Comment::factory()->count(1)->create([
            'blog_post_id' => $adminPost->id,
            'user_id' => $commenter->id,
        ]);

        Comment::factory()->count(4)->create([
            'blog_post_id' => $otherPost->id,
            'user_id' => $commenter->id,
        ]);

        DB::table('blog_post_likes')->insert([
            ['blog_post_id' => $adminPost->id, 'user_id' => $admin->id, 'created_at' => now(), 'updated_at' => now()],
            ['blog_post_id' => $otherPost->id, 'user_id' => $otherAuthor->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $metrics = $this->service->getRequestedMetrics($admin);
        $high = $metrics['high_value_metrics'];

        expect($high['published_posts'])->toBe(1)
            ->and($high['total_comments_on_published_posts'])->toBe(1)
            ->and($high['total_likes_on_published_posts'])->toBe(1)
            ->and($high['two_factor_adoption_rate'])->toBeNull()
            ->and($high['invitation_funnel']['pending'])->toBeNull()
            ->and($high['posts_published_30d'])->toHaveCount(30)
            ->and($high['comments_created_30d'])->toHaveCount(30)
            ->and($high['likes_created_30d'])->toHaveCount(30)
            ->and($high['follower_growth_30d'])->toHaveCount(30)
            ->and($high['top_authors_by_published_posts_30d'])->toHaveCount(2)
            ->and(collect($high['top_authors_by_published_posts_30d'])->pluck('id')->toArray())
                ->toContain($admin->id)
                ->toContain($otherAuthor->id);
    });

    test('backfills missing trend dates with zero counts', function () {
        $metrics = $this->service->getRequestedMetrics();
        $high = $metrics['high_value_metrics'];

        expect($high['posts_published_30d'])->toHaveCount(30)
            ->and($high['comments_created_30d'])->toHaveCount(30)
            ->and($high['likes_created_30d'])->toHaveCount(30)
            ->and($high['follower_growth_30d'])->toHaveCount(30)
            ->and(collect($high['posts_published_30d'])->sum('count'))->toBe(0)
            ->and(collect($high['comments_created_30d'])->sum('count'))->toBe(0)
            ->and(collect($high['likes_created_30d'])->sum('count'))->toBe(0)
            ->and(collect($high['follower_growth_30d'])->sum('count'))->toBe(0);
    });

    test('totals for comments and likes exclude unpublished and future-published posts', function () {
        $author = User::factory()->create();
        $commenter = User::factory()->create();

        $publishedPost = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $unpublishedPost = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => false,
            'published_at' => null,
        ]);

        $futurePublishedPost = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);

        Comment::factory()->count(2)->create([
            'blog_post_id' => $publishedPost->id,
            'user_id' => $commenter->id,
        ]);
        Comment::factory()->count(3)->create([
            'blog_post_id' => $unpublishedPost->id,
            'user_id' => $commenter->id,
        ]);
        Comment::factory()->count(4)->create([
            'blog_post_id' => $futurePublishedPost->id,
            'user_id' => $commenter->id,
        ]);

        DB::table('blog_post_likes')->insert([
            ['blog_post_id' => $publishedPost->id, 'user_id' => $author->id, 'created_at' => now(), 'updated_at' => now()],
            ['blog_post_id' => $publishedPost->id, 'user_id' => $commenter->id, 'created_at' => now(), 'updated_at' => now()],
            ['blog_post_id' => $unpublishedPost->id, 'user_id' => $author->id, 'created_at' => now(), 'updated_at' => now()],
            ['blog_post_id' => $unpublishedPost->id, 'user_id' => $commenter->id, 'created_at' => now(), 'updated_at' => now()],
            ['blog_post_id' => $futurePublishedPost->id, 'user_id' => $author->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $high = $this->service->getRequestedMetrics()['high_value_metrics'];

        expect($high['total_comments_on_published_posts'])->toBe(2)
            ->and($high['total_likes_on_published_posts'])->toBe(2)
            ->and($high['published_posts'])->toBe(1);
    });

    test('comments per post ordering uses comments then likes then published_at and is limited to 10', function () {
        $author = User::factory()->create();
        $commenter = User::factory()->create();

        $oldest = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDays(3),
            'title' => 'Oldest Tie',
        ]);

        $middle = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDays(2),
            'title' => 'Middle Tie',
        ]);

        $newest = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
            'title' => 'Newest Tie',
        ]);

        foreach ([$oldest, $middle, $newest] as $post) {
            Comment::factory()->count(5)->create([
                'blog_post_id' => $post->id,
                'user_id' => $commenter->id,
            ]);
        }

        DB::table('blog_post_likes')->insert([
            ['blog_post_id' => $middle->id, 'user_id' => $author->id, 'created_at' => now(), 'updated_at' => now()],
            ['blog_post_id' => $middle->id, 'user_id' => $commenter->id, 'created_at' => now(), 'updated_at' => now()],
            ['blog_post_id' => $newest->id, 'user_id' => $author->id, 'created_at' => now(), 'updated_at' => now()],
            ['blog_post_id' => $newest->id, 'user_id' => $commenter->id, 'created_at' => now(), 'updated_at' => now()],
            ['blog_post_id' => $oldest->id, 'user_id' => $author->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        BlogPost::factory()->count(9)->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDays(4),
        ]);

        $topPosts = $this->service->getRequestedMetrics()['comments_per_blog_post'];

        expect($topPosts)->toHaveCount(10)
            ->and($topPosts[0]->id)->toBe($newest->id)
            ->and($topPosts[1]->id)->toBe($middle->id)
            ->and($topPosts[2]->id)->toBe($oldest->id);
    });

    test('top authors list is sorted by published count then name and limited to 10', function () {
        $alpha = User::factory()->create(['name' => 'Alpha']);
        $beta = User::factory()->create(['name' => 'Beta']);

        BlogPost::factory()->count(2)->create([
            'user_id' => $alpha->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        BlogPost::factory()->count(2)->create([
            'user_id' => $beta->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        for ($index = 1; $index <= 10; $index++) {
            $user = User::factory()->create(['name' => sprintf('Author %02d', $index)]);

            BlogPost::factory()->create([
                'user_id' => $user->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);
        }

        $topAuthors = $this->service->getRequestedMetrics()['high_value_metrics']['top_authors_by_published_posts_30d'];

        expect($topAuthors)->toHaveCount(10)
            ->and($topAuthors[0]->name)->toBe('Alpha')
            ->and($topAuthors[0]->published_posts_count)->toBe(2)
            ->and($topAuthors[1]->name)->toBe('Beta')
            ->and($topAuthors[1]->published_posts_count)->toBe(2);
    });

    test('scope parity applies to totals and 30-day trends for personal vs global', function () {
        $admin = User::factory()->admin()->create();
        $otherAuthor = User::factory()->create();
        $commenter = User::factory()->create();

        $adminPost = BlogPost::factory()->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'published_at' => now()->subDays(2),
        ]);

        $otherPost = BlogPost::factory()->create([
            'user_id' => $otherAuthor->id,
            'is_published' => true,
            'published_at' => now()->subDays(2),
        ]);

        Comment::factory()->count(2)->create([
            'blog_post_id' => $adminPost->id,
            'user_id' => $commenter->id,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        Comment::factory()->count(1)->create([
            'blog_post_id' => $otherPost->id,
            'user_id' => $commenter->id,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        DB::table('blog_post_likes')->insert([
            ['blog_post_id' => $adminPost->id, 'user_id' => $admin->id, 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()],
            ['blog_post_id' => $adminPost->id, 'user_id' => $commenter->id, 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()],
            ['blog_post_id' => $otherPost->id, 'user_id' => $otherAuthor->id, 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()],
        ]);

        $followerOne = User::factory()->create();
        $followerTwo = User::factory()->create();
        $followerThree = User::factory()->create();

        $admin->followers()->attach($followerOne->id, [
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        $admin->followers()->attach($followerTwo->id, [
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        $otherAuthor->followers()->attach($followerThree->id, [
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $personalHigh = $this->service->getRequestedMetrics($admin)['high_value_metrics'];
        $globalHigh = $this->service->getRequestedMetrics()['high_value_metrics'];

        expect($personalHigh['total_comments_on_published_posts'])->toBe(2)
            ->and($globalHigh['total_comments_on_published_posts'])->toBe(3)
            ->and($personalHigh['total_likes_on_published_posts'])->toBe(2)
            ->and($globalHigh['total_likes_on_published_posts'])->toBe(3)
            ->and(collect($personalHigh['posts_published_30d'])->sum('count'))->toBe(1)
            ->and(collect($globalHigh['posts_published_30d'])->sum('count'))->toBe(2)
            ->and(collect($personalHigh['comments_created_30d'])->sum('count'))->toBe(2)
            ->and(collect($globalHigh['comments_created_30d'])->sum('count'))->toBe(3)
            ->and(collect($personalHigh['likes_created_30d'])->sum('count'))->toBe(2)
            ->and(collect($globalHigh['likes_created_30d'])->sum('count'))->toBe(3)
            ->and(collect($personalHigh['follower_growth_30d'])->sum('count'))->toBe(2)
            ->and(collect($globalHigh['follower_growth_30d'])->sum('count'))->toBe(3);
    });

    test('requested view metrics are calculated for global scope when tracking exists', function () {
        $authorOne = User::factory()->create();
        $authorTwo = User::factory()->create();

        $postOne = BlogPost::factory()->create([
            'user_id' => $authorOne->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $postTwo = BlogPost::factory()->create([
            'user_id' => $authorTwo->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        DB::table('blog_post_views')->insert([
            [
                'blog_post_id' => $postOne->id,
                'user_id' => null,
                'session_id' => 's1',
                'ip_hash' => 'h1',
                'user_agent_hash' => 'u1',
                'viewed_at' => now()->subDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'blog_post_id' => $postTwo->id,
                'user_id' => null,
                'session_id' => 's2',
                'ip_hash' => 'h2',
                'user_agent_hash' => 'u2',
                'viewed_at' => now()->subDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'blog_post_id' => $postTwo->id,
                'user_id' => null,
                'session_id' => 's3',
                'ip_hash' => 'h3',
                'user_agent_hash' => 'u3',
                'viewed_at' => now()->subDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $metrics = $this->service->getRequestedMetrics();

        expect($metrics['total_blog_post_views'])->toBe(3)
            ->and($metrics['total_anonymous_blog_post_views'])->toBe(3)
            ->and($metrics['average_views_per_blog_post'])->toBe(1.5)
            ->and($metrics['anonymous_view_share_percentage'])->toBe(100.0)
            ->and($metrics['views_30d'])->toHaveCount(30)
            ->and($metrics['anonymous_views_30d'])->toHaveCount(30)
            ->and(collect($metrics['views_30d'])->sum('count'))->toBe(3)
            ->and(collect($metrics['anonymous_views_30d'])->sum('count'))->toBe(3);
    });

    test('requested view metrics are scoped by authored posts for personal scope', function () {
        $admin = User::factory()->admin()->create();
        $otherAuthor = User::factory()->create();

        $adminPost = BlogPost::factory()->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $otherPost = BlogPost::factory()->create([
            'user_id' => $otherAuthor->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        DB::table('blog_post_views')->insert([
            [
                'blog_post_id' => $adminPost->id,
                'user_id' => null,
                'session_id' => 'a1',
                'ip_hash' => 'ha1',
                'user_agent_hash' => 'ua1',
                'viewed_at' => now()->subDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'blog_post_id' => $adminPost->id,
                'user_id' => null,
                'session_id' => 'a2',
                'ip_hash' => 'ha2',
                'user_agent_hash' => 'ua2',
                'viewed_at' => now()->subDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'blog_post_id' => $otherPost->id,
                'user_id' => null,
                'session_id' => 'o1',
                'ip_hash' => 'ho1',
                'user_agent_hash' => 'uo1',
                'viewed_at' => now()->subDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $metrics = $this->service->getRequestedMetrics($admin);

        expect($metrics['total_blog_post_views'])->toBe(2)
            ->and($metrics['total_anonymous_blog_post_views'])->toBe(2)
            ->and($metrics['average_views_per_blog_post'])->toBe(2.0)
            ->and($metrics['anonymous_view_share_percentage'])->toBe(100.0)
            ->and($metrics['views_30d'])->toHaveCount(30)
            ->and($metrics['anonymous_views_30d'])->toHaveCount(30)
            ->and(collect($metrics['views_30d'])->sum('count'))->toBe(2)
            ->and(collect($metrics['anonymous_views_30d'])->sum('count'))->toBe(2);
    });

    test('requested view metrics are disabled when views table is missing', function () {
        Schema::dropIfExists('blog_post_views');

        $admin = User::factory()->admin()->create();
        $scopePayload = $this->service->getScopedMetricsForUser($admin);
        $metrics = $this->service->getRequestedMetrics($admin);

        expect($scopePayload['viewsTrackingEnabled'])->toBeFalse()
            ->and($metrics['total_blog_post_views'])->toBeNull()
            ->and($metrics['total_anonymous_blog_post_views'])->toBeNull()
            ->and($metrics['average_views_per_blog_post'])->toBeNull()
            ->and($metrics['anonymous_view_share_percentage'])->toBeNull()
            ->and($metrics['views_30d'])->toBe([])
            ->and($metrics['anonymous_views_30d'])->toBe([]);
    });

    test('views 30 day trend is ordered, fixed-length, and excludes unpublished or future posts', function () {
        $author = User::factory()->create();

        $published = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDays(2),
        ]);

        $draft = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => false,
            'published_at' => null,
        ]);

        $future = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);

        DB::table('blog_post_views')->insert([
            [
                'blog_post_id' => $published->id,
                'user_id' => null,
                'session_id' => 'v1',
                'ip_hash' => 'hv1',
                'user_agent_hash' => 'uv1',
                'viewed_at' => now()->subDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'blog_post_id' => $draft->id,
                'user_id' => null,
                'session_id' => 'v2',
                'ip_hash' => 'hv2',
                'user_agent_hash' => 'uv2',
                'viewed_at' => now()->subDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'blog_post_id' => $future->id,
                'user_id' => null,
                'session_id' => 'v3',
                'ip_hash' => 'hv3',
                'user_agent_hash' => 'uv3',
                'viewed_at' => now()->subDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $viewsTrend = $this->service->getRequestedMetrics()['views_30d'];

        expect($viewsTrend)->toHaveCount(30)
            ->and($viewsTrend[0]['day'])->toBe(now()->subDays(29)->toDateString())
            ->and($viewsTrend[29]['day'])->toBe(now()->toDateString())
            ->and(collect($viewsTrend)->sum('count'))->toBe(1)
            ->and(collect($viewsTrend)->pluck('day')->values()->all())->toBe(
                collect(range(0, 29))
                    ->map(fn (int $offset) => now()->subDays(29 - $offset)->toDateString())
                    ->values()
                    ->all()
            );
    });
});
