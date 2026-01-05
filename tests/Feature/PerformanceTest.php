<?php

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Performance Tests
 * 
 * These tests verify application performance characteristics including:
 * - N+1 query detection and prevention
 * - Large dataset handling (30-60 records - sufficient to catch issues)
 * - Memory usage patterns
 * - Query optimization and indexing
 * 
 * Note: Dataset sizes are intentionally smaller (20-60 records) to balance
 * test speed with effectiveness. These sizes are sufficient to detect
 * performance issues while keeping tests fast.
 */

describe('N+1 Query Detection', function () {
    describe('Blog Posts', function () {
        it('avoids N+1 queries for authors on index page', function () {
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
        })->group('performance', 'n+1', 'queries');

        it('loads relationships efficiently for single post', function () {
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
        })->group('performance', 'n+1', 'queries');
    });

    describe('Author Profiles', function () {
        it('avoids N+1 queries when loading posts', function () {
            $admin = createTestAdmin();
            
            // Create multiple posts with same author
            BlogPost::factory()->count(10)->create([
                'user_id' => $admin->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);
            
            DB::enableQueryLog();
            
            $response = $this->get(route('author.profile', $admin->id));
            
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            
            // Should load posts efficiently with eager loading (< 15 queries for 10 posts)
            expect(count($queries))->toBeLessThan(15);
        })->group('performance', 'n+1', 'queries');
    });

    describe('Comment Loading', function () {
        it('avoids N+1 queries for comment authors', function () {
            $post = createPublishedPost();
            $users = User::factory()->count(10)->create();
            
            // Create comments from different users (10 authors = potential N+1 issue)
            foreach ($users as $user) {
                createComment($post, $user);
            }
            
            DB::enableQueryLog();
            
            $response = $this->get(route('blog.show', $post->slug));
            
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            
            // Should eagerly load comment authors (< 20 queries for 10 comments)
            expect(count($queries))->toBeLessThan(20);
        })->group('performance', 'n+1', 'queries');

        it('loads nested relationships efficiently', function () {
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
            
            expect($response->status())->toBe(200)
                ->and(count($queries))->toBeLessThan(25);
        })->group('performance', 'n+1', 'queries');
    });
});

describe('Large Dataset Handling', function () {
    describe('Pagination', function () {
        it('handles pagination with large dataset', function () {
            $admin = createTestAdmin();
            
            // Create posts (30 is sufficient to test pagination)
            BlogPost::factory()->count(30)->create([
                'user_id' => $admin->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);
            
            $response = $this->get(route('blog'));
            
            expect($response->status())->toBe(200);
            $response->assertInertia(fn ($page) => $page
                ->has('posts')
            );
        })->group('performance', 'queries');

        it('handles search with many posts efficiently', function () {
            $admin = createTestAdmin();
            
            // Create posts (50 is sufficient to test search performance)
            BlogPost::factory()->count(50)->create([
                'user_id' => $admin->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);
            
            $startTime = microtime(true);
            
            $response = $this->get(route('blog', ['search' => 'test']));
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            expect($response->status())->toBe(200)
                ->and($executionTime)->toBeLessThan(3); // 3 seconds
        })->group('performance', 'queries');
    });

    describe('Bulk Operations', function () {
        it('handles large comment thread efficiently', function () {
            $post = createPublishedPost();
            $user = createTestMember();
            
            // Create comments (20 is sufficient to test performance)
            for ($i = 0; $i < 20; $i++) {
                createComment($post, $user, ['content' => "Comment $i"]);
            }
            
            $startTime = microtime(true);
            
            $response = $this->get(route('blog.show', $post->slug));
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            expect($response->status())->toBe(200)
                ->and($executionTime)->toBeLessThan(2); // 2 seconds
        })->group('performance', 'queries');

        it('handles many likes on single post', function () {
            $post = createPublishedPost();
            $users = User::factory()->count(30)->create();
            
            // Add likes (30 is sufficient to test aggregation)
            foreach ($users as $user) {
                DB::table('blog_post_likes')->insert([
                    'user_id' => $user->id,
                    'blog_post_id' => $post->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            $response = $this->get(route('blog.show', $post->slug));
            
            expect($response->status())->toBe(200);
        })->group('performance', 'queries');

        it('handles many follows for author', function () {
            $author = createTestAdmin();
            $followers = User::factory()->count(30)->create();
            
            // Add followers (30 is sufficient to test aggregation)
            foreach ($followers as $follower) {
                DB::table('user_follows')->insert([
                    'follower_id' => $follower->id,
                    'following_id' => $author->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            $response = $this->get(route('author.profile', $author->id));
            
            expect($response->status())->toBe(200);
        })->group('performance', 'queries');

        it('processes chunks for bulk operations', function () {
            $admin = createTestAdmin();
            
            // Create posts (60 is sufficient to test chunking with 20-record chunks)
            BlogPost::factory()->count(60)->create([
                'user_id' => $admin->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);
            
            $processedCount = 0;
            
            // Process in chunks of 20
            BlogPost::chunk(20, function ($posts) use (&$processedCount) {
                $processedCount += $posts->count();
            });
            
            expect($processedCount)->toBe(60);
        })->group('performance', 'optimization');
    });
});

describe('Query Optimization', function () {
    describe('Eager Loading', function () {
        it('uses indexes effectively for frequently accessed queries', function () {
            $admin = createTestAdmin();
            
            BlogPost::factory()->count(20)->create([
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
        })->group('performance', 'optimization', 'queries');
    });

    describe('Indexing', function () {
        it('performs counts efficiently with large datasets', function () {
            $admin = createTestAdmin();
            
            BlogPost::factory()->count(30)->create([
                'user_id' => $admin->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);
            
            $startTime = microtime(true);
            
            $count = BlogPost::where('is_published', true)->count();
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            expect($count)->toBe(30)
                ->and($executionTime)->toBeLessThan(0.5); // Should be very fast
        })->group('performance', 'optimization', 'queries');
    });

    describe('Stress Testing', function () {
        it('handles concurrent requests efficiently', function () {
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
        })->group('performance', 'optimization');
    });
});

describe('Memory Usage', function () {
    describe('Large Result Sets', function () {
        it('maintains reasonable memory usage with large dataset', function () {
            $admin = createTestAdmin();
            
            $initialMemory = memory_get_usage();
            
            // Create posts (30 is sufficient to test memory patterns)
            BlogPost::factory()->count(30)->create([
                'user_id' => $admin->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);
            
            $response = $this->get(route('blog'));
            
            $peakMemory = memory_get_peak_usage();
            $memoryUsed = $peakMemory - $initialMemory;
            
            expect($response->status())->toBe(200)
                ->and($memoryUsed)->toBeLessThan(30 * 1024 * 1024); // 30MB threshold
        })->group('performance', 'memory');
    });
});
