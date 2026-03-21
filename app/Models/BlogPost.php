<?php

namespace App\Models;

use App\Database\Concerns\HasBatchOperations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory;
    use HasBatchOperations;

    /**
     * Flag to temporarily disable cache invalidation during bulk operations.
     */
    protected static bool $cacheInvalidationEnabled = true;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'tags',
        'is_featured',
        'is_published',
        'published_at',
        'reading_time',
        'user_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $post) {
            // Generate slug if not provided
            if (empty($post->slug)) {
                $post->slug = static::generateUniqueSlug($post->title);
            }
            
            // Calculate reading time
            $post->reading_time = static::calculateReadingTime($post->content);
        });

        static::updating(function (self $post) {
            // Regenerate slug if title changed and no custom slug provided
            if ($post->isDirty('title') && !$post->isDirty('slug')) {
                $post->slug = static::generateUniqueSlug($post->title, $post->id);
            }
            
            // Recalculate reading time if content changed
            if ($post->isDirty('content')) {
                $post->reading_time = static::calculateReadingTime($post->content);
            }
        });

        // Invalidate cache when blog posts are created, updated, or deleted
        static::saved(function () {
            if (static::$cacheInvalidationEnabled) {
                static::invalidateBlogCache();
            }
        });

        static::deleted(function () {
            if (static::$cacheInvalidationEnabled) {
                static::invalidateBlogCache();
            }
        });
    }

    /**
     * Disable cache invalidation (for bulk operations).
     */
    public static function withoutCacheInvalidation(callable $callback): mixed
    {
        $previousState = static::$cacheInvalidationEnabled;
        static::$cacheInvalidationEnabled = false;

        try {
            return $callback();
        } finally {
            static::$cacheInvalidationEnabled = $previousState;
            // Only invalidate when re-enabling (outermost call)
            // This prevents multiple invalidations with nested calls
            if ($previousState === true) {
                static::invalidateBlogCache();
            }
        }
    }

    /**
     * Get the current blog cache version.
     * 
     * This version is included in all cache keys to enable instant invalidation
     * by simply incrementing the version number.
     * 
     * NOTE: The version counter is stored in cache using Cache::forever().
     * When you run `php artisan cache:clear`, the version resets to 1 and all
     * cached data is cleared, so the cache will naturally warm from scratch with
     * version 1 keys. This is the expected behavior and requires no manual intervention.
     * 
     * If you need the version to persist across cache clears (e.g., for debugging
     * or strict version monotonicity), store it in the database instead of cache.
     */
    public static function getCacheVersion(): int
    {
        $version = Cache::get('blog:cache_version');
        
        if ($version === null) {
            $version = 1;
            Cache::forever('blog:cache_version', $version);
        }
        
        return $version;
    }

    /**
     * Bump the blog cache version to invalidate all cached data.
     * 
     * This is more efficient than deleting individual keys, especially for
     * drivers that don't support cache tags (file, database, array).
     */
    public static function bumpCacheVersion(): void
    {
        try {
            $currentVersion = static::getCacheVersion();
            Cache::forever('blog:cache_version', $currentVersion + 1);
        } catch (\Throwable $exception) {
            Log::warning('Failed to bump blog cache version; continuing without cache invalidation.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate all blog-related caches.
     * 
     * Uses cache tags for efficient invalidation (Redis/Memcached)
     * Falls back to version bumping for other drivers.
     */
    public static function invalidateBlogCache(): void
    {
        try {
            // Use tag flushing when the active store reports tag support.
            if (Cache::supportsTags()) {
                Cache::tags(['blog_posts'])->flush();
                return;
            }

            // Fallback: Bump cache version to instantly invalidate all versioned keys
            // This is more efficient and reliable than trying to enumerate and delete keys
            static::bumpCacheVersion();
        } catch (\Throwable $exception) {
            Log::warning('Failed to invalidate blog cache; continuing without cache invalidation.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Generate a unique slug for the blog post.
     */
    public static function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;
        
        $query = static::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
            $query = static::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }
        
        return $slug;
    }

    /**
     * Calculate reading time in minutes (average 200 words per minute).
     */
    public static function calculateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        return max(1, ceil($wordCount / 200));
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(BlogPostLike::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(BlogPostView::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
                    ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
