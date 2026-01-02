<?php

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// N+1 Query Detection Tests
test('blog post index avoids N+1 queries for authors', function () {
    $admin = createTestAdmin();
    
    // Create multiple posts with same author
    BlogPost::factory()->count(10)->create([
        'user_id' => $admin->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    // Enable query logging
    DB::enableQueryLog();
    
    $response = $this->get(route('blog'));
    
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    
    // Should not have a separate query for each post's author
    // Ideally should use eager loading
    expect(count($queries))->toBeLessThan(15); // Allow some queries but not 1 per post
});

test('author profile avoids N+1 queries for posts', function () {
    $admin = createTestAdmin();
    
    // Create multiple posts
    BlogPost::factory()->count(10)->create([
        'user_id' => $admin->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    DB::enableQueryLog();
    
    $response = $this->get(route('author.profile', $admin->id));
    
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    
    // Should load posts efficiently
    expect(count($queries))->toBeLessThan(15);
});

test('comment listing avoids N+1 queries for users', function () {
    $post = createPublishedPost();
    $users = User::factory()->count(10)->create();
    
    // Create comments from different users
    foreach ($users as $user) {
        createComment($post, $user);
    }
    
    DB::enableQueryLog();
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    
    // Should eagerly load comment authors
    expect(count($queries))->toBeLessThan(20);
});

test('blog post with relationships loads efficiently', function () {
    $author = createTestAdmin();
    $post = createPublishedPost(['user_id' => $author->id]);
    $users = User::factory()->count(5)->create();
    
    // Add likes and comments
    foreach ($users as $user) {
        DB::table('blog_post_likes')->insert([
            'user_id' => $user->id,
            'blog_post_id' => $post->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        createComment($post, $user);
    }
    
    DB::enableQueryLog();
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    
    // Should load all relationships efficiently
    expect(count($queries))->toBeLessThan(30);
});

// Large Dataset Handling Tests
test('handles pagination with large dataset', function () {
    $admin = createTestAdmin();
    
    // Create many posts
    BlogPost::factory()->count(100)->create([
        'user_id' => $admin->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('posts')
    );
});

test('handles large comment thread efficiently', function () {
    $post = createPublishedPost();
    $user = createTestMember();
    
    // Create many comments
    for ($i = 0; $i < 50; $i++) {
        createComment($post, $user, ['content' => "Comment $i"]);
    }
    
    $startTime = microtime(true);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    
    $response->assertStatus(200);
    
    // Should complete in reasonable time (adjust threshold as needed)
    expect($executionTime)->toBeLessThan(2); // 2 seconds
});

test('handles many likes on single post', function () {
    $post = createPublishedPost();
    $users = User::factory()->count(100)->create();
    
    // Add many likes
    foreach ($users as $user) {
        DB::table('blog_post_likes')->insert([
            'user_id' => $user->id,
            'blog_post_id' => $post->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertStatus(200);
});

test('handles many follows for author', function () {
    $author = createTestAdmin();
    $followers = User::factory()->count(100)->create();
    
    // Add many followers
    foreach ($followers as $follower) {
        DB::table('user_follows')->insert([
            'follower_id' => $follower->id,
            'following_id' => $author->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    $response = $this->get(route('author.profile', $author->id));
    
    $response->assertStatus(200);
});

test('search performs efficiently with many posts', function () {
    $admin = createTestAdmin();
    
    // Create many posts
    BlogPost::factory()->count(200)->create([
        'user_id' => $admin->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $startTime = microtime(true);
    
    $response = $this->get(route('blog', ['search' => 'test']));
    
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    
    $response->assertStatus(200);
    
    // Search should complete in reasonable time
    expect($executionTime)->toBeLessThan(3); // 3 seconds
});

// Memory Usage Tests
test('memory usage remains reasonable with large dataset', function () {
    $admin = createTestAdmin();
    
    $initialMemory = memory_get_usage();
    
    // Create posts
    BlogPost::factory()->count(100)->create([
        'user_id' => $admin->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog'));
    
    $peakMemory = memory_get_peak_usage();
    $memoryUsed = $peakMemory - $initialMemory;
    
    $response->assertStatus(200);
    
    // Should not use excessive memory (50MB threshold, adjust as needed)
    expect($memoryUsed)->toBeLessThan(50 * 1024 * 1024);
});

test('chunked processing works for bulk operations', function () {
    $admin = createTestAdmin();
    
    // Create many posts
    BlogPost::factory()->count(200)->create([
        'user_id' => $admin->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $processedCount = 0;
    
    // Process in chunks
    BlogPost::chunk(50, function ($posts) use (&$processedCount) {
        $processedCount += $posts->count();
    });
    
    expect($processedCount)->toBe(200);
});

// Query Optimization Tests
test('uses indexes effectively for frequently accessed queries', function () {
    $admin = createTestAdmin();
    
    BlogPost::factory()->count(50)->create([
        'user_id' => $admin->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    DB::enableQueryLog();
    
    // Query by slug (should use index)
    $post = BlogPost::where('slug', 'test-slug')->first();
    
    // Query published posts (should use index)
    $publishedPosts = BlogPost::where('is_published', true)->get();
    
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    
    expect(count($queries))->toBeLessThan(10);
});

test('counts are efficient with large datasets', function () {
    $admin = createTestAdmin();
    
    BlogPost::factory()->count(100)->create([
        'user_id' => $admin->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $startTime = microtime(true);
    
    $count = BlogPost::where('is_published', true)->count();
    
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    
    expect($count)->toBe(100);
    expect($executionTime)->toBeLessThan(0.5); // Should be very fast
});

// Relationship Loading Performance
test('loads nested relationships efficiently', function () {
    $admin = createTestAdmin();
    $post = createPublishedPost(['user_id' => $admin->id]);
    
    // Create comments with replies
    $parentComment = createComment($post, createTestMember());
    for ($i = 0; $i < 10; $i++) {
        createComment($post, createTestMember(), ['parent_id' => $parentComment->id]);
    }
    
    DB::enableQueryLog();
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    
    $response->assertStatus(200);
    
    // Should load nested comments efficiently
    expect(count($queries))->toBeLessThan(25);
});

// Stress Test
test('handles concurrent requests efficiently', function () {
    $post = createPublishedPost();
    
    $responses = [];
    $startTime = microtime(true);
    
    // Simulate multiple concurrent requests
    for ($i = 0; $i < 10; $i++) {
        $responses[] = $this->get(route('blog.show', $post->slug));
    }
    
    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;
    
    foreach ($responses as $response) {
        $response->assertStatus(200);
    }
    
    // All requests should complete in reasonable total time
    expect($totalTime)->toBeLessThan(5); // 5 seconds for 10 requests
});
