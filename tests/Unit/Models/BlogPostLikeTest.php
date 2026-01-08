<?php

use App\Models\BlogPost;
use App\Models\BlogPostLike;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

uses(Tests\TestCase::class);

/**
 * @group models
 * @group blog-post-like
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
        // Arrange: Create like in-memory
        $like = new BlogPostLike();
        
        // Act: Get relationship
        $relationship = $like->blogPost();
        
        // Assert: Correct relationship type
        expect($relationship)->toBeInstanceOf(BelongsTo::class);
    });

    test('has user relationship', function () {
        // Arrange: Create like in-memory
        $like = new BlogPostLike();
        
        // Act: Get relationship
        $relationship = $like->user();
        
        // Assert: Correct relationship type
        expect($relationship)->toBeInstanceOf(BelongsTo::class);
    });
});

// NOTE: Business logic tests that query the database should be moved to Feature tests.
// Commented out for now.
/*
describe('business logic', function () {
    test('can create like for blog post', function () {
        // Arrange: Data for new like
        $data = [
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ];
        
        // Act: Create like
        $like = BlogPostLike::create($data);
        
        // Assert: Like is created correctly
        expect($like)->toBeInstanceOf(BlogPostLike::class)
            ->and($like->blog_post_id)->toBe($this->post->id)
            ->and($like->user_id)->toBe($this->user->id);
        
        $this->assertDatabaseHas('blog_post_likes', $data);
    });

    test('belongs to correct user and post', function () {
        // Arrange: Create like
        $like = BlogPostLike::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
        
        // Act: Load relationships
        $like->load(['blogPost', 'user']);
        
        // Assert: Relationships are correct
        expect($like->blogPost->id)->toBe($this->post->id)
            ->and($like->user->id)->toBe($this->user->id)
            ->and($like->blogPost->title)->toBe($this->post->title)
            ->and($like->user->name)->toBe($this->user->name);
    });

    test('user can like multiple posts', function () {
        // Arrange: Create multiple posts
        $post1 = BlogPost::factory()->create(['user_id' => $this->user->id]);
        $post2 = BlogPost::factory()->create(['user_id' => $this->user->id]);
        $post3 = BlogPost::factory()->create(['user_id' => $this->user->id]);
        
        // Act: Like multiple posts
        BlogPostLike::create(['blog_post_id' => $post1->id, 'user_id' => $this->user->id]);
        BlogPostLike::create(['blog_post_id' => $post2->id, 'user_id' => $this->user->id]);
        BlogPostLike::create(['blog_post_id' => $post3->id, 'user_id' => $this->user->id]);
        
        // Assert: User has 3 likes
        $likeCount = BlogPostLike::where('user_id', $this->user->id)->count();
        expect($likeCount)->toBe(3);
        
        // Assert: Each post has 1 like
        expect(BlogPostLike::where('blog_post_id', $post1->id)->count())->toBe(1)
            ->and(BlogPostLike::where('blog_post_id', $post2->id)->count())->toBe(1)
            ->and(BlogPostLike::where('blog_post_id', $post3->id)->count())->toBe(1);
    });
});

describe('edge cases', function () {
    test('multiple users can like same post', function () {
        // Arrange: Create multiple users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        
        // Act: Multiple users like same post
        BlogPostLike::create(['blog_post_id' => $this->post->id, 'user_id' => $user1->id]);
        BlogPostLike::create(['blog_post_id' => $this->post->id, 'user_id' => $user2->id]);
        BlogPostLike::create(['blog_post_id' => $this->post->id, 'user_id' => $user3->id]);
        
        // Assert: Post has 3 likes
        $likeCount = BlogPostLike::where('blog_post_id', $this->post->id)->count();
        expect($likeCount)->toBe(3);
    });

    test('like has timestamps', function () {
        // Arrange & Act: Create like
        $like = BlogPostLike::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
        
        // Assert: Timestamps are set
        expect($like->created_at)->not->toBeNull()
            ->and($like->updated_at)->not->toBeNull();
    });

    test('can delete like', function () {
        // Arrange: Create like
        $like = BlogPostLike::factory()->create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
        
        $likeId = $like->id;
        
        // Act: Delete like
        $like->delete();
        
        // Assert: Like is deleted
        $this->assertDatabaseMissing('blog_post_likes', ['id' => $likeId]);
        expect(BlogPostLike::find($likeId))->toBeNull();
    });

    test('factory creates valid like', function () {
        // Act: Use factory to create like
        $like = BlogPostLike::factory()->create();
        
        // Assert: Like has all required fields
        expect($like)->toBeInstanceOf(BlogPostLike::class)
            ->and($like->blog_post_id)->not->toBeNull()
            ->and($like->user_id)->not->toBeNull()
            ->and($like->blogPost)->toBeInstanceOf(BlogPost::class)
            ->and($like->user)->toBeInstanceOf(User::class);
    });

    test('different user can like same post as another user', function () {
        // Arrange: Create another user
        $otherUser = User::factory()->create();
        
        // Act: Both users like same post
        $like1 = BlogPostLike::create([
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
        
        $like2 = BlogPostLike::create([
            'blog_post_id' => $this->post->id,
            'user_id' => $otherUser->id,
        ]);
        
        // Assert: Two separate likes exist
        expect($like1->id)->not->toBe($like2->id)
            ->and($like1->user_id)->toBe($this->user->id)
            ->and($like2->user_id)->toBe($otherUser->id)
            ->and($like1->blog_post_id)->toBe($like2->blog_post_id);
    });
});

describe('fillable attributes', function () {
    test('blog_post_id is fillable', function () {
        // Arrange: Data with blog_post_id
        $data = [
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ];
        
        // Act: Mass assign
        $like = BlogPostLike::create($data);
        
        // Assert: blog_post_id is assigned
        expect($like->blog_post_id)->toBe($this->post->id);
    });

    test('user_id is fillable', function () {
        // Arrange: Data with user_id
        $data = [
            'blog_post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ];
        
        // Act: Mass assign
        $like = BlogPostLike::create($data);
        
        // Assert: user_id is assigned
        expect($like->user_id)->toBe($this->user->id);
    });
});
*/