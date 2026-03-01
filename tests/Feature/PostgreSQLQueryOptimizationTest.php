<?php

use App\Models\BlogPost;
use App\Models\User;
use App\Services\BlogPostService;
use Illuminate\Support\Facades\DB;

/*
 * PostgreSQL Query Optimization Tests
 * 
 * These tests verify that the PostgreSQL-specific query optimizations
 * (full-text search, stemming, etc.) are working correctly.
 */

describe('PostgreSQL Query Optimizations', function () {

    it('uses full-text search for PostgreSQL', function () {
        // Create test data
        $admin = User::factory()->create(['role' => 'admin']);
        
        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Understanding Laravel Framework',
            'content' => 'Laravel is a wonderful PHP framework for building web applications.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Vue.js for Beginners',
            'content' => 'Vue is a progressive JavaScript framework.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Enable query logging
        DB::enableQueryLog();

        // Perform search
        $service = new BlogPostService();
        $results = $service->getAllPublishedPosts(['search' => 'Laravel']);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Verify we found the right post
        expect($results)->toHaveCount(1)
            ->and($results->first()->title)->toBe('Understanding Laravel Framework');

        // Verify the query uses PostgreSQL full-text search
        $searchQuery = collect($queries)->first(function ($query) {
            return str_contains($query['query'], 'to_tsvector');
        });

        expect($searchQuery)->not->toBeNull()
            ->and($searchQuery['query'])->toContain('to_tsvector')
            ->and($searchQuery['query'])->toContain('plainto_tsquery');
    })->group('performance', 'postgresql', 'optimization');

    it('handles special characters in search queries', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        
        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'title' => 'C++ Programming Guide',
            'content' => 'Learn C++ programming language basics.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $service = new BlogPostService();
        
        // Should handle C++ without errors
        $results = $service->getAllPublishedPosts(['search' => 'C++']);

        expect($results)->toHaveCount(1)
            ->and($results->first()->title)->toBe('C++ Programming Guide');
    })->group('performance', 'postgresql', 'optimization');

    it('performs case-insensitive searches', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        
        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'title' => 'PostgreSQL Database Design',
            'content' => 'Best practices for PostgreSQL database design.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $service = new BlogPostService();
        
        // All these should find the post (case-insensitive)
        $lowercase = $service->getAllPublishedPosts(['search' => 'postgresql']);
        $uppercase = $service->getAllPublishedPosts(['search' => 'POSTGRESQL']);
        $mixed = $service->getAllPublishedPosts(['search' => 'PostgreSQL']);

        expect($lowercase)->toHaveCount(1)
            ->and($uppercase)->toHaveCount(1)
            ->and($mixed)->toHaveCount(1);
    })->group('performance', 'postgresql', 'optimization');

    it('searches across title, excerpt, and content', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Post with term only in title
        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'title' => 'TypeScript Tutorial',
            'excerpt' => 'Learn about static typing',
            'content' => 'This guide covers JavaScript with types.',
            'is_published' => true,
            'published_at' => now()->subDays(3),
        ]);

        // Post with term only in excerpt
        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Modern JavaScript',
            'excerpt' => 'Using TypeScript for better development',
            'content' => 'JavaScript is evolving rapidly.',
            'is_published' => true,
            'published_at' => now()->subDays(2),
        ]);

        // Post with term only in content
        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Web Development',
            'excerpt' => 'Building modern web apps',
            'content' => 'We recommend TypeScript for large projects.',
            'is_published' => true,
            'published_at' => now()->subDays(1),
        ]);

        $service = new BlogPostService();
        $results = $service->getAllPublishedPosts(['search' => 'TypeScript']);

        // Should find all three posts
        expect($results)->toHaveCount(3);
    })->group('performance', 'postgresql', 'optimization');

    it('combines search with other filters', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        
        // Admin post with Laravel tag
        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Laravel Best Practices',
            'tags' => ['Laravel', 'PHP'],
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Member post with Laravel tag
        BlogPost::factory()->create([
            'user_id' => $member->id,
            'title' => 'Laravel Tips and Tricks',
            'tags' => ['Laravel', 'Tips'],
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Admin post without Laravel tag
        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Laravel Framework Guide',
            'tags' => ['PHP'],
            'is_published' => true,
            'published_at' => now(),
        ]);

        $service = new BlogPostService();
        
        // Search for Laravel + filter by admin + filter by Laravel tag
        $results = $service->getAllPublishedPosts([
            'search' => 'Laravel',
            'author' => $admin->id,
            'tag' => 'Laravel',
        ]);

        expect($results)->toHaveCount(1)
            ->and($results->first()->title)->toBe('Laravel Best Practices');
    })->group('performance', 'postgresql', 'optimization', 'filters');

});

describe('Query Performance Benchmarks', function () {

    it('demonstrates full-text search performance advantage', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create a decent amount of content to search through
        BlogPost::factory()->count(50)->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Add one post with our search term
        BlogPost::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Laravel Performance Optimization',
            'content' => 'How to optimize your Laravel applications for production.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $service = new BlogPostService();

        // Warm up
        $service->getAllPublishedPosts(['search' => 'optimization']);

        // Benchmark search
        $start = microtime(true);
        $results = $service->getAllPublishedPosts(['search' => 'optimization']);
        $duration = (microtime(true) - $start) * 1000; // Convert to ms

        // Should find the post
        expect($results)->toHaveCount(1)
            ->and($results->first()->title)->toBe('Laravel Performance Optimization');

        // With full-text search, this should be very fast even with 50+ posts
        // On a modern machine, this should complete in < 50ms
        expect($duration)->toBeLessThan(100);
    })->group('performance', 'postgresql', 'benchmark');

});
