<?php

use App\Models\BlogPost;
use App\Models\User;
use App\Services\BlogPostService;
use Illuminate\Support\Facades\Cache;
use function Pest\Laravel\{actingAs};

beforeEach(function () {
    // Clear cache before each test
    Cache::flush();
    
    $this->service = new BlogPostService();
});

describe('BlogPostService Caching', function () {
    describe('Popular Posts Caching', function () {
        it('caches popular posts by published date', function () {
            $author = User::factory()->create();
            
            BlogPost::factory()->count(15)->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);

            // First call - should hit database
            $posts = $this->service->getPopularPosts(10);
            expect($posts)->toHaveCount(10);

            // Verify it's cached
            expect(Cache::has('blog:popular_posts:10'))->toBeTrue();

            // Second call - should hit cache
            $cachedPosts = $this->service->getPopularPosts(10);
            expect($cachedPosts)->toHaveCount(10)
                ->and($cachedPosts->pluck('id')->toArray())
                ->toBe($posts->pluck('id')->toArray());
        });

        it('caches different limits separately', function () {
            $author = User::factory()->create();
            
            BlogPost::factory()->count(20)->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);

            $posts5 = $this->service->getPopularPosts(5);
            $posts10 = $this->service->getPopularPosts(10);

            expect($posts5)->toHaveCount(5)
                ->and($posts10)->toHaveCount(10)
                ->and(Cache::has('blog:popular_posts:5'))->toBeTrue()
                ->and(Cache::has('blog:popular_posts:10'))->toBeTrue();
        });
    });

    describe('Available Tags Caching', function () {
        it('caches available tags from published posts', function () {
            $author = User::factory()->create();
            
            BlogPost::factory()->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'tags' => ['Laravel', 'PHP'],
            ]);

            BlogPost::factory()->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'tags' => ['Vue', 'Laravel'],
            ]);

            // First call
            $tags = $this->service->getAvailableTags();
            
            expect($tags)->toContain('Laravel', 'PHP', 'Vue')
                ->and(Cache::has('blog:available_tags'))->toBeTrue();

            // Second call should hit cache
            $cachedTags = $this->service->getAvailableTags();
            expect($cachedTags->toArray())->toBe($tags->toArray());
        });

        it('excludes tags from unpublished posts', function () {
            $author = User::factory()->create();
            
            BlogPost::factory()->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'tags' => ['Published'],
            ]);

            BlogPost::factory()->create([
                'user_id' => $author->id,
                'is_published' => false,
                'tags' => ['Draft'],
            ]);

            $tags = $this->service->getAvailableTags();
            
            expect($tags)->toContain('Published')
                ->and($tags)->not->toContain('Draft');
        });
    });

    describe('Available Authors Caching', function () {
        it('caches authors with published posts', function () {
            $author1 = User::factory()->create(['name' => 'Author One']);
            $author2 = User::factory()->create(['name' => 'Author Two']);
            $author3 = User::factory()->create(['name' => 'No Posts']);

            BlogPost::factory()->create([
                'user_id' => $author1->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);

            BlogPost::factory()->create([
                'user_id' => $author2->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);

            $authors = $this->service->getAvailableAuthors();
            
            expect($authors)->toHaveCount(2)
                ->and($authors->pluck('name'))->toContain('Author One', 'Author Two')
                ->and($authors->pluck('name'))->not->toContain('No Posts')
                ->and(Cache::has('blog:available_authors'))->toBeTrue();
        });
    });

    describe('Featured and Recent Posts Caching', function () {
        it('caches featured posts without filters', function () {
            $author = User::factory()->create();
            
            BlogPost::factory()->count(5)->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'is_featured' => true,
            ]);

            $posts = $this->service->getFeaturedPosts(2);
            
            expect($posts)->toHaveCount(2)
                ->and(Cache::has('blog:featured_posts:2'))->toBeTrue();

            // Second call hits cache
            $cachedPosts = $this->service->getFeaturedPosts(2);
            expect($cachedPosts->pluck('id')->toArray())
                ->toBe($posts->pluck('id')->toArray());
        });

        it('caches recent posts without filters', function () {
            $author = User::factory()->create();
            
            BlogPost::factory()->count(10)->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'is_featured' => false,
            ]);

            $posts = $this->service->getRecentPosts(6);
            
            expect($posts)->toHaveCount(6)
                ->and(Cache::has('blog:recent_posts:6'))->toBeTrue();
        });

        it('caches posts with tag filters', function () {
            $author = User::factory()->create();
            
            BlogPost::factory()->count(5)->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'is_featured' => true,
                'tags' => ['Laravel'],
            ]);

            BlogPost::factory()->count(3)->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'is_featured' => true,
                'tags' => ['Vue'],
            ]);

            $posts = $this->service->getFeaturedPosts(10, ['tag' => 'Laravel']);
            
            expect($posts)->toHaveCount(5);
            
            // Should be cached with filter hash
            $filterHash = md5(json_encode(['tag' => 'Laravel']));
            expect(Cache::has("blog:featured_posts:10:{$filterHash}"))->toBeTrue();
        });

        it('bypasses cache for search filters', function () {
            $author = User::factory()->create();
            
            BlogPost::factory()->count(5)->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'is_featured' => true,
            ]);

            // Search filter should bypass cache
            $posts = $this->service->getFeaturedPosts(10, ['search' => 'test']);
            
            expect($posts)->toBeInstanceOf(\Illuminate\Support\Collection::class);
            
            // Should NOT be cached (search is not cacheable)
            $searchHash = md5(json_encode(['search' => 'test']));
            expect(Cache::has("blog:featured_posts:10:{$searchHash}"))->toBeFalse();
        });
    });

    describe('Post Statistics Caching', function () {
        it('caches post statistics', function () {
            $author = User::factory()->create();
            
            // Create 10 non-featured posts
            BlogPost::factory()->count(10)->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'is_featured' => false,  // Explicitly set to false
            ]);

            // Create 3 featured posts
            BlogPost::factory()->count(3)->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'is_featured' => true,
            ]);

            $stats = $this->service->getPostStats();
            
            expect($stats)->toHaveKeys(['total_posts', 'featured_count', 'total_comments', 'total_likes'])
                ->and($stats['total_posts'])->toBe(13)
                ->and($stats['featured_count'])->toBe(3)
                ->and(Cache::has('blog:post_stats'))->toBeTrue();
        });
    });

    describe('Cache Invalidation', function () {
        it('invalidates cache when blog post is created', function () {
            $author = User::factory()->create();
            
            // Prime the cache
            $this->service->getAvailableTags();
            expect(Cache::has('blog:available_tags'))->toBeTrue();

            // Create new post
            BlogPost::factory()->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'tags' => ['NewTag'],
            ]);

            // Cache should be invalidated
            expect(Cache::has('blog:available_tags'))->toBeFalse();
        });

        it('invalidates cache when blog post is updated', function () {
            $author = User::factory()->create();
            
            $post = BlogPost::factory()->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);

            // Prime the cache
            $this->service->getPopularPosts(10);
            expect(Cache::has('blog:popular_posts:10'))->toBeTrue();

            // Update post
            $post->update(['title' => 'Updated Title']);

            // Cache should be invalidated
            expect(Cache::has('blog:popular_posts:10'))->toBeFalse();
        });

        it('invalidates cache when blog post is deleted', function () {
            $author = User::factory()->create();
            
            $post = BlogPost::factory()->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);

            // Prime the cache
            $this->service->getAvailableAuthors();
            expect(Cache::has('blog:available_authors'))->toBeTrue();

            // Delete post
            $post->delete();

            // Cache should be invalidated
            expect(Cache::has('blog:available_authors'))->toBeFalse();
        });

        it('can manually invalidate cache', function () {
            // Prime multiple caches
            $this->service->getAvailableTags();
            $this->service->getAvailableAuthors();
            $this->service->getPopularPosts(10);

            expect(Cache::has('blog:available_tags'))->toBeTrue()
                ->and(Cache::has('blog:available_authors'))->toBeTrue()
                ->and(Cache::has('blog:popular_posts:10'))->toBeTrue();

            // Manual invalidation
            $this->service->invalidateCache();

            // All caches should be cleared
            expect(Cache::has('blog:available_tags'))->toBeFalse()
                ->and(Cache::has('blog:available_authors'))->toBeFalse()
                ->and(Cache::has('blog:popular_posts:10'))->toBeFalse();
        });
    });

    describe('Cache Performance', function () {
        it('reduces database queries with caching', function () {
            $author = User::factory()->create();
            
            BlogPost::factory()->count(20)->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);

            // Clear any existing cache
            Cache::flush();

            // First call - hits database
            DB::enableQueryLog();
            $this->service->getPopularPosts(10);
            $firstCallQueries = count(DB::getQueryLog());
            DB::flushQueryLog();

            // Second call - hits cache (should be 0 or minimal queries)
            $this->service->getPopularPosts(10);
            $secondCallQueries = count(DB::getQueryLog());
            DB::disableQueryLog();

            expect($firstCallQueries)->toBeGreaterThan(0)
                ->and($secondCallQueries)->toBeLessThan($firstCallQueries);
        });
    });
});
