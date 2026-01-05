<?php

/**
 * Blog Post Like Test Suite
 *
 * Tests the like/unlike functionality for blog posts including toggle behavior,
 * like counting, and access control.
 */

use App\Models\BlogPost;
use App\Models\BlogPostLike;
use App\Models\User;

describe('Liking Blog Posts', function () {
    it('allows authenticated users to like a blog post', function () {
        $user = createTestMember();
        $post = createPublishedPost();

        $response = $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));

        expect($response)->toHaveSuccessMessage('Post liked');
        $this->assertDatabaseHas('blog_post_likes', [
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    })->group('likes', 'toggle', 'authenticated');

    it('denies guests from liking blog posts', function () {
        $post = createPublishedPost();

        $response = $this->post(route('blog.like.toggle', $post->slug));

        expect($response)->toRedirectToLogin();
        $this->assertDatabaseCount('blog_post_likes', 0);
    })->group('likes', 'toggle', 'guest');

    it('returns 404 for non-existent blog posts', function () {
        $user = createTestMember();

        $response = $this->actingAs($user)->post(route('blog.like.toggle', 'non-existent-slug'));

        expect($response)->toBeNotFound();
    })->group('likes', 'toggle', 'validation');

    it('allows users to like their own blog posts', function () {
        $author = createTestAdmin();
        $post = createPublishedPost(['user_id' => $author->id]);

        $response = $this->actingAs($author)->post(route('blog.like.toggle', $post->slug));

        expect($response)->toHaveSuccessMessage('Post liked');
        $this->assertDatabaseHas('blog_post_likes', [
            'blog_post_id' => $post->id,
            'user_id' => $author->id,
        ]);
    })->group('likes', 'toggle', 'authenticated');

    it('allows multiple users to like the same blog post', function () {
        $user1 = createTestMember(['email' => 'user1@test.com']);
        $user2 = createTestMember(['email' => 'user2@test.com']);
        $user3 = createTestMember(['email' => 'user3@test.com']);
        $post = createPublishedPost();

        $this->actingAs($user1)->post(route('blog.like.toggle', $post->slug));
        $this->actingAs($user2)->post(route('blog.like.toggle', $post->slug));
        $this->actingAs($user3)->post(route('blog.like.toggle', $post->slug));

        $this->assertDatabaseCount('blog_post_likes', 3);
        expect(BlogPostLike::where('blog_post_id', $post->id)->count())->toBe(3);
    })->group('likes', 'toggle', 'authenticated');

    it('allows users to like multiple blog posts', function () {
        $user = createTestMember();
        $author = createTestAdmin();
        $post1 = createPublishedPost(['user_id' => $author->id, 'title' => 'Post 1']);
        $post2 = createPublishedPost(['user_id' => $author->id, 'title' => 'Post 2']);
        $post3 = createPublishedPost(['user_id' => $author->id, 'title' => 'Post 3']);

        $this->actingAs($user)->post(route('blog.like.toggle', $post1->slug));
        $this->actingAs($user)->post(route('blog.like.toggle', $post2->slug));
        $this->actingAs($user)->post(route('blog.like.toggle', $post3->slug));

        $this->assertDatabaseCount('blog_post_likes', 3);
        expect(BlogPostLike::where('user_id', $user->id)->count())->toBe(3);
    })->group('likes', 'toggle', 'authenticated');

    it('allows users to like both published and draft posts', function () {
        $user = createTestMember();
        $author = createTestAdmin();
        $publishedPost = createPublishedPost(['user_id' => $author->id, 'title' => 'Published']);
        $draftPost = createDraftPost(['user_id' => $author->id, 'title' => 'Draft']);

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
    })->group('likes', 'toggle', 'authenticated');
});

describe('Unliking Blog Posts', function () {
    it('allows authenticated users to unlike previously liked posts', function () {
        $user = createTestMember();
        $post = createPublishedPost();

        BlogPostLike::create([
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('blog_post_likes', [
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));

        expect($response)->toHaveSuccessMessage('Like removed');
        $this->assertDatabaseMissing('blog_post_likes', [
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    })->group('likes', 'toggle', 'authenticated');

    it('toggles like twice to return to original state', function () {
        $user = createTestMember();
        $post = createPublishedPost();

        $this->assertDatabaseCount('blog_post_likes', 0);

        $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
        $this->assertDatabaseHas('blog_post_likes', [
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
        $this->assertDatabaseMissing('blog_post_likes', [
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseCount('blog_post_likes', 0);
    })->group('likes', 'toggle', 'authenticated');

    it('toggles like multiple times correctly', function () {
        $user = createTestMember();
        $post = createPublishedPost();

        $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
        expect(BlogPostLike::where('blog_post_id', $post->id)->where('user_id', $user->id)->exists())->toBeTrue();

        $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
        expect(BlogPostLike::where('blog_post_id', $post->id)->where('user_id', $user->id)->exists())->toBeFalse();

        $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
        expect(BlogPostLike::where('blog_post_id', $post->id)->where('user_id', $user->id)->exists())->toBeTrue();

        $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
        expect(BlogPostLike::where('blog_post_id', $post->id)->where('user_id', $user->id)->exists())->toBeFalse();
    })->group('likes', 'toggle', 'authenticated');

    it('does not affect other users likes when unliking', function () {
        $user1 = createTestMember(['email' => 'user1@test.com']);
        $user2 = createTestMember(['email' => 'user2@test.com']);
        $post = createPublishedPost();

        BlogPostLike::create(['blog_post_id' => $post->id, 'user_id' => $user1->id]);
        BlogPostLike::create(['blog_post_id' => $post->id, 'user_id' => $user2->id]);

        $this->assertDatabaseCount('blog_post_likes', 2);

        $this->actingAs($user1)->post(route('blog.like.toggle', $post->slug));

        $this->assertDatabaseMissing('blog_post_likes', [
            'blog_post_id' => $post->id,
            'user_id' => $user1->id,
        ]);
        
        $this->assertDatabaseHas('blog_post_likes', [
            'blog_post_id' => $post->id,
            'user_id' => $user2->id,
        ]);

        $this->assertDatabaseCount('blog_post_likes', 1);
    })->group('likes', 'toggle', 'authenticated', 'isolation');

    it('does not affect likes on other posts when unliking one', function () {
        $user = createTestMember();
        $author = createTestAdmin();
        $post1 = createPublishedPost(['user_id' => $author->id, 'title' => 'Post 1']);
        $post2 = createPublishedPost(['user_id' => $author->id, 'title' => 'Post 2']);

        BlogPostLike::create(['blog_post_id' => $post1->id, 'user_id' => $user->id]);
        BlogPostLike::create(['blog_post_id' => $post2->id, 'user_id' => $user->id]);

        $this->assertDatabaseCount('blog_post_likes', 2);

        $this->actingAs($user)->post(route('blog.like.toggle', $post1->slug));

        $this->assertDatabaseMissing('blog_post_likes', [
            'blog_post_id' => $post1->id,
            'user_id' => $user->id,
        ]);
        
        $this->assertDatabaseHas('blog_post_likes', [
            'blog_post_id' => $post2->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('blog_post_likes', 1);
    })->group('likes', 'toggle', 'authenticated', 'isolation');
});

describe('Like Database Integrity', function () {
    it('prevents duplicate likes from same user on same post', function () {
        $user = createTestMember();
        $post = createPublishedPost();

        BlogPostLike::create([
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        expect(fn() => BlogPostLike::create([
            'blog_post_id' => $post->id,
            'user_id' => $user->id,
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    })->group('likes', 'database', 'constraints');

    it('creates likes with timestamps', function () {
        $user = createTestMember();
        $post = createPublishedPost();

        $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));

        $like = BlogPostLike::where('blog_post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        expect($like->created_at)->not->toBeNull()
            ->and($like->updated_at)->not->toBeNull();
    })->group('likes', 'database', 'timestamps');
});

describe('Like Relationships', function () {
    it('belongs to a blog post', function () {
        $post = createPublishedPost();
        $like = BlogPostLike::factory()->create([
            'blog_post_id' => $post->id,
        ]);

        expect($like->blogPost->id)->toBe($post->id);
    })->group('likes', 'relationships');

    it('belongs to a user', function () {
        $user = createTestMember();
        $like = BlogPostLike::factory()->create([
            'user_id' => $user->id,
        ]);

        expect($like->user->id)->toBe($user->id);
    })->group('likes', 'relationships');
});

describe('Cascade Deletion', function () {
    it('deletes all likes when blog post is deleted', function () {
        $user1 = createTestMember(['email' => 'user1@test.com']);
        $user2 = createTestMember(['email' => 'user2@test.com']);
        $post = createPublishedPost();

        BlogPostLike::create(['blog_post_id' => $post->id, 'user_id' => $user1->id]);
        BlogPostLike::create(['blog_post_id' => $post->id, 'user_id' => $user2->id]);

        $this->assertDatabaseCount('blog_post_likes', 2);

        $post->delete();

        $this->assertDatabaseCount('blog_post_likes', 0);
    })->group('likes', 'cascade', 'deletion');

    it('deletes all likes when user is deleted', function () {
        $user = createTestMember();
        $author = createTestAdmin();
        $post1 = createPublishedPost(['user_id' => $author->id, 'title' => 'Post 1']);
        $post2 = createPublishedPost(['user_id' => $author->id, 'title' => 'Post 2']);

        BlogPostLike::create(['blog_post_id' => $post1->id, 'user_id' => $user->id]);
        BlogPostLike::create(['blog_post_id' => $post2->id, 'user_id' => $user->id]);

        $this->assertDatabaseCount('blog_post_likes', 2);

        $user->delete();

        $this->assertDatabaseCount('blog_post_likes', 0);
    })->group('likes', 'cascade', 'deletion');
});

describe('Like Edge Cases', function () {
    it('allows admin users to like blog posts', function () {
        $admin = createTestAdmin();
        $post = createPublishedPost();

        $response = $this->actingAs($admin)->post(route('blog.like.toggle', $post->slug));

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_post_likes', [
            'blog_post_id' => $post->id,
            'user_id' => $admin->id,
        ]);
    })->group('likes', 'edge-cases', 'roles');

    it('allows master admin users to like blog posts', function () {
        $masterAdmin = createTestMasterAdmin();
        $post = createPublishedPost();

        $response = $this->actingAs($masterAdmin)->post(route('blog.like.toggle', $post->slug));

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_post_likes', [
            'blog_post_id' => $post->id,
            'user_id' => $masterAdmin->id,
        ]);
    })->group('likes', 'edge-cases', 'roles');

    it('allows member users to like blog posts', function () {
        $member = createTestMember();
        $post = createPublishedPost();

        $response = $this->actingAs($member)->post(route('blog.like.toggle', $post->slug));

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_post_likes', [
            'blog_post_id' => $post->id,
            'user_id' => $member->id,
        ]);
    })->group('likes', 'edge-cases', 'roles');

    it('toggles like on featured posts correctly', function () {
        $user = createTestMember();
        $author = createTestAdmin();
        $post = createPublishedPost([
            'user_id' => $author->id,
            'is_featured' => true,
        ]);

        $response = $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
        expect($response)->toHaveSuccessMessage('Post liked');
        
        $response = $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
        expect($response)->toHaveSuccessMessage('Like removed');
    })->group('likes', 'edge-cases', 'featured');
});

describe('Like Queries', function () {
    it('can query likes count for a blog post', function () {
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
    })->group('likes', 'queries');

    it('can check if specific user liked a post', function () {
        $user = createTestMember(['email' => 'user@test.com']);
        $otherUser = createTestMember(['email' => 'other@test.com']);
        $post = createPublishedPost();

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

        expect($userLikedPost)->toBeTrue()
            ->and($otherUserLikedPost)->toBeFalse();
    })->group('likes', 'queries');
});
