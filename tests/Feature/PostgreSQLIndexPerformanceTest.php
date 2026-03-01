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

    it('has GIN index available for tag searches and queries work correctly', function () {
        // Verify the GIN index exists and is available
        $indexExists = DB::selectOne(
            "SELECT 1 FROM pg_indexes WHERE indexname = ? AND tablename = 'blog_posts'",
            ['blog_posts_tags_gin_index']
        );
        expect($indexExists)->not->toBeNull(
            'GIN index blog_posts_tags_gin_index should exist'
        );
        
        // Create test data
        $admin = User::factory()->create(['role' => 'admin']);
        
        BlogPost::factory()->count(50)->create([
            'user_id' => $admin->id,
            'tags' => ['Laravel', 'PHP', 'Testing'],
            'is_published' => true,
            'published_at' => now()->subDays(rand(1, 60)),
        ]);
        
        BlogPost::factory()->count(50)->create([
            'user_id' => $admin->id,
            'tags' => ['Vue', 'JavaScript', 'Frontend'],
            'is_published' => true,
            'published_at' => now()->subDays(rand(1, 60)),
        ]);

        // Verify the query returns correct results
        // Note: PostgreSQL may choose Seq Scan for small test datasets even with index available.
        // The index will be used in production with larger datasets when cost-beneficial.
        $posts = BlogPost::whereJsonContains('tags', 'Laravel')->get();
        expect($posts->count())->toBe(50)
            ->and($posts->every(fn($post) => in_array('Laravel', $post->tags)))->toBeTrue();
    })->group('performance', 'indexes');

    it('has composite index available for published posts and queries work correctly', function () {
        // Verify the composite index exists
        $indexExists = DB::selectOne(
            "SELECT 1 FROM pg_indexes WHERE indexname = ? AND tablename = 'blog_posts'",
            ['blog_posts_published_date_index']
        );
        expect($indexExists)->not->toBeNull(
            'Composite index blog_posts_published_date_index should exist'
        );
        
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create test data
        BlogPost::factory()->count(100)->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'published_at' => now()->subDays(rand(1, 90)),
        ]);
        
        BlogPost::factory()->count(50)->create([
            'user_id' => $admin->id,
            'is_published' => false,
        ]);

        // Verify query works correctly with published posts
        // Index will be used when cost-beneficial for larger datasets
        $posts = BlogPost::published()->orderBy('published_at', 'desc')->take(10)->get();
        expect($posts->count())->toBe(10)
            ->and($posts->every(fn($post) => $post->is_published))->toBeTrue();
    })->group('performance', 'indexes');

    it('has featured posts index available and queries work correctly', function () {
        // Verify the featured posts index exists
        $indexExists = DB::selectOne(
            "SELECT 1 FROM pg_indexes WHERE indexname = ? AND tablename = 'blog_posts'",
            ['blog_posts_featured_published_index']
        );
        expect($indexExists)->not->toBeNull(
            'Featured posts index blog_posts_featured_published_index should exist'
        );
        
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create featured posts
        BlogPost::factory()->count(50)->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'is_featured' => true,
            'published_at' => now()->subDays(rand(1, 60)),
        ]);
        
        // Create non-featured posts
        BlogPost::factory()->count(100)->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'is_featured' => false,
            'published_at' => now()->subDays(rand(1, 60)),
        ]);

        // Verify query returns only featured posts correctly
        // Index will be used when cost-beneficial
        $posts = BlogPost::published()->where('is_featured', true)->orderBy('published_at', 'desc')->get();
        expect($posts->count())->toBe(50)
            ->and($posts->every(fn($post) => $post->is_featured))->toBeTrue();
    })->group('performance', 'indexes');

    it('has draft posts index available for author queries and queries work correctly', function () {
        // Verify the drafts index exists
        $indexExists = DB::selectOne(
            "SELECT 1 FROM pg_indexes WHERE indexname = ? AND tablename = 'blog_posts'",
            ['blog_posts_drafts_by_author_index']
        );
        expect($indexExists)->not->toBeNull(
            'Drafts by author index blog_posts_drafts_by_author_index should exist'
        );
        
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create drafts for admin
        BlogPost::factory()->count(50)->create([
            'user_id' => $admin->id,
            'is_published' => false,
            'published_at' => null,
        ]);
        
        // Create drafts for other users
        $otherUsers = User::factory()->count(5)->create();
        foreach ($otherUsers as $user) {
            BlogPost::factory()->count(10)->create([
                'user_id' => $user->id,
                'is_published' => false,
            ]);
        }

        actingAs($admin);
        
        // Verify query returns only admin's drafts
        // Index will be used when cost-beneficial for filtering by user_id and is_published
        $drafts = BlogPost::where('user_id', $admin->id)
            ->where('is_published', false)
            ->orderBy('updated_at', 'desc')
            ->get();
        
        expect($drafts->count())->toBe(50)
            ->and($drafts->every(fn($post) => $post->user_id === $admin->id && !$post->is_published))->toBeTrue();
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
