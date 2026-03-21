<?php

/**
 * Dashboard Test Suite
 *
 * Tests dashboard access control and functionality for authenticated users.
 */

use App\Models\User;
use App\Models\BlogPost;
use App\Models\Comment;

describe('Dashboard Access', function () {
    it('redirects unauthenticated users to login page', function () {
        $response = $this->get(route('dashboard'));
        
        expect($response)->toRedirectToLogin();
    })->group('dashboard', 'guest');

    it('allows authenticated users to visit dashboard', function () {
        $user = User::factory()->admin()->withTwoFactor()->create();

        $response = authenticatedGet($user, route('dashboard'));

        $response->assertStatus(200);
    })->group('dashboard', 'authenticated');

    it('returns personal-only scoped metrics for admin users', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $author = User::factory()->create();

        $adminPost = BlogPost::factory()->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $otherPost = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        Comment::factory()->count(2)->create([
            'blog_post_id' => $adminPost->id,
            'user_id' => $author->id,
        ]);

        Comment::factory()->count(4)->create([
            'blog_post_id' => $otherPost->id,
            'user_id' => $author->id,
        ]);

        $admin->followers()->attach($author->id);

        $response = authenticatedGet($admin, route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('metricsByScope.personal')
            ->missing('metricsByScope.global')
            ->where('metricsByScope.personal.total_blog_post_views', null)
            ->where('metricsByScope.personal.average_views_per_blog_post', null)
            ->where('metricsByScope.personal.total_followers', 1)
            ->has('metricsByScope.personal.comments_per_blog_post', 1)
            ->where('metricsByScope.personal.comments_per_blog_post.0.id', $adminPost->id)
            ->where('metricsByScope.personal.comments_per_blog_post.0.likes_count', 0)
            ->has('metricsByScope.personal.high_value_metrics.top_authors_by_published_posts_30d', 1)
            ->where('metricsByScope.personal.high_value_metrics.top_authors_by_published_posts_30d.0.id', $admin->id)
            ->has('metricsByScope.personal.high_value_metrics.posts_published_30d', 30)
            ->has('metricsByScope.personal.high_value_metrics.comments_created_30d', 30)
            ->has('metricsByScope.personal.high_value_metrics.likes_created_30d', 30)
            ->has('metricsByScope.personal.high_value_metrics.follower_growth_30d', 30)
            ->where('meta.views_tracking_enabled', false)
            ->where('meta.available_scopes', ['personal'])
            ->where('meta.default_scope', 'personal'));
    })->group('dashboard', 'authenticated');

    it('returns both personal and global scoped metrics for master admins', function () {
        $masterAdmin = User::factory()->masterAdmin()->withTwoFactor()->create();
        $author = User::factory()->create();

        $masterPost = BlogPost::factory()->create([
            'user_id' => $masterAdmin->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $authorPost = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        Comment::factory()->count(1)->create([
            'blog_post_id' => $masterPost->id,
            'user_id' => $author->id,
        ]);

        Comment::factory()->count(3)->create([
            'blog_post_id' => $authorPost->id,
            'user_id' => $author->id,
        ]);

        $masterAdmin->followers()->attach($author->id);
        $author->followers()->attach($masterAdmin->id);

        $response = authenticatedGet($masterAdmin, route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('metricsByScope.personal')
            ->has('metricsByScope.global')
            ->has('metricsByScope.personal.comments_per_blog_post', 1)
            ->where('metricsByScope.personal.comments_per_blog_post.0.id', $masterPost->id)
            ->where('metricsByScope.personal.comments_per_blog_post.0.likes_count', 0)
            ->where('metricsByScope.personal.total_followers', 1)
            ->has('metricsByScope.personal.high_value_metrics.top_authors_by_published_posts_30d', 1)
            ->where('metricsByScope.personal.high_value_metrics.top_authors_by_published_posts_30d.0.id', $masterAdmin->id)
            ->has('metricsByScope.personal.high_value_metrics.posts_published_30d', 30)
            ->has('metricsByScope.personal.high_value_metrics.comments_created_30d', 30)
            ->has('metricsByScope.personal.high_value_metrics.likes_created_30d', 30)
            ->has('metricsByScope.personal.high_value_metrics.follower_growth_30d', 30)
            ->has('metricsByScope.global.comments_per_blog_post', 2)
            ->where('metricsByScope.global.total_followers', 2)
            ->has('metricsByScope.global.high_value_metrics.top_authors_by_published_posts_30d', 2)
            ->has('metricsByScope.global.high_value_metrics.posts_published_30d', 30)
            ->has('metricsByScope.global.high_value_metrics.comments_created_30d', 30)
            ->has('metricsByScope.global.high_value_metrics.likes_created_30d', 30)
            ->has('metricsByScope.global.high_value_metrics.follower_growth_30d', 30)
            ->where('meta.available_scopes', ['personal', 'global'])
            ->where('meta.default_scope', 'personal'));
    })->group('dashboard', 'authenticated');
});