<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PostgreSQL CREATE INDEX CONCURRENTLY cannot run inside a transaction.
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only apply PostgreSQL-specific indexes if using PostgreSQL
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $language = (string) config('database.full_text_search.language', 'english');

        // Validate regconfig token before embedding into DDL.
        // PostgreSQL identifiers/config names may include letters, digits, underscores, and dots.
        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $language)) {
            throw new RuntimeException("Invalid full-text search language config: {$language}");
        }

        // 1. GIN index for JSON tag searches (CRITICAL for whereJsonContains performance)
        // Used in: BlogPostService::applyFilters for tag filtering
        // Cast to jsonb for efficient indexing (json type doesn't support GIN efficiently)
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS blog_posts_tags_gin_index ON blog_posts USING GIN ((tags::jsonb) jsonb_path_ops)');

        // 2. Full-text search index for title, excerpt, and content (CRITICAL for search)
        // Used in: BlogPostService::applyFilters with ILIKE searches
        // Creates a tsvector combining all searchable text fields
        DB::statement(" 
            CREATE INDEX CONCURRENTLY IF NOT EXISTS blog_posts_search_index ON blog_posts 
            USING GIN (
                to_tsvector('{$language}', 
                    coalesce(title, '') || ' ' || 
                    coalesce(excerpt, '') || ' ' || 
                    coalesce(content, '')
                )
            )
        ");

        // 3. Composite index for published posts ordered by date (HIGH VALUE)
        // Used in: BlogPost::scopePublished() + orderBy('published_at')
        // Covers: WHERE is_published = true ORDER BY published_at DESC
        // Note: Partial index only includes published posts for smaller index size
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS blog_posts_published_date_index 
            ON blog_posts (published_at DESC) 
            WHERE is_published = true
        ');

        // 4. Composite index for featured published posts (MEDIUM VALUE)
        // Used in: BlogPostService::getFeaturedPosts()
        // Covers: WHERE is_published = true AND is_featured = true ORDER BY published_at DESC
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS blog_posts_featured_published_index 
            ON blog_posts (published_at DESC) 
            WHERE is_published = true AND is_featured = true
        ');

        // 5. Index for draft posts by author (MEDIUM VALUE)
        // Used in: BlogPostController::drafts()
        // Covers: WHERE is_published = false AND user_id = X ORDER BY updated_at DESC
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS blog_posts_drafts_by_author_index 
            ON blog_posts (user_id, updated_at DESC) 
            WHERE is_published = false
        ');

        // 6. Index for counting likes per post (MEDIUM VALUE)
        // Used in: BlogPost::withCount('likes')
        // The foreign key index on blog_post_id already exists, but this is explicit
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS blog_post_likes_post_id_index 
            ON blog_post_likes (blog_post_id)
        ');

        // 7. Index for user's liked posts (LOW-MEDIUM VALUE)
        // Used when showing which posts a user has liked
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS blog_post_likes_user_id_index 
            ON blog_post_likes (user_id)
        ');

        // 8. Index for follower counts (MEDIUM VALUE)
        // Used in: User::withCount('followers', 'following')
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS user_follows_following_id_index 
            ON user_follows (following_id)
        ');

        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS user_follows_follower_id_index 
            ON user_follows (follower_id)
        ');

        // 9. Index for finding comments by post (already exists via foreign key, but ensure it's there)
        // The comments_blog_post_id_index already exists from previous migration
        
        // 10. Composite index for threaded comments (MEDIUM VALUE)
        // Used when loading comment threads with parent_id
        DB::statement('
            CREATE INDEX CONCURRENTLY IF NOT EXISTS comments_thread_index 
            ON comments (blog_post_id, parent_id, created_at DESC)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Drop all PostgreSQL-specific indexes
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS blog_posts_tags_gin_index');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS blog_posts_search_index');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS blog_posts_published_date_index');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS blog_posts_featured_published_index');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS blog_posts_drafts_by_author_index');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS blog_post_likes_post_id_index');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS blog_post_likes_user_id_index');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS user_follows_following_id_index');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS user_follows_follower_id_index');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS comments_thread_index');
    }
};
