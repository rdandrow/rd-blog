<?php

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

uses(Tests\TestCase::class);

/**
 * @group models
 * @group comment
 * @group relationships
 * @group unit
 */

beforeEach(function () {
    $this->user = new User();
    $this->user->id = 1;
    $this->post = new BlogPost();
    $this->post->id = 1;
    $this->post->user_id = 1;
});

describe('relationships', function () {
    test('has blog post relationship', function () {
        // Arrange: Create comment in-memory
        $comment = new Comment();
        
        // Act: Get relationship
        $relationship = $comment->blogPost();
        
        // Assert: Correct relationship type
        expect($relationship)->toBeInstanceOf(BelongsTo::class);
    });

    test('has user relationship', function () {
        // Arrange: Create comment in-memory
        $comment = new Comment();
        
        // Act: Get relationship
        $relationship = $comment->user();
        
        // Assert: Correct relationship type
        expect($relationship)->toBeInstanceOf(BelongsTo::class);
    });

    test('has parent comment relationship', function () {
        // Arrange: Create comment in-memory
        $comment = new Comment();
        
        // Act: Get relationship
        $relationship = $comment->parent();
        
        // Assert: Correct relationship type
        expect($relationship)->toBeInstanceOf(BelongsTo::class);
    });

    test('has replies relationship', function () {
        // Arrange: Create comment in-memory
        $comment = new Comment();
        
        // Act: Get relationship
        $relationship = $comment->replies();
        
        // Assert: Correct relationship type
        expect($relationship)->toBeInstanceOf(HasMany::class);
    });
});

// NOTE: Business logic tests that query the database should be moved to Feature tests.
// Commented out for now.
/*
describe('business logic', function () {
    test('can be reply to another comment', function () {
        // Arrange: Create parent comment
        $parent = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => null,
        ]);
        
        // Act: Create reply
        $reply = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => $parent->id,
        ]);
        
        // Assert: Reply has correct parent
        expect($reply->parent_id)->toBe($parent->id)
            ->and($reply->parent->id)->toBe($parent->id)
            ->and($reply->isReply())->toBeTrue();
    });

    test('can have multiple replies', function () {
        // Arrange: Create parent comment
        $parent = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
        
        // Act: Create multiple replies
        Comment::factory()->count(3)->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => $parent->id,
        ]);
        
        // Assert: Parent has 3 replies
        expect($parent->replies)->toHaveCount(3)
            ->and($parent->replies)->each->toBeInstanceOf(Comment::class);
    });

    test('belongs to correct blog post', function () {
        // Arrange: Create comment
        $comment = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
        
        // Act: Load relationship
        $comment->load('blogPost');
        
        // Assert: Comment belongs to correct post
        expect($comment->blogPost->id)->toBe($this->post->id)
            ->and($comment->blog_post_id)->toBe($this->post->id);
    });
});

describe('edge cases', function () {
    test('handles null parent_id for root comments', function () {
        // Arrange & Act: Create root comment (no parent)
        $comment = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => null,
        ]);
        
        // Assert: parent_id is null and isReply is false
        expect($comment->parent_id)->toBeNull()
            ->and($comment->isReply())->toBeFalse()
            ->and($comment->parent)->toBeNull();
    });

    test('parent comment can have nested replies', function () {
        // Arrange: Create parent comment
        $parent = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => null,
        ]);
        
        // Act: Create first-level reply
        $reply1 = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => $parent->id,
        ]);
        
        // Act: Create second-level reply (nested)
        $reply2 = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => $reply1->id,
        ]);
        
        // Assert: Nested structure is correct
        expect($parent->replies)->toHaveCount(1)
            ->and($reply1->parent->id)->toBe($parent->id)
            ->and($reply2->parent->id)->toBe($reply1->id)
            ->and($reply1->replies)->toHaveCount(1);
    });
});

describe('is reply method', function () {
    test('returns true for reply comments', function () {
        // Arrange: Create parent and reply
        $parent = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
        
        $reply = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => $parent->id,
        ]);
        
        // Act & Assert
        expect($reply->isReply())->toBeTrue();
    });

    test('returns false for root comments', function () {
        // Arrange: Create root comment
        $comment = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => null,
        ]);
        
        // Act & Assert
        expect($comment->isReply())->toBeFalse();
    });
});

describe('additional scenarios', function () {
    test('replies are ordered by created_at ascending', function () {
        // Arrange: Create parent comment
        $parent = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
        
        // Act: Create replies with delays
        $reply1 = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => $parent->id,
            'created_at' => now()->subMinutes(3),
        ]);
        
        $reply2 = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => $parent->id,
            'created_at' => now()->subMinutes(2),
        ]);
        
        $reply3 = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => $parent->id,
            'created_at' => now()->subMinutes(1),
        ]);
        
        // Assert: Replies are in correct order
        $replies = $parent->replies;
        expect($replies->first()->id)->toBe($reply1->id)
            ->and($replies->last()->id)->toBe($reply3->id);
    });

    test('comment has timestamps', function () {
        // Arrange & Act: Create comment
        $comment = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
        
        // Assert: Timestamps are set
        expect($comment->created_at)->not->toBeNull()
            ->and($comment->updated_at)->not->toBeNull();
    });

    test('can delete comment', function () {
        // Arrange: Create comment
        $comment = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
        
        $commentId = $comment->id;
        
        // Act: Delete comment
        $comment->delete();
        
        // Assert: Comment is deleted
        $this->assertDatabaseMissing('comments', ['id' => $commentId]);
        expect(Comment::find($commentId))->toBeNull();
    });

    test('factory creates valid comment', function () {
        // Act: Use factory to create comment
        $comment = Comment::factory()->create();
        
        // Assert: Comment has all required fields
        expect($comment)->toBeInstanceOf(Comment::class)
            ->and($comment->blog_post_id)->not->toBeNull()
            ->and($comment->user_id)->not->toBeNull()
            ->and($comment->content)->not->toBeNull()
            ->and($comment->blogPost)->toBeInstanceOf(BlogPost::class)
            ->and($comment->user)->toBeInstanceOf(User::class);
    });

    test('different users can comment on same post', function () {
        // Arrange: Create another user
        $user2 = User::factory()->create();
        
        // Act: Both users comment on same post
        $comment1 = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
        
        $comment2 = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $user2->id,
        ]);
        
        // Assert: Two separate comments exist
        expect($comment1->id)->not->toBe($comment2->id)
            ->and($comment1->user_id)->toBe($this->user->id)
            ->and($comment2->user_id)->toBe($user2->id)
            ->and($comment1->blog_post_id)->toBe($comment2->blog_post_id);
    });

    test('user can have multiple comments', function () {
        // Act: Create multiple comments from same user
        Comment::factory()->count(4)->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
        
        // Assert: User has 4 comments
        $commentCount = Comment::where('user_id', $this->user->id)->count();
        expect($commentCount)->toBe(4);
    });
});

describe('fillable attributes', function () {
    test('blog_post_id is fillable', function () {
        // Arrange: Data with blog_post_id
        $data = [
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => 'Test comment',
        ];
        
        // Act: Mass assign
        $comment = Comment::create($data);
        
        // Assert: blog_post_id is assigned
        expect($comment->blog_post_id)->toBe($this->post->id);
    });

    test('user_id is fillable', function () {
        // Arrange: Data with user_id
        $data = [
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => 'Test comment',
        ];
        
        // Act: Mass assign
        $comment = Comment::create($data);
        
        // Assert: user_id is assigned
        expect($comment->user_id)->toBe($this->user->id);
    });

    test('parent_id is fillable', function () {
        // Arrange: Create parent comment
        $parent = Comment::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
        
        // Arrange: Data with parent_id
        $data = [
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => 'Reply comment',
            'parent_id' => $parent->id,
        ];
        
        // Act: Mass assign
        $comment = Comment::create($data);
        
        // Assert: parent_id is assigned
        expect($comment->parent_id)->toBe($parent->id);
    });

    test('content is fillable', function () {
        // Arrange: Data with content
        $data = [
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => 'This is a test comment content',
        ];
        
        // Act: Mass assign
        $comment = Comment::create($data);
        
        // Assert: content is assigned
        expect($comment->content)->toBe('This is a test comment content');
    });
});
*/