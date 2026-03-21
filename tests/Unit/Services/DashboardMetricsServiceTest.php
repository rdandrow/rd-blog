<?php

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
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
            ->and($high['top_authors_by_published_posts_30d'])->toHaveCount(1)
            ->and($high['top_authors_by_published_posts_30d'][0]->id)->toBe($admin->id);
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
});
