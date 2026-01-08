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
