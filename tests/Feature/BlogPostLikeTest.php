<?php

/**
 * Blog Post Like Test Suite
 *
 * Tests the like/unlike functionality for blog posts including toggle behavior,
 * like counting, and access control.
 *
 * Test Categories:
 * - Liking Blog Posts: Creating likes on posts
 * - Unliking Blog Posts: Removing existing likes
 * - Toggle Behavior: Like/unlike in single action
 * - Like Counts: Accurate counting and display
 * - Access Control: Authentication requirements
 * - Multiple Users: Multiple users liking same post
 *
 * Features Tested:
 * - Authenticated user can like posts
 * - Toggle behavior (like/unlike same endpoint)
 * - Like count tracking
 * - Duplicate prevention (one like per user per post)
 * - Guest access prevention
 * - Success/error message display
 * - Database integrity
 */

use App\Models\BlogPost;
use App\Models\BlogPostLike;
use App\Models\User;

// Liking Blog Posts Tests
test('authenticated users can like a blog post', function () {
    $user = createTestMember();
    $post = createPublishedPost();

    $response = $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Post liked');
    
    $this->assertDatabaseHas('blog_post_likes', [
        'blog_post_id' => $post->id,
        'user_id' => $user->id,
    ]);
});

test('guests cannot like blog posts', function () {
    $post = createPublishedPost();

    $response = $this->post(route('blog.like.toggle', $post->slug));

    $response->assertRedirect(route('login'));
    $this->assertDatabaseCount('blog_post_likes', 0);
});

test('cannot like non-existent blog posts', function () {
    $user = createTestMember();

    $response = $this->actingAs($user)->post(route('blog.like.toggle', 'non-existent-slug'));

    $response->assertNotFound(); // 404 - blog post doesn't exist
});

test('users can like their own blog posts', function () {
    $author = createTestAdmin();
    $post = createPublishedPost(['user_id' => $author->id]);

    $response = $this->actingAs($author)->post(route('blog.like.toggle', $post->slug));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Post liked');
    
    $this->assertDatabaseHas('blog_post_likes', [
        'blog_post_id' => $post->id,
        'user_id' => $author->id,
    ]);
});

test('multiple users can like the same blog post', function () {
    $user1 = createTestMember();
    $user2 = createTestMember();
    $user3 = createTestMember();
    $post = createPublishedPost();

    $this->actingAs($user1)->post(route('blog.like.toggle', $post->slug));
    $this->actingAs($user2)->post(route('blog.like.toggle', $post->slug));
    $this->actingAs($user3)->post(route('blog.like.toggle', $post->slug));

    $this->assertDatabaseCount('blog_post_likes', 3); // All 3 likes should persist
    expect(BlogPostLike::where('blog_post_id', $post->id)->count())->toBe(3);
});

test('user can like multiple blog posts', function () {
    $user = createTestMember();
    $author = createTestAdmin();
    $post1 = createPublishedPost(['user_id' => $author->id]);
    $post2 = createPublishedPost(['user_id' => $author->id]);
    $post3 = createPublishedPost(['user_id' => $author->id]);

    $this->actingAs($user)->post(route('blog.like.toggle', $post1->slug));
    $this->actingAs($user)->post(route('blog.like.toggle', $post2->slug));
    $this->actingAs($user)->post(route('blog.like.toggle', $post3->slug));

    $this->assertDatabaseCount('blog_post_likes', 3);
    expect(BlogPostLike::where('user_id', $user->id)->count())->toBe(3);
});

test('user can like both published and draft posts', function () {
    $user = createTestMember();
    $author = createTestAdmin();
    $publishedPost = createPublishedPost(['user_id' => $author->id]);
    $draftPost = createDraftPost(['user_id' => $author->id]);

    $this->actingAs($user)->post(route('blog.like.toggle', $publishedPost->slug));
    $this->actingAs($user)->post(route('blog.like.toggle', $draftPost->slug));

    $this->assertDatabaseHas('blog_post_likes', [
        'blog_post_id' => $publishedPost->id,
        'user_id' => $user->id,
    ]);
    
    $this->assertDatabaseHas('blog_post_likes', [
        'blog_post_id' => $draftPost->id,
        'user_id' => $user->id,
    ]);
});

// Unliking Blog Posts Tests
test('authenticated users can unlike a blog post they previously liked', function () {
    $user = createTestMember();
    $post = createPublishedPost();

    // First, like the post
    BlogPostLike::create([
        'blog_post_id' => $post->id,
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('blog_post_likes', [
        'blog_post_id' => $post->id,
        'user_id' => $user->id,
    ]);

    // Now, unlike the post
    $response = $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Like removed');
    
    $this->assertDatabaseMissing('blog_post_likes', [
        'blog_post_id' => $post->id,
        'user_id' => $user->id,
    ]);
});

test('toggling like twice returns to original state', function () {
    $user = createTestMember();
    $post = createPublishedPost();

    // Initially no like
    $this->assertDatabaseCount('blog_post_likes', 0);

    // First toggle - add like
    $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
    $this->assertDatabaseHas('blog_post_likes', [
        'blog_post_id' => $post->id,
        'user_id' => $user->id,
    ]);

    // Second toggle - remove like
    $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
    $this->assertDatabaseMissing('blog_post_likes', [
        'blog_post_id' => $post->id,
        'user_id' => $user->id,
    ]);
    $this->assertDatabaseCount('blog_post_likes', 0);
});

test('toggling like multiple times works correctly', function () {
    $user = createTestMember();
    $post = createPublishedPost();

    // Like
    $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
    expect(BlogPostLike::where('blog_post_id', $post->id)->where('user_id', $user->id)->exists())->toBeTrue();

    // Unlike
    $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
    expect(BlogPostLike::where('blog_post_id', $post->id)->where('user_id', $user->id)->exists())->toBeFalse();

    // Like again
    $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
    expect(BlogPostLike::where('blog_post_id', $post->id)->where('user_id', $user->id)->exists())->toBeTrue();

    // Unlike again
    $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
    expect(BlogPostLike::where('blog_post_id', $post->id)->where('user_id', $user->id)->exists())->toBeFalse();
});

test('unliking a post does not affect other users likes', function () {
    $user1 = createTestMember();
    $user2 = createTestMember();
    $post = createPublishedPost();

    // Both users like the post
    BlogPostLike::create(['blog_post_id' => $post->id, 'user_id' => $user1->id]);
    BlogPostLike::create(['blog_post_id' => $post->id, 'user_id' => $user2->id]);

    $this->assertDatabaseCount('blog_post_likes', 2);

    // User 1 unlikes
    $this->actingAs($user1)->post(route('blog.like.toggle', $post->slug));

    // User 1's like is gone, but user 2's remains
    $this->assertDatabaseMissing('blog_post_likes', [
        'blog_post_id' => $post->id,
        'user_id' => $user1->id,
    ]);
    
    $this->assertDatabaseHas('blog_post_likes', [
        'blog_post_id' => $post->id,
        'user_id' => $user2->id,
    ]);

    $this->assertDatabaseCount('blog_post_likes', 1);
});

test('unliking one post does not affect likes on other posts', function () {
    $user = createTestMember();
    $author = createTestAdmin();
    $post1 = createPublishedPost(['user_id' => $author->id]);
    $post2 = createPublishedPost(['user_id' => $author->id]);

    // Like both posts
    BlogPostLike::create(['blog_post_id' => $post1->id, 'user_id' => $user->id]);
    BlogPostLike::create(['blog_post_id' => $post2->id, 'user_id' => $user->id]);

    $this->assertDatabaseCount('blog_post_likes', 2);

    // Unlike post 1
    $this->actingAs($user)->post(route('blog.like.toggle', $post1->slug));

    // Post 1 like is gone, post 2 like remains
    $this->assertDatabaseMissing('blog_post_likes', [
        'blog_post_id' => $post1->id,
        'user_id' => $user->id,
    ]);
    
    $this->assertDatabaseHas('blog_post_likes', [
        'blog_post_id' => $post2->id,
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseCount('blog_post_likes', 1);
});

// Database Constraints Tests
test('database prevents duplicate likes from same user on same post', function () {
    $user = createTestMember();
    $post = createPublishedPost();

    // Create first like
    BlogPostLike::create([
        'blog_post_id' => $post->id,
        'user_id' => $user->id,
    ]);

    // Attempting to create duplicate should throw exception
    expect(fn() => BlogPostLike::create([
        'blog_post_id' => $post->id,
        'user_id' => $user->id,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

// Relationships Tests
test('blog post like belongs to a blog post', function () {
    $post = createPublishedPost();
    $like = BlogPostLike::factory()->create([
        'blog_post_id' => $post->id,
    ]);

    expect($like->blogPost->id)->toBe($post->id);
});

test('blog post like belongs to a user', function () {
    $user = createTestMember();
    $like = BlogPostLike::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($like->user->id)->toBe($user->id);
});

// Cascade Deletion Tests
test('deleting a blog post deletes all its likes', function () {
    $user1 = createTestMember();
    $user2 = createTestMember();
    $post = createPublishedPost();

    // Create multiple likes
    BlogPostLike::create(['blog_post_id' => $post->id, 'user_id' => $user1->id]);
    BlogPostLike::create(['blog_post_id' => $post->id, 'user_id' => $user2->id]);

    $this->assertDatabaseCount('blog_post_likes', 2);

    // Delete the post
    $post->delete();

    // All likes should be deleted
    $this->assertDatabaseCount('blog_post_likes', 0);
});

test('deleting a user deletes all their likes', function () {
    $user = createTestMember();
    $author = createTestAdmin();
    $post1 = createPublishedPost(['user_id' => $author->id]);
    $post2 = createPublishedPost(['user_id' => $author->id]);

    // User likes multiple posts
    BlogPostLike::create(['blog_post_id' => $post1->id, 'user_id' => $user->id]);
    BlogPostLike::create(['blog_post_id' => $post2->id, 'user_id' => $user->id]);

    $this->assertDatabaseCount('blog_post_likes', 2);

    // Delete the user
    $user->delete();

    // All their likes should be deleted
    $this->assertDatabaseCount('blog_post_likes', 0);
});

// Timestamp Tests
test('likes are timestamped correctly', function () {
    $user = createTestMember();
    $post = createPublishedPost();

    $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));

    $like = BlogPostLike::where('blog_post_id', $post->id)
        ->where('user_id', $user->id)
        ->first();

    expect($like->created_at)->not->toBeNull();
    expect($like->updated_at)->not->toBeNull();
});

// Edge Cases Tests
test('admin users can like blog posts', function () {
    $admin = createTestAdmin();
    $post = createPublishedPost();

    $response = $this->actingAs($admin)->post(route('blog.like.toggle', $post->slug));

    $response->assertRedirect();
    $this->assertDatabaseHas('blog_post_likes', [
        'blog_post_id' => $post->id,
        'user_id' => $admin->id,
    ]);
});

test('master admin users can like blog posts', function () {
    $masterAdmin = createTestMasterAdmin();
    $post = createPublishedPost();

    $response = $this->actingAs($masterAdmin)->post(route('blog.like.toggle', $post->slug));

    $response->assertRedirect();
    $this->assertDatabaseHas('blog_post_likes', [
        'blog_post_id' => $post->id,
        'user_id' => $masterAdmin->id,
    ]);
});

test('member users can like blog posts', function () {
    $member = createTestMember();
    $post = createPublishedPost();

    $response = $this->actingAs($member)->post(route('blog.like.toggle', $post->slug));

    $response->assertRedirect();
    $this->assertDatabaseHas('blog_post_likes', [
        'blog_post_id' => $post->id,
        'user_id' => $member->id,
    ]);
});

test('toggling like on featured posts works correctly', function () {
    $user = createTestMember();
    $author = createTestAdmin();
    $post = createPublishedPost([
        'user_id' => $author->id,
        'is_featured' => true,
    ]);

    // Like
    $response = $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
    $response->assertSessionHas('success', 'Post liked');
    
    // Unlike
    $response = $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
    $response->assertSessionHas('success', 'Like removed');
});

test('can query likes count for a blog post', function () {
    $post = createPublishedPost();
    
    $users = User::factory()->count(5)->create();
    
    foreach ($users as $user) {
        BlogPostLike::create([
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }

    $likesCount = BlogPostLike::where('blog_post_id', $post->id)->count();
    
    expect($likesCount)->toBe(5);
});

test('can check if specific user liked a post', function () {
    $user = createTestMember();
    $otherUser = createTestMember();
    $post = createPublishedPost();

    // User likes the post
    BlogPostLike::create([
        'blog_post_id' => $post->id,
        'user_id' => $user->id,
    ]);

    $userLikedPost = BlogPostLike::where('blog_post_id', $post->id)
        ->where('user_id', $user->id)
        ->exists();
    
    $otherUserLikedPost = BlogPostLike::where('blog_post_id', $post->id)
        ->where('user_id', $otherUser->id)
        ->exists();

    expect($userLikedPost)->toBeTrue();
    expect($otherUserLikedPost)->toBeFalse();
});
