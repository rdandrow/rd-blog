<?php

use App\Models\BlogPost;
use App\Models\User;
use App\Services\BlogPostService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

            // Second call - should hit cache (verify by comparing results)
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

            // Verify different limits are cached separately  
            expect($posts5)->toHaveCount(5)
                ->and($posts10)->toHaveCount(10);
            
            // Call again to verify caching works for both
            $cached5 = $this->service->getPopularPosts(5);
            $cached10 = $this->service->getPopularPosts(10);
            
            expect($cached5->pluck('id')->toArray())->toBe($posts5->pluck('id')->toArray())
                ->and($cached10->pluck('id')->toArray())->toBe($posts10->pluck('id')->toArray());
        });
    });

    describe('Cache Resilience', function () {
        it('falls back to callback when tagged cache read-through throws', function () {
            config(['cache.default' => 'redis']);

            $service = new class {
                use \App\Services\Concerns\CachesBlogData;

                public function rememberProxy(string $key, callable $callback, ?int $ttl = null): mixed
                {
                    return $this->remember($key, $callback, $ttl);
                }
            };

            Cache::shouldReceive('supportsTags')
                ->once()
                ->andReturn(true);

            Cache::shouldReceive('tags')
                ->once()
                ->with(['blog_posts'])
                ->andReturn(new class {
                    public function remember(string $key, int $ttl, callable $callback): mixed
                    {
                        throw new \RuntimeException('Redis unavailable');
                    }
                });

            Log::shouldReceive('warning')
                ->once()
                ->withArgs(function (string $message, array $context): bool {
                    return str_contains($message, 'Blog cache read-through failed')
                        && ($context['driver'] ?? null) === 'redis'
                        && ($context['key'] ?? null) === 'blog:test-key'
                        && ($context['exception'] ?? null) === \RuntimeException::class;
                });

            $executed = 0;

            $value = $service->rememberProxy('test-key', function () use (&$executed): string {
                $executed++;

                return 'fallback-value';
            });

            expect($value)->toBe('fallback-value')
                ->and($executed)->toBe(1);
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
            
            expect($tags)->toContain('Laravel', 'PHP', 'Vue');

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
                ->and($authors->pluck('name'))->not->toContain('No Posts');
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
            
            expect($posts)->toHaveCount(2);

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
            
            expect($posts)->toHaveCount(6);
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
            
            // Verify all posts have the Laravel tag
            foreach ($posts as $post) {
                expect($post->tags)->toContain('Laravel');
            }
        });

        it('bypasses cache for search filters', function () {
            $author = User::factory()->create();
            
            BlogPost::factory()->count(5)->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'is_featured' => true,
            ]);

            // Search filter should bypass cache - verify by checking DB queries
            DB::enableQueryLog();
            
            // First call with search filter
            $posts1 = $this->service->getFeaturedPosts(10, ['search' => 'test']);
            $firstCallQueries = count(DB::getQueryLog());
            DB::flushQueryLog();
            
            // Second identical call - should still hit DB (not cached)
            $posts2 = $this->service->getFeaturedPosts(10, ['search' => 'test']);
            $secondCallQueries = count(DB::getQueryLog());
            DB::disableQueryLog();
            
            expect($posts1)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($firstCallQueries)->toBeGreaterThan(0)
                ->and($secondCallQueries)->toBeGreaterThan(0)
                ->and($secondCallQueries)->toBe($firstCallQueries); // Same queries = not cached
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
                ->and($stats['featured_count'])->toBe(3);
        });
    });

    describe('Cache Invalidation', function () {
        it('invalidates cache when blog post is created', function () {
            $author = User::factory()->create();
            
            BlogPost::factory()->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'tags' => ['Laravel', 'PHP'],
            ]);

            // Prime the cache
            $tagsBefore = $this->service->getAvailableTags();
            expect($tagsBefore)->toContain('Laravel', 'PHP');

            // Create new post with a new tag
            BlogPost::factory()->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'tags' => ['NewTag'],
            ]);

            // Cache should be invalidated - verify new tag appears
            $tagsAfter = $this->service->getAvailableTags();
            expect($tagsAfter)->toContain('NewTag');
        });

        it('invalidates cache when blog post is updated', function () {
            $author = User::factory()->create();
            
            $post = BlogPost::factory()->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);

            // Prime the cache
            $postsBefore = $this->service->getPopularPosts(10);
            $originalTitle = $postsBefore->first()->title;

            // Update post
            $post->update(['title' => 'Updated Title']);

            // Cache should be invalidated - verify new title appears
            $postsAfter = $this->service->getPopularPosts(10);
            expect($postsAfter->first()->title)->not->toBe($originalTitle)
                ->and($postsAfter->first()->title)->toBe('Updated Title');
        });

        it('invalidates cache when blog post is deleted', function () {
            $author = User::factory()->create();
            
            $post = BlogPost::factory()->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);

            // Prime the cache
            $authorsBefore = $this->service->getAvailableAuthors();
            expect($authorsBefore)->toHaveCount(1);

            // Delete post
            $post->delete();

            // Cache should be invalidated - author should no longer appear
            $authorsAfter = $this->service->getAvailableAuthors();
            expect($authorsAfter)->toHaveCount(0);
        });

        it('can manually invalidate cache', function () {
            $author = User::factory()->create();
            
            BlogPost::factory()->count(10)->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
                'tags' => ['Laravel', 'PHP'],
            ]);

            // Prime multiple caches
            $tagsBefore = $this->service->getAvailableTags();
            $authorsBefore = $this->service->getAvailableAuthors();
            $postsBefore = $this->service->getPopularPosts(10);

            expect($tagsBefore)->toHaveCount(2)
                ->and($authorsBefore)->toHaveCount(1)
                ->and($postsBefore)->toHaveCount(10);

            // Manual invalidation
            $this->service->invalidateCache();

            // After manual invalidation, data should still be accessible (gets re-cached)
            $tagsAfter = $this->service->getAvailableTags();
            $authorsAfter = $this->service->getAvailableAuthors();
            $postsAfter = $this->service->getPopularPosts(10);
            
            expect($tagsAfter)->toHaveCount(2)
                ->and($authorsAfter)->toHaveCount(1)
                ->and($postsAfter)->toHaveCount(10);
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

    describe('Versioned Cache Keys', function () {
        it('uses versioned cache keys for non-tagged stores', function () {
            // Force use of non-tagged cache store (array driver doesn't support tags)
            config(['cache.default' => 'array']);
            Cache::flush();
            
            $author = User::factory()->create();
            BlogPost::factory()->count(5)->create([
                'user_id' => $author->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);

            // Get initial cache version
            $initialVersion = BlogPost::getCacheVersion();
            expect($initialVersion)->toBeInt()->toBeGreaterThan(0);

            // Fetch data (should cache with version)
            $posts1 = $this->service->getPopularPosts(10);
            expect($posts1)->toHaveCount(5);

            // Verify data is cached by fetching again
            $posts2 = $this->service->getPopularPosts(10);
            expect($posts2->pluck('id')->toArray())->toBe($posts1->pluck('id')->toArray());

            // Manually bump version to simulate invalidation
            BlogPost::bumpCacheVersion();

            // Cache version should have bumped
            $newVersion = BlogPost::getCacheVersion();
            expect($newVersion)->toBe($initialVersion + 1);

            // Create a new post
            BlogPost::withoutCacheInvalidation(function () use ($author) {
                BlogPost::factory()->create([
                    'user_id' => $author->id,
                    'is_published' => true,
                    'published_at' => now(),
                ]);
            });

            // Fetching data should now use new version key and return updated results
            $posts3 = $this->service->getPopularPosts(10);
            expect($posts3)->toHaveCount(6); // Now includes the new post
        });

        it('bumps cache version on model events for non-tagged stores', function () {
            config(['cache.default' => 'file']);
            Cache::flush();
            
            $author = User::factory()->create();
            
            // Manually set initial version
            Cache::forever('blog:cache_version', 10);
            expect(BlogPost::getCacheVersion())->toBe(10);

            // Create - should bump version
            $post = BlogPost::factory()->create([
                'user_id' => $author->id,
                'is_published' => true,
            ]);
            expect(BlogPost::getCacheVersion())->toBe(11);

            // Update - should bump version
            $post->update(['title' => 'Updated Title']);
            expect(BlogPost::getCacheVersion())->toBe(12);

            // Delete - should bump version
            $post->delete();
            expect(BlogPost::getCacheVersion())->toBe(13);
        });

        it('handles filtered cache keys with versions', function () {
            config(['cache.default' => 'array']);
            Cache::flush();
            
            $author = User::factory()->create();
            
            // Get baseline version
            $baseVersion = BlogPost::getCacheVersion();
            
            BlogPost::withoutCacheInvalidation(function () use ($author) {
                BlogPost::factory()->create([
                    'user_id' => $author->id,
                    'is_published' => true,
                    'is_featured' => true,
                    'published_at' => now(),
                    'tags' => ['Laravel', 'PHP'],
                ]);
            });

            // Fetch with filters (should cache with version + filter hash)
            $filtered1 = $this->service->getFeaturedPosts(10, ['tag' => 'Laravel']);
            expect($filtered1)->toHaveCount(1);

            // Manually bump version (simulating invalidation)
            BlogPost::bumpCacheVersion();
            
            // Create new post without auto-invalidation
            BlogPost::withoutCacheInvalidation(function () use ($author) {
                BlogPost::factory()->create([
                    'user_id' => $author->id,
                    'is_published' => true,
                    'is_featured' => true,
                    'published_at' => now(),
                    'tags' => ['Laravel', 'Vue'],
                ]);
            });

            $newVersion = BlogPost::getCacheVersion();
            expect($newVersion)->toBeGreaterThan($baseVersion);

            // Same filter query should now return updated results (new version key)
            $filtered2 = $this->service->getFeaturedPosts(10, ['tag' => 'Laravel']);
            expect($filtered2)->toHaveCount(2);
        });

        it('maintains separate versions for different cache stores', function () {
            // This test verifies that cache version is stored in cache,
            // so different cache stores can have different versions
            config(['cache.default' => 'array']);
            Cache::flush();
            
            $version1 = BlogPost::getCacheVersion();
            expect($version1)->toBe(1); // Initial version
            
            BlogPost::bumpCacheVersion();
            $version2 = BlogPost::getCacheVersion();
            expect($version2)->toBe(2);
            
            BlogPost::bumpCacheVersion();
            $version3 = BlogPost::getCacheVersion();
            expect($version3)->toBe(3);
        });
    });
});
