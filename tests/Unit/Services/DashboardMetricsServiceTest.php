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
            ->and($metrics['comments_per_blog_post']->first()->comments_count)->toBe(2);
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
            ->and($metrics['comments_per_blog_post']->first()->comments_count)->toBe(4);
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
});
