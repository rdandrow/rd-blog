<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

/**
 * Trait for caching blog-related data with automatic cache invalidation.
 * 
 * Provides methods for caching expensive queries like popular posts,
 * tag lists, author lists, and post statistics.
 */
trait CachesBlogData
{
    /**
     * Cache TTL in seconds (default: 1 hour).
     */
    protected int $cacheTtl = 3600;

    /**
     * Cache key prefix for blog data.
     */
    protected string $cachePrefix = 'blog:';

    /**
     * Remember a cached value with the blog prefix.
     *
     * @param string $key Cache key (will be prefixed)
     * @param callable $callback Callback to execute if cache miss
     * @param int|null $ttl Time to live in seconds (null = use default)
     * @return mixed
     */
    protected function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? $this->cacheTtl;
        $fullKey = $this->cachePrefix . $key;

        return Cache::remember($fullKey, $ttl, $callback);
    }

    /**
     * Forget a cached value.
     *
     * @param string $key Cache key (will be prefixed)
     * @return bool
     */
    protected function forget(string $key): bool
    {
        return Cache::forget($this->cachePrefix . $key);
    }

    /**
     * Flush all blog-related cached data.
     *
     * @return void
     */
    protected function flushBlogCache(): void
    {
        $baseKeys = [
            'popular_posts',
            'featured_posts',
            'recent_posts',
            'available_tags',
            'available_authors',
            'post_stats',
        ];

        // Clear base keys
        foreach ($baseKeys as $key) {
            $this->forget($key);
        }
        
        // Clear variations with different limits and filters
        foreach ($baseKeys as $key) {
            // Clear variations with limits (1-50)
            for ($i = 1; $i <= 50; $i++) {
                Cache::forget($this->cachePrefix . "{$key}:{$i}");
            }
            
            // Clear variations with filter hashes (can't enumerate all, rely on TTL)
            // In production, consider using cache tags for better invalidation
        }
    }

    /**
     * Get cache key for filtered data.
     *
     * @param string $baseKey Base cache key
     * @param array $filters Filters to include in key
     * @return string
     */
    protected function getFilteredCacheKey(string $baseKey, array $filters): string
    {
        if (empty($filters)) {
            return $baseKey;
        }

        // Sort filters for consistent cache keys
        ksort($filters);
        
        // Create hash of filters
        $filterHash = md5(json_encode($filters));
        
        return "{$baseKey}:{$filterHash}";
    }

    /**
     * Cache popular posts (by most recent published date).
     * Note: This uses published_at as a proxy for "popular" since views
     * tracking is not implemented. In production, use views or engagement metrics.
     *
     * @param int $limit Number of posts
     * @return Collection
     */
    protected function cachePopularPosts(int $limit = 10): Collection
    {
        return $this->remember("popular_posts:{$limit}", function () use ($limit) {
            return \App\Models\BlogPost::with('author')
                ->published()
                ->orderBy('published_at', 'desc')
                ->take($limit)
                ->get();
        });
    }

    /**
     * Cache available tags.
     *
     * @return Collection
     */
    protected function cacheAvailableTags(): Collection
    {
        return $this->remember('available_tags', function () {
            return \App\Models\BlogPost::published()
                ->select('tags')
                ->whereNotNull('tags')
                ->get()
                ->pluck('tags')
                ->flatten()
                ->unique()
                ->sort()
                ->values();
        });
    }

    /**
     * Cache available authors.
     *
     * @return Collection
     */
    protected function cacheAvailableAuthors(): Collection
    {
        return $this->remember('available_authors', function () {
            return \App\Models\User::whereHas('blogPosts', function ($query) {
                $query->published();
            })->get(['id', 'name']);
        });
    }

    /**
     * Cache post statistics.
     *
     * @return array
     */
    protected function cachePostStats(): array
    {
        return $this->remember('post_stats', function () {
            return [
                'total_posts' => \App\Models\BlogPost::published()->count(),
                'featured_count' => \App\Models\BlogPost::published()->where('is_featured', true)->count(),
                'total_comments' => \App\Models\Comment::whereHas('blogPost', function ($query) {
                    $query->published();
                })->count(),
                'total_likes' => \App\Models\BlogPostLike::whereHas('blogPost', function ($query) {
                    $query->published();
                })->count(),
            ];
        }, 1800); // 30 minutes for stats
    }

    /**
     * Cache featured posts with optional filters.
     *
     * @param int $limit Number of posts
     * @param array $filters Optional filters
     * @return Collection
     */
    protected function cacheFeaturedPosts(int $limit = 2, array $filters = []): Collection
    {
        $cacheKey = $this->getFilteredCacheKey("featured_posts:{$limit}", $filters);
        
        return $this->remember($cacheKey, function () use ($limit, $filters) {
            $query = \App\Models\BlogPost::with('author')
                ->published()
                ->where('is_featured', true);

            // Apply filters if provided
            if (!empty($filters) && method_exists($this, 'applyFilters')) {
                $query = $this->applyFilters($query, $filters);
            }

            return $query->orderBy('published_at', 'desc')
                ->take($limit)
                ->get();
        });
    }

    /**
     * Cache recent posts with optional filters.
     *
     * @param int $limit Number of posts
     * @param array $filters Optional filters
     * @return Collection
     */
    protected function cacheRecentPosts(int $limit = 6, array $filters = []): Collection
    {
        $cacheKey = $this->getFilteredCacheKey("recent_posts:{$limit}", $filters);
        
        return $this->remember($cacheKey, function () use ($limit, $filters) {
            $query = \App\Models\BlogPost::with('author')
                ->published()
                ->where('is_featured', false);

            // Apply filters if provided
            if (!empty($filters) && method_exists($this, 'applyFilters')) {
                $query = $this->applyFilters($query, $filters);
            }

            return $query->orderBy('published_at', 'desc')
                ->take($limit)
                ->get();
        });
    }
}
