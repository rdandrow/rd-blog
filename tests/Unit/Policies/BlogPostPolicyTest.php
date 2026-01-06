<?php

use App\Models\BlogPost;
use App\Models\User;
use App\Policies\BlogPostPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

describe('viewAny method', function () {
    it('allows all authenticated users to view any posts', function () {
        // Arrange: Create a user and policy
        $user = User::factory()->make();
        $policy = new BlogPostPolicy();
        
        // Act: Check viewAny authorization
        $result = $policy->viewAny($user);
        
        // Assert: All authenticated users can view any posts
        expect($result)->toBeTrue();
    });

    it('allows users with different roles to view any posts', function () {
        // Arrange: Create users with different roles
        $masterAdmin = User::factory()->make(['role' => 'master_admin']);
        $admin = User::factory()->make(['role' => 'admin']);
        $member = User::factory()->make(['role' => 'member']);
        $policy = new BlogPostPolicy();
        
        // Act & Assert: All roles can view any posts
        expect($policy->viewAny($masterAdmin))->toBeTrue();
        expect($policy->viewAny($admin))->toBeTrue();
        expect($policy->viewAny($member))->toBeTrue();
    });
});

describe('view method', function () {
    it('allows all authenticated users to view individual posts', function () {
        // Arrange: Create a user, blog post, and policy
        $user = User::factory()->make();
        $post = BlogPost::factory()->make();
        $policy = new BlogPostPolicy();
        
        // Act: Check view authorization
        $result = $policy->view($user, $post);
        
        // Assert: All authenticated users can view individual posts
        expect($result)->toBeTrue();
    });

    it('allows users to view posts they do not own', function () {
        // Arrange: Create user and post owned by different user
        $user = User::factory()->make(['id' => 1]);
        $post = BlogPost::factory()->make(['user_id' => 2]);
        $policy = new BlogPostPolicy();
        
        // Act: Check view authorization
        $result = $policy->view($user, $post);
        
        // Assert: Users can view posts they don't own
        expect($result)->toBeTrue();
    });

    it('allows users to view their own posts', function () {
        // Arrange: Create user and their own post
        $user = User::factory()->make(['id' => 1]);
        $post = BlogPost::factory()->make(['user_id' => 1]);
        $policy = new BlogPostPolicy();
        
        // Act: Check view authorization
        $result = $policy->view($user, $post);
        
        // Assert: Users can view their own posts
        expect($result)->toBeTrue();
    });
});

describe('create method', function () {
    it('allows all authenticated users to create posts', function () {
        // Arrange: Create a user and policy
        $user = User::factory()->make();
        $policy = new BlogPostPolicy();
        
        // Act: Check create authorization
        $result = $policy->create($user);
        
        // Assert: All authenticated users can create posts
        expect($result)->toBeTrue();
    });

    it('allows users with different roles to create posts', function () {
        // Arrange: Create users with different roles
        $masterAdmin = User::factory()->make(['role' => 'master_admin']);
        $admin = User::factory()->make(['role' => 'admin']);
        $member = User::factory()->make(['role' => 'member']);
        $policy = new BlogPostPolicy();
        
        // Act & Assert: All roles can create posts
        expect($policy->create($masterAdmin))->toBeTrue();
        expect($policy->create($admin))->toBeTrue();
        expect($policy->create($member))->toBeTrue();
    });
});

describe('update method', function () {
    it('allows post author to update their post', function () {
        // Arrange: Create user and their post
        $user = User::factory()->make(['id' => 1]);
        $post = BlogPost::factory()->make(['user_id' => 1]);
        $policy = new BlogPostPolicy();
        
        // Act: Check update authorization
        $result = $policy->update($user, $post);
        
        // Assert: Author can update their post
        expect($result)->toBeTrue();
    });

    it('denies non-authors from updating posts', function () {
        // Arrange: Create user and post owned by different user
        $user = User::factory()->make(['id' => 2]);
        $post = BlogPost::factory()->make(['user_id' => 1]);
        $policy = new BlogPostPolicy();
        
        // Act: Check update authorization
        $result = $policy->update($user, $post);
        
        // Assert: Non-author cannot update the post
        expect($result)->toBeFalse();
    });

    it('denies admins from updating posts they do not own', function () {
        // Arrange: Create admin user and post owned by member
        $admin = User::factory()->make(['id' => 2, 'role' => 'admin']);
        $post = BlogPost::factory()->make(['user_id' => 1]);
        $policy = new BlogPostPolicy();
        
        // Act: Check update authorization
        $result = $policy->update($admin, $post);
        
        // Assert: Even admins cannot update posts they don't own
        expect($result)->toBeFalse();
    });

    it('denies master admins from updating posts they do not own', function () {
        // Arrange: Create master admin user and post owned by member
        $masterAdmin = User::factory()->make(['id' => 2, 'role' => 'master_admin']);
        $post = BlogPost::factory()->make(['user_id' => 1]);
        $policy = new BlogPostPolicy();
        
        // Act: Check update authorization
        $result = $policy->update($masterAdmin, $post);
        
        // Assert: Even master admins cannot update posts they don't own
        expect($result)->toBeFalse();
    });
});

describe('delete method', function () {
    it('allows post author to delete their post', function () {
        // Arrange: Create user and their post
        $user = User::factory()->make(['id' => 1]);
        $post = BlogPost::factory()->make(['user_id' => 1]);
        $policy = new BlogPostPolicy();
        
        // Act: Check delete authorization
        $result = $policy->delete($user, $post);
        
        // Assert: Author can delete their post
        expect($result)->toBeTrue();
    });

    it('denies non-authors from deleting posts', function () {
        // Arrange: Create user and post owned by different user
        $user = User::factory()->make(['id' => 2]);
        $post = BlogPost::factory()->make(['user_id' => 1]);
        $policy = new BlogPostPolicy();
        
        // Act: Check delete authorization
        $result = $policy->delete($user, $post);
        
        // Assert: Non-author cannot delete the post
        expect($result)->toBeFalse();
    });

    it('denies admins from deleting posts they do not own', function () {
        // Arrange: Create admin user and post owned by member
        $admin = User::factory()->make(['id' => 2, 'role' => 'admin']);
        $post = BlogPost::factory()->make(['user_id' => 1]);
        $policy = new BlogPostPolicy();
        
        // Act: Check delete authorization
        $result = $policy->delete($admin, $post);
        
        // Assert: Even admins cannot delete posts they don't own
        expect($result)->toBeFalse();
    });

    it('denies master admins from deleting posts they do not own', function () {
        // Arrange: Create master admin user and post owned by member
        $masterAdmin = User::factory()->make(['id' => 2, 'role' => 'master_admin']);
        $post = BlogPost::factory()->make(['user_id' => 1]);
        $policy = new BlogPostPolicy();
        
        // Act: Check delete authorization
        $result = $policy->delete($masterAdmin, $post);
        
        // Assert: Even master admins cannot delete posts they don't own
        expect($result)->toBeFalse();
    });
});

describe('restore method', function () {
    it('denies all users from restoring posts', function () {
        // Arrange: Create user and post
        $user = User::factory()->make(['id' => 1]);
        $post = BlogPost::factory()->make(['user_id' => 1]);
        $policy = new BlogPostPolicy();
        
        // Act: Check restore authorization
        $result = $policy->restore($user, $post);
        
        // Assert: No users can restore posts (even post owner)
        expect($result)->toBeFalse();
    });

    it('denies post author from restoring their own post', function () {
        // Arrange: Create user and their post
        $user = User::factory()->make(['id' => 1]);
        $post = BlogPost::factory()->make(['user_id' => 1]);
        $policy = new BlogPostPolicy();
        
        // Act: Check restore authorization
        $result = $policy->restore($user, $post);
        
        // Assert: Even author cannot restore
        expect($result)->toBeFalse();
    });

    it('denies admins from restoring posts', function () {
        // Arrange: Create admin user and post
        $admin = User::factory()->make(['role' => 'admin']);
        $post = BlogPost::factory()->make();
        $policy = new BlogPostPolicy();
        
        // Act: Check restore authorization
        $result = $policy->restore($admin, $post);
        
        // Assert: Admins cannot restore posts
        expect($result)->toBeFalse();
    });

    it('denies master admins from restoring posts', function () {
        // Arrange: Create master admin user and post
        $masterAdmin = User::factory()->make(['role' => 'master_admin']);
        $post = BlogPost::factory()->make();
        $policy = new BlogPostPolicy();
        
        // Act: Check restore authorization
        $result = $policy->restore($masterAdmin, $post);
        
        // Assert: Master admins cannot restore posts
        expect($result)->toBeFalse();
    });
});

describe('forceDelete method', function () {
    it('denies all users from force deleting posts', function () {
        // Arrange: Create user and post
        $user = User::factory()->make(['id' => 1]);
        $post = BlogPost::factory()->make(['user_id' => 1]);
        $policy = new BlogPostPolicy();
        
        // Act: Check forceDelete authorization
        $result = $policy->forceDelete($user, $post);
        
        // Assert: No users can force delete posts (even post owner)
        expect($result)->toBeFalse();
    });

    it('denies post author from force deleting their own post', function () {
        // Arrange: Create user and their post
        $user = User::factory()->make(['id' => 1]);
        $post = BlogPost::factory()->make(['user_id' => 1]);
        $policy = new BlogPostPolicy();
        
        // Act: Check forceDelete authorization
        $result = $policy->forceDelete($user, $post);
        
        // Assert: Even author cannot force delete
        expect($result)->toBeFalse();
    });

    it('denies admins from force deleting posts', function () {
        // Arrange: Create admin user and post
        $admin = User::factory()->make(['role' => 'admin']);
        $post = BlogPost::factory()->make();
        $policy = new BlogPostPolicy();
        
        // Act: Check forceDelete authorization
        $result = $policy->forceDelete($admin, $post);
        
        // Assert: Admins cannot force delete posts
        expect($result)->toBeFalse();
    });

    it('denies master admins from force deleting posts', function () {
        // Arrange: Create master admin user and post
        $masterAdmin = User::factory()->make(['role' => 'master_admin']);
        $post = BlogPost::factory()->make();
        $policy = new BlogPostPolicy();
        
        // Act: Check forceDelete authorization
        $result = $policy->forceDelete($masterAdmin, $post);
        
        // Assert: Master admins cannot force delete posts
        expect($result)->toBeFalse();
    });
});

describe('authorization matrix', function () {
    it('correctly applies authorization rules across all methods', function () {
        // Arrange: Create author, non-author, and posts
        $author = User::factory()->make(['id' => 1]);
        $nonAuthor = User::factory()->make(['id' => 2]);
        $authorPost = BlogPost::factory()->make(['user_id' => 1]);
        $policy = new BlogPostPolicy();
        
        // Assert: Author permissions on their own post
        expect($policy->viewAny($author))->toBeTrue();
        expect($policy->view($author, $authorPost))->toBeTrue();
        expect($policy->create($author))->toBeTrue();
        expect($policy->update($author, $authorPost))->toBeTrue();
        expect($policy->delete($author, $authorPost))->toBeTrue();
        expect($policy->restore($author, $authorPost))->toBeFalse();
        expect($policy->forceDelete($author, $authorPost))->toBeFalse();
        
        // Assert: Non-author permissions on someone else's post
        expect($policy->viewAny($nonAuthor))->toBeTrue();
        expect($policy->view($nonAuthor, $authorPost))->toBeTrue();
        expect($policy->create($nonAuthor))->toBeTrue();
        expect($policy->update($nonAuthor, $authorPost))->toBeFalse();
        expect($policy->delete($nonAuthor, $authorPost))->toBeFalse();
        expect($policy->restore($nonAuthor, $authorPost))->toBeFalse();
        expect($policy->forceDelete($nonAuthor, $authorPost))->toBeFalse();
    });
});
