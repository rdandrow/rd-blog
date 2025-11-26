<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BlogPostService
{
    /**
     * Apply search and filter conditions to a query.
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Apply search filter
        if (!empty($filters['search'])) {
            $query->where(function (Builder $q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                  ->orWhere('excerpt', 'like', "%{$filters['search']}%")
                  ->orWhere('content', 'like', "%{$filters['search']}%");
            });
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
     * Get featured blog posts.
     */
    public function getFeaturedPosts(int $limit = 2, array $filters = []): Collection
    {
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
        return BlogPost::published()
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Get all authors who have published posts.
     */
    public function getAvailableAuthors(): Collection
    {
        return User::whereHas('blogPosts', function (Builder $query) {
            $query->published();
        })->get(['id', 'name']);
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
}
