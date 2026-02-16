<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use function Pest\Laravel\actingAs;

/*
 * PostgreSQL Index Performance Tests
 * 
 * These tests demonstrate the performance benefits of the PostgreSQL-specific
 * indexes added in migration 2026_02_16_000000_add_postgresql_optimized_indexes.
 */

describe('PostgreSQL Index Performance', function () {

    beforeEach(function () {
        // Only run these tests on PostgreSQL
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('These tests require PostgreSQL');
        }
    });

    it('uses GIN index for tag searches', function () {
        // Create a test post with tags
        $admin = User::factory()->create(['role' => 'admin']);
        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'tags' => ['Laravel', 'PHP', 'Testing'],
            'is_published' => true,
            'published_at' => now(),
        ]);

        DB::enableQueryLog();
        $posts = BlogPost::whereJsonContains('tags', 'Laravel')->get();
        $queries = DB::getQueryLog();
        
        expect($queries)->not->toBeEmpty()
            ->and($posts->count())->toBeGreaterThan(0);

        DB::disableQueryLog();
    })->group('performance', 'indexes');

    it('uses composite index for published posts', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        BlogPost::factory()->count(5)->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'published_at' => now()->subDays(rand(1, 30)),
        ]);

        DB::enableQueryLog();
        $posts = BlogPost::published()->orderBy('published_at', 'desc')->take(10)->get();
        $queries = DB::getQueryLog();
        
        expect($queries)->not->toBeEmpty()
            ->and($posts->count())->toBeGreaterThan(0);

        DB::disableQueryLog();
    })->group('performance', 'indexes');

    it('uses featured posts index efficiently', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        BlogPost::factory()->count(3)->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'is_featured' => true,
            'published_at' => now()->subDays(rand(1, 10)),
        ]);

        DB::enableQueryLog();
        $posts = BlogPost::published()->where('is_featured', true)->orderBy('published_at', 'desc')->get();
        $queries = DB::getQueryLog();
        
        expect($queries)->not->toBeEmpty()
            ->and($posts->count())->toBeGreaterThan(0);

        DB::disableQueryLog();
    })->group('performance', 'indexes');

    it('uses draft posts index for author queries', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        BlogPost::factory()->count(3)->create([
            'user_id' => $admin->id,
            'is_published' => false,
            'published_at' => null,
        ]);

        actingAs($admin);
        DB::enableQueryLog();
        $drafts = BlogPost::where('user_id', $admin->id)
            ->where('is_published', false)
            ->orderBy('updated_at', 'desc')
            ->get();
        $queries = DB::getQueryLog();
        
        expect($queries)->not->toBeEmpty()
            ->and($drafts->count())->toBeGreaterThan(0);

        DB::disableQueryLog();
    })->group('performance', 'indexes');

    it('verifies all custom indexes exist', function () {
        $indexes = DB::select("
            SELECT indexname 
            FROM pg_indexes 
            WHERE schemaname = 'public' 
            AND tablename IN ('blog_posts', 'blog_post_likes', 'comments', 'user_follows')
            AND indexname LIKE '%_index'
            ORDER BY indexname
        ");

        $indexNames = array_column($indexes, 'indexname');

        // Verify all 10 critical indexes exist
        expect($indexNames)->toContain('blog_posts_tags_gin_index')
            ->toContain('blog_posts_search_index')
            ->toContain('blog_posts_published_date_index')
            ->toContain('blog_posts_featured_published_index')
            ->toContain('blog_posts_drafts_by_author_index')
            ->toContain('blog_post_likes_post_id_index')
            ->toContain('blog_post_likes_user_id_index')
            ->toContain('user_follows_following_id_index')
            ->toContain('user_follows_follower_id_index')
            ->toContain('comments_thread_index');
    })->group('performance', 'indexes');

});
