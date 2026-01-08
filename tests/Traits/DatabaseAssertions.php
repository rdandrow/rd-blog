<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;

/**
 * Trait for common database assertions in tests.
 * 
 * Provides fluent, Pest-style database assertions that complement
 * the expect() API with database-specific operations.
 * 
 * Usage:
 * ```php
 * use Tests\Traits\DatabaseAssertions;
 * 
 * uses(DatabaseAssertions::class);
 * 
 * it('creates a record', function () {
 *     $user = createTestMember();
 *     $this->assertDatabaseHasRecord('users', ['id' => $user->id]);
 * });
 * ```
 */
trait DatabaseAssertions
{
    use InteractsWithDatabase;
    
    /**
     * Assert that a table has a record matching the given data.
     *
     * @param string $table
     * @param array $data
     * @param string|null $connection
     * @return $this
     */
    protected function assertDatabaseHasRecord(string $table, array $data, ?string $connection = null): static
    {
        $this->assertDatabaseHas($table, $data, $connection);
        return $this;
    }
    
    /**
     * Assert that a table does NOT have a record matching the given data.
     *
     * @param string $table
     * @param array $data
     * @param string|null $connection
     * @return $this
     */
    protected function assertDatabaseMissingRecord(string $table, array $data, ?string $connection = null): static
    {
        $this->assertDatabaseMissing($table, $data, $connection);
        return $this;
    }
    
    /**
     * Assert that a record exists in the database with specific counts.
     * Useful for checking relationship counts.
     *
     * @param string $table
     * @param array $data
     * @param int $expectedCount
     * @param string|null $connection
     * @return $this
     */
    protected function assertDatabaseHasCount(string $table, array $data, int $expectedCount, ?string $connection = null): static
    {
        $count = $this->getConnection($connection)
            ->table($table)
            ->where($data)
            ->count();
        
        expect($count)->toBe($expectedCount,
            "Expected {$expectedCount} record(s) in table '{$table}' but found {$count}"
        );
        
        return $this;
    }
    
    /**
     * Assert that a model was soft deleted.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return $this
     */
    protected function assertModelSoftDeleted($model): static
    {
        $this->assertSoftDeleted($model);
        return $this;
    }
    
    /**
     * Assert that a blog post exists with specific attributes.
     *
     * @param array $attributes
     * @return $this
     */
    protected function assertBlogPostExists(array $attributes): static
    {
        $this->assertDatabaseHas('blog_posts', $attributes);
        return $this;
    }
    
    /**
     * Assert that a comment exists with specific attributes.
     *
     * @param array $attributes
     * @return $this
     */
    protected function assertCommentExists(array $attributes): static
    {
        $this->assertDatabaseHas('comments', $attributes);
        return $this;
    }
    
    /**
     * Assert that a like exists for a blog post and user.
     *
     * @param int $blogPostId
     * @param int $userId
     * @return $this
     */
    protected function assertLikeExists(int $blogPostId, int $userId): static
    {
        $this->assertDatabaseHas('blog_post_likes', [
            'blog_post_id' => $blogPostId,
            'user_id' => $userId,
        ]);
        return $this;
    }
    
    /**
     * Assert that a like does NOT exist for a blog post and user.
     *
     * @param int $blogPostId
     * @param int $userId
     * @return $this
     */
    protected function assertLikeNotExists(int $blogPostId, int $userId): static
    {
        $this->assertDatabaseMissing('blog_post_likes', [
            'blog_post_id' => $blogPostId,
            'user_id' => $userId,
        ]);
        return $this;
    }
    
    /**
     * Assert that a user follows another user.
     *
     * @param int $followerId
     * @param int $followingId
     * @return $this
     */
    protected function assertUserFollows(int $followerId, int $followingId): static
    {
        $this->assertDatabaseHas('user_follows', [
            'follower_id' => $followerId,
            'following_id' => $followingId,
        ]);
        return $this;
    }
    
    /**
     * Assert that a user does NOT follow another user.
     *
     * @param int $followerId
     * @param int $followingId
     * @return $this
     */
    protected function assertUserNotFollows(int $followerId, int $followingId): static
    {
        $this->assertDatabaseMissing('user_follows', [
            'follower_id' => $followerId,
            'following_id' => $followingId,
        ]);
        return $this;
    }
}
