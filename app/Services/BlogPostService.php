<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\User;
use App\Services\Concerns\CachesBlogData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BlogPostService
{
    use CachesBlogData;
    /**
     * Apply search and filter conditions to a query.
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            
            // Use PostgreSQL full-text search for better performance
            if ($this->isPostgreSqlConnection($query)) {
                $language = config('database.full_text_search.language', 'english');
                $query->whereRaw(
                    "to_tsvector(?, coalesce(title, '') || ' ' || coalesce(excerpt, '') || ' ' || coalesce(content, '')) @@ plainto_tsquery(?, ?)",
                    [$language, $language, $search]
                );
            } else {
                // Fallback for other databases (portable case-insensitive pattern matching)
                $searchLower = strtolower($search);
                $query->where(function (Builder $q) use ($searchLower) {
                    $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(excerpt) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(content) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }

        // Apply tag filter
        if (!empty($filters['tag'])) {
            $query->whereJsonContains('tags', $filters['tag']);
        }

        // Apply author filter
        if (!empty($filters['author'])) {
            $query->where('user_id', $filters['author']);
        }

        return $query;
    }

    /**
     * Determine whether the query is using a PostgreSQL connection.
     */
    protected function isPostgreSqlConnection(Builder $query): bool
    {
        return $query->getConnection()->getDriverName() === 'pgsql';
    }

    /**
     * Get featured blog posts.
     */
    public function getFeaturedPosts(int $limit = 2, array $filters = []): Collection
    {
        // Use cache if no filters or only common filters
        if (empty($filters) || $this->isCacheable($filters)) {
            return $this->cacheFeaturedPosts($limit, $filters);
        }

        // Bypass cache for complex filters
        $query = BlogPost::with('author')
            ->published()
            ->where('is_featured', true);

        $query = $this->applyFilters($query, $filters);

        return $query->orderBy('published_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Get recent blog posts (non-featured).
     */
    public function getRecentPosts(int $limit = 6, array $filters = []): Collection
    {
        // Use cache if no filters or only common filters
        if (empty($filters) || $this->isCacheable($filters)) {
            return $this->cacheRecentPosts($limit, $filters);
        }

        // Bypass cache for complex filters
        $query = BlogPost::with('author')
            ->published()
            ->where('is_featured', false);

        $query = $this->applyFilters($query, $filters);

        return $query->orderBy('published_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Get all published blog posts with optional filters.
     */
    public function getAllPublishedPosts(array $filters = []): Collection
    {
        $query = BlogPost::with('author')->published();

        $query = $this->applyFilters($query, $filters);

        return $query->orderBy('published_at', 'desc')->get();
    }

    /**
     * Get all available tags from published posts.
     */
    public function getAvailableTags(): Collection
    {
        return $this->cacheAvailableTags();
    }

    /**
     * Get all authors who have published posts.
     */
    public function getAvailableAuthors(): Collection
    {
        return $this->cacheAvailableAuthors();
    }

    /**
     * Get data for the landing page.
     */
    public function getLandingPageData(array $filters = []): array
    {
        return [
            'featured_posts' => $this->getFeaturedPosts(2, $filters),
            'recent_posts' => $this->getRecentPosts(6, $filters),
            'available_tags' => $this->getAvailableTags(),
            'available_authors' => $this->getAvailableAuthors(),
        ];
    }

    /**
     * Get data for the blog listing page.
     */
    public function getBlogListingData(array $filters = []): array
    {
        $allPosts = $this->getAllPublishedPosts($filters);

        return [
            'featured_posts' => $allPosts->where('is_featured', true)->take(4)->values(),
            'posts' => $allPosts->where('is_featured', false)->values(),
            'available_tags' => $this->getAvailableTags(),
            'available_authors' => $this->getAvailableAuthors(),
        ];
    }

    /**
     * Get the most recently published blog posts.
     *
     * Named "popular" for API intent, but ordered by published_at because
     * view/engagement tracking is not yet implemented. Replace the ordering
     * in cachePopularPosts() once real popularity metrics are available.
     */
    public function getPopularPosts(int $limit = 10): Collection
    {
        return $this->cachePopularPosts($limit);
    }

    /**
     * Get blog post statistics.
     */
    public function getPostStats(): array
    {
        return $this->cachePostStats();
    }

    /**
     * Check if filters are cacheable.
     * 
     * Only cache common filter combinations to avoid cache pollution.
     */
    protected function isCacheable(array $filters): bool
    {
        // Only cache tag and author filters (most common)
        $allowedKeys = ['tag', 'author'];
        
        foreach (array_keys($filters) as $key) {
            if (!in_array($key, $allowedKeys)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Invalidate all blog caches.
     * 
     * Call this after creating/updating/deleting posts.
     * This delegates to the model's cache invalidation logic.
     */
    public function invalidateCache(): void
    {
        BlogPost::invalidateBlogCache();
    }
}
