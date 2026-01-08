<?php

/**
 * Comment System Test Suite
 *
 * Tests the complete comment functionality including creating, replying to,
 * editing, and deleting comments on blog posts.
 */

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\User;

describe('Comment Creation', function () {
    beforeEach(function () {
        $this->user = createTestMember();
        $this->post = createPublishedPost();
    });
    
    afterEach(function () {
        // Ensure cleanup between tests
        Comment::query()->delete();
    });

    it('allows authenticated users to add comments to published blog posts', function () {
        $response = authenticatedPost($this->user, route('comments.store', $this->post->slug), [
            'content' => 'This is a test comment.',
        ]);

        expect($response)
            ->toHaveSuccessMessage('Comment added successfully!')
            ->and($this->assertDatabaseHas('comments', [
                'blog_post_id' => $this->post->id,
                'user_id' => $this->user->id,
                'content' => 'This is a test comment.',
                'parent_id' => null,
            ]));
    })->group('comments', 'creation', 'authenticated');

    it('redirects guests to login when attempting to comment', function () {
        $response = $this->post(route('comments.store', $this->post->slug), [
            'content' => 'This is a test comment.',
        ]);

        expect($response)->toRedirectToLogin();
        $this->assertDatabaseCount('comments', 0);
    })->group('comments', 'creation', 'guest');

    it('allows multiple users to comment on the same post', function () {
        $user1 = createTestMember();
        $user2 = createTestMember();

        authenticatedPost($user1, route('comments.store', $this->post->slug), [
            'content' => 'Comment from user 1',
        ]);

        authenticatedPost($user2, route('comments.store', $this->post->slug), [
            'content' => 'Comment from user 2',
        ]);

        $this->assertDatabaseCount('comments', 2);
        $this->assertDatabaseHas('comments', [
            'user_id' => $user1->id,
            'content' => 'Comment from user 1',
        ]);
        $this->assertDatabaseHas('comments', [
            'user_id' => $user2->id,
            'content' => 'Comment from user 2',
        ]);
    })->group('comments', 'creation');

    it('allows same user to add multiple comments to the same post', function () {
        authenticatedPost($this->user, route('comments.store', $this->post->slug), [
            'content' => 'First comment',
        ]);

        authenticatedPost($this->user, route('comments.store', $this->post->slug), [
            'content' => 'Second comment',
        ]);

        $this->assertDatabaseCount('comments', 2);
        expect(Comment::where('user_id', $this->user->id)->count())->toBe(2);
    })->group('comments', 'creation');

    it('returns 404 when adding comment to non-existent blog post', function () {
        $response = authenticatedPost($this->user, route('comments.store', 'non-existent-slug'), [
            'content' => 'This is a test comment.',
        ]);

        expect($response)->toBeNotFound();
    })->group('comments', 'creation', 'edge-cases');
});

describe('Comment Validation', function () {
    beforeEach(function () {
        $this->user = createTestMember();
        $this->post = createPublishedPost();
    });

    it('validates comment content constraints', function (string $content, bool $shouldFail) {
        $response = authenticatedPost($this->user, route('comments.store', $this->post->slug), [
            'content' => $content,
        ]);

        if ($shouldFail) {
            expect($response)->toHaveValidationError('content');
            $this->assertDatabaseCount('comments', 0);
        } else {
            $response->assertRedirect();
            $this->assertDatabaseHas('comments', ['content' => $content]);
        }
    })->with([
        'empty content fails' => ['', true],
        'content exceeding max length fails' => [str_repeat('a', 1001), true],
        'content at max length succeeds' => [str_repeat('a', 1000), false],
    ])->group('comments', 'validation');

    it('preserves whitespace and formatting in comment content', function () {
        $content = "Line 1\n\nLine 2\n\nLine 3";

        $response = authenticatedPost($this->user, route('comments.store', $this->post->slug), [
            'content' => $content,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'content' => $content,
        ]);
    })->group('comments', 'validation', 'formatting');

    it('preserves special characters in comments', function () {
        $content = 'Test with special chars: <script>alert("xss")</script> & "quotes"';

        $response = authenticatedPost($this->user, route('comments.store', $this->post->slug), [
            'content' => $content,
        ]);

        $response->assertRedirect();
        $comment = Comment::latest()->first();
        expect($comment->content)->toBe($content);
    })->group('comments', 'validation', 'security');
});

describe('Comment Replies', function () {
    beforeEach(function () {
        $this->user = createTestMember();
        $this->post = createPublishedPost();
        $this->parentComment = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
        ]);
    });

    it('allows authenticated users to reply to comments', function () {
        $response = authenticatedPost($this->user, route('comments.store', $this->post->slug), [
            'content' => 'This is a reply.',
            'parent_id' => $this->parentComment->id, // Creates nested comment thread
        ]);

        expect($response)
            ->toHaveSuccessMessage('Reply added successfully!')
            ->and($this->assertDatabaseHas('comments', [
                'blog_post_id' => $this->post->id,
                'user_id' => $this->user->id,
                'parent_id' => $this->parentComment->id,
                'content' => 'This is a reply.',
            ]));
    })->group('comments', 'replies', 'authenticated');

    it('rejects reply with invalid parent comment id', function () {
        $response = authenticatedPost($this->user, route('comments.store', $this->post->slug), [
            'content' => 'This is a reply.',
            'parent_id' => TEST_NONEXISTENT_ID, // Non-existent comment ID
        ]);

        expect($response)->toHaveValidationError('parent_id');
    })->group('comments', 'replies', 'validation');

    it('rejects reply when parent comment belongs to different blog post', function () {
        $post2 = createPublishedPost();
        $commentOnPost2 = Comment::factory()->create([
            'blog_post_id' => $post2->id, // Parent comment is on a different post
        ]);

        $response = authenticatedPost($this->user, route('comments.store', $this->post->slug), [
            'content' => 'This is a reply.',
            'parent_id' => $commentOnPost2->id,
        ]);

        $response->assertStatus(400);
    })->group('comments', 'replies', 'validation');

    it('allows users to reply to their own comments', function () {
        $comment = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $response = authenticatedPost($this->user, route('comments.store', $this->post->slug), [
            'content' => 'Replying to my own comment.',
            'parent_id' => $comment->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'parent_id' => $comment->id,
            'user_id' => $this->user->id,
            'content' => 'Replying to my own comment.',
        ]);
    })->group('comments', 'replies');

    it('supports nested replies', function () {
        // Create first reply
        $firstReply = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'parent_id' => $this->parentComment->id,
        ]);

        // Create reply to the reply
        $response = authenticatedPost($this->user, route('comments.store', $this->post->slug), [
            'content' => 'Nested reply',
            'parent_id' => $firstReply->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'parent_id' => $firstReply->id,
            'content' => 'Nested reply',
        ]);
    })->group('comments', 'replies', 'nested');

    it('correctly identifies replies through isReply method', function () {
        $replyComment = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'parent_id' => $this->parentComment->id,
        ]);

        expect($this->parentComment->isReply())->toBeFalse()
            ->and($replyComment->isReply())->toBeTrue();
    })->group('comments', 'replies', 'model');

    it('loads replies through relationship', function () {
        Comment::factory()->count(3)->create([
            'blog_post_id' => $this->post->id,
            'parent_id' => $this->parentComment->id,
        ]);

        $this->parentComment->load('replies');
        
        expect($this->parentComment->replies)->toHaveCount(3);
    })->group('comments', 'replies', 'relationships');
});

describe('Comment Deletion', function () {
    beforeEach(function () {
        $this->user = createTestMember();
        $this->post = createPublishedPost();
    });

    it('allows users to delete their own comments', function () {
        $comment = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $response = authenticatedDelete($this->user, route('comments.destroy', $comment));

        expect($response)->toHaveSuccessMessage('Comment deleted successfully!');
        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    })->group('comments', 'deletion', 'authorized');

    it('denies users from deleting other users comments', function () {
        $user1 = createTestMember();
        $user2 = createTestMember();
        
        $comment = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $user2->id,
        ]);

        $response = authenticatedDelete($user1, route('comments.destroy', $comment));

        expect($response)->toBeForbidden();
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
        ]);
    })->group('comments', 'deletion', 'unauthorized');

    it('allows admins to delete any comment', function ($adminFactory) {
        $comment = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $admin = is_callable($adminFactory) ? $adminFactory() : $adminFactory;
        $response = authenticatedDelete($admin, route('comments.destroy', $comment));

        expect($response)->toHaveSuccessMessage('Comment deleted successfully!');
        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    })->with('admin_roles')->group('comments', 'deletion', 'admin');

    it('redirects guests when attempting to delete comments', function () {
        $comment = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
        ]);

        $response = $this->delete(route('comments.destroy', $comment));

        expect($response)->toRedirectToLogin();
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
        ]);
    })->group('comments', 'deletion', 'guest');

    it('removes comment when deleting parent with replies', function () {
        $parentComment = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        // Create replies
        Comment::factory()->count(2)->create([
            'blog_post_id' => $this->post->id,
            'parent_id' => $parentComment->id,
        ]);

        $this->assertDatabaseCount('comments', 3);

        $response = authenticatedDelete($this->user, route('comments.destroy', $parentComment));

        $response->assertRedirect();
        $this->assertDatabaseMissing('comments', [
            'id' => $parentComment->id,
        ]);
        
        // Note: Depending on cascade settings, replies might also be deleted
        // This test just verifies the parent is deleted
    })->group('comments', 'deletion', 'cascading');

    it('allows users to delete their replies', function () {
        $parentComment = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
        ]);

        $reply = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'parent_id' => $parentComment->id,
            'user_id' => $this->user->id,
        ]);

        $response = authenticatedDelete($this->user, route('comments.destroy', $reply));

        $response->assertRedirect();
        $this->assertDatabaseMissing('comments', [
            'id' => $reply->id,
        ]);
        
        // Parent comment should still exist
        $this->assertDatabaseHas('comments', [
            'id' => $parentComment->id,
        ]);
    })->group('comments', 'deletion', 'replies');
});

describe('Comment Relationships', function () {
    it('belongs to a blog post', function () {
        $post = createPublishedPost();
        $comment = Comment::factory()->create([
            'blog_post_id' => $post->id,
        ]);

        expect($comment->blogPost->id)->toBe($post->id);
    })->group('comments', 'relationships', 'model');

    it('belongs to a user', function () {
        $user = createTestMember();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
        ]);

        expect($comment->user->id)->toBe($user->id);
    })->group('comments', 'relationships', 'model');

    it('has parent relationship for replies', function () {
        $post = createPublishedPost();
        
        $parentComment = Comment::factory()->create([
            'blog_post_id' => $post->id,
        ]);

        $reply = Comment::factory()->create([
            'blog_post_id' => $post->id,
            'parent_id' => $parentComment->id,
        ]);

        expect($reply->parent->id)->toBe($parentComment->id);
    })->group('comments', 'relationships', 'model');

    it('has no parent for top-level comments', function () {
        $comment = Comment::factory()->create([
            'parent_id' => null,
        ]);

        expect($comment->parent)->toBeNull();
    })->group('comments', 'relationships', 'model');

    it('creates timestamps correctly', function () {
        $user = createTestMember();
        $post = createPublishedPost();

        authenticatedPost($user, route('comments.store', $post->slug), [
            'content' => 'Test comment',
        ]);

        $comment = Comment::latest()->first();
        expect($comment->created_at)->not->toBeNull()
            ->and($comment->updated_at)->not->toBeNull();
    })->group('comments', 'timestamps');
});
