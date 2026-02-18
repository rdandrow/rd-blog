<?php

namespace App\Models;

use App\Database\Concerns\HasBatchOperations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
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
            // Invalidate once after bulk operation completes
            static::invalidateBlogCache();
        }
    }

    /**
     * Invalidate all blog-related caches.
     * 
     * Uses cache tags for efficient invalidation (Redis/Memcached)
     * Falls back to pattern-based clearing for other drivers.
     */
    protected static function invalidateBlogCache(): void
    {
        $store = Cache::getStore();
        
        // Use cache tags if supported (Redis, Memcached)
        if (method_exists($store, 'tags')) {
            Cache::tags(['blog_posts'])->flush();
            return;
        }
        
        // Fallback for drivers that don't support tags (file, database)
        // This is less efficient but necessary for compatibility
        static::flushBlogCacheKeys();
    }

    /**
     * Flush blog cache keys for drivers that don't support tags.
     * 
     * This is a fallback method - prefer using cache tags with Redis.
     */
    protected static function flushBlogCacheKeys(): void
    {
        $prefixes = [
            'blog:popular_posts',
            'blog:featured_posts',
            'blog:recent_posts',
            'blog:available_tags',
            'blog:available_authors',
            'blog:post_stats',
            'blog:all_published_posts',
            'blog:landing_page',
        ];

        foreach ($prefixes as $prefix) {
            // Clear base key
            Cache::forget($prefix);
            
            // Clear common variations (limit parameter)
            // Note: Only clears up to limit 20 to reduce overhead
            // Filtered caches with MD5 hashes will expire via TTL
            for ($i = 1; $i <= 20; $i++) {
                Cache::forget("{$prefix}:{$i}");
            }
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
