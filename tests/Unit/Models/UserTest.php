<?php

use App\Models\User;
use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

describe('role helper methods', function () {
    beforeEach(function () {
        // Create users once for all role tests to avoid repeated database hits
        $this->masterAdmin = User::factory()->create(['role' => 'master_admin']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->member = User::factory()->create(['role' => 'member']);
    });

    it('identifies master admin role correctly', function () {
        // Act & Assert: Check if user is master admin
        expect($this->masterAdmin->isMasterAdmin())->toBeTrue();
    });

    it('identifies that admin is not master admin', function () {
        // Act & Assert: Regular admin should not be master admin
        expect($this->admin->isMasterAdmin())->toBeFalse();
    });

    it('identifies that member is not master admin', function () {
        // Act & Assert: Member should not be master admin
        expect($this->member->isMasterAdmin())->toBeFalse();
    });

    it('identifies admin role includes master admin', function () {
        // Act & Assert: Master admin should return true for isAdmin()
        expect($this->masterAdmin->isAdmin())->toBeTrue();
    });

    it('identifies admin role correctly', function () {
        // Act & Assert: Admin should return true for isAdmin()
        expect($this->admin->isAdmin())->toBeTrue();
    });

    it('identifies that member is not admin', function () {
        // Act & Assert: Member should not be admin
        expect($this->member->isAdmin())->toBeFalse();
    });

    it('identifies regular admin role correctly', function () {
        // Act & Assert: Admin should return true for isRegularAdmin()
        expect($this->admin->isRegularAdmin())->toBeTrue();
    });

    it('identifies that master admin is not regular admin', function () {
        // Act & Assert: Master admin should not be regular admin
        expect($this->masterAdmin->isRegularAdmin())->toBeFalse();
    });

    it('identifies member role correctly', function () {
        // Act & Assert: Member should return true for isMember()
        expect($this->member->isMember())->toBeTrue();
    });

    it('identifies that admin is not member', function () {
        // Act & Assert: Admin should not be member
        expect($this->admin->isMember())->toBeFalse();
    });

    it('identifies that master admin is not member', function () {
        // Act & Assert: Master admin should not be member
        expect($this->masterAdmin->isMember())->toBeFalse();
    });
});

describe('relationship methods', function () {
    it('has following relationship', function () {
        // Arrange: Create a user
        $user = User::factory()->create();
        
        // Act: Get the following relationship
        $relationship = $user->following();
        
        // Assert: Should return a BelongsToMany relationship
        expect($relationship)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    it('has followers relationship', function () {
        // Arrange: Create a user
        $user = User::factory()->create();
        
        // Act: Get the followers relationship
        $relationship = $user->followers();
        
        // Assert: Should return a BelongsToMany relationship
        expect($relationship)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    it('can follow another user', function () {
        // Arrange: Create two users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Act: User 1 follows user 2
        $user1->following()->attach($user2->id);
        
        // Assert: User 1 should be following user 2 (use query instead of eager loading)
        expect($user1->following()->count())->toBe(1);
        expect($user1->following()->first()->id)->toBe($user2->id);
    });

    it('can have followers', function () {
        // Arrange: Create two users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Act: User 2 follows user 1
        $user2->following()->attach($user1->id);
        
        // Assert: User 1 should have 1 follower (use query instead of eager loading)
        expect($user1->followers()->count())->toBe(1);
        expect($user1->followers()->first()->id)->toBe($user2->id);
    });

    it('has blog posts relationship', function () {
        // Arrange: Create a user
        $user = User::factory()->create();
        
        // Act: Get the blog posts relationship
        $relationship = $user->blogPosts();
        
        // Assert: Should return a HasMany relationship
        expect($relationship)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    it('can have multiple blog posts', function () {
        // Arrange: Create a user with blog posts
        $user = User::factory()->create();
        BlogPost::factory()->count(3)->create(['user_id' => $user->id]);
        
        // Act: Get blog posts count
        $count = $user->blogPosts()->count();
        
        // Assert: Should have 3 blog posts
        expect($count)->toBe(3);
    });

    it('can have published blog posts', function () {
        // Arrange: Create a user with published and unpublished posts
        $user = User::factory()->create();
        BlogPost::factory()->count(2)->create([
            'user_id' => $user->id,
            'is_published' => true,
            'published_at' => now(),
        ]);
        BlogPost::factory()->count(1)->create([
            'user_id' => $user->id,
            'is_published' => false,
            'published_at' => null,
        ]);
        
        // Act: Get published posts count
        $publishedCount = $user->blogPosts()->where('is_published', true)->count();
        
        // Assert: Should have 2 published posts
        expect($publishedCount)->toBe(2);
    });

    it('following relationship uses correct pivot table and columns', function () {
        // Arrange: Create a user
        $user = User::factory()->create();
        
        // Act: Get the following relationship
        $relationship = $user->following();
        
        // Assert: Should use correct pivot table
        expect($relationship->getTable())->toBe('user_follows');
        expect($relationship->getForeignPivotKeyName())->toBe('follower_id');
        expect($relationship->getRelatedPivotKeyName())->toBe('following_id');
    });

    it('followers relationship uses correct pivot table and columns', function () {
        // Arrange: Create a user
        $user = User::factory()->create();
        
        // Act: Get the followers relationship
        $relationship = $user->followers();
        
        // Assert: Should use correct pivot table
        expect($relationship->getTable())->toBe('user_follows');
        expect($relationship->getForeignPivotKeyName())->toBe('following_id');
        expect($relationship->getRelatedPivotKeyName())->toBe('follower_id');
    });
});

describe('role combinations', function () {
    it('correctly differentiates all three roles', function () {
        // Arrange: Create users with each role (reuse from single creation)
        $masterAdmin = User::factory()->make(['role' => 'master_admin']);
        $admin = User::factory()->make(['role' => 'admin']);
        $member = User::factory()->make(['role' => 'member']);
        
        // Assert: Master admin checks
        expect($masterAdmin->isMasterAdmin())->toBeTrue();
        expect($masterAdmin->isAdmin())->toBeTrue();
        expect($masterAdmin->isRegularAdmin())->toBeFalse();
        expect($masterAdmin->isMember())->toBeFalse();
        
        // Assert: Admin checks
        expect($admin->isMasterAdmin())->toBeFalse();
        expect($admin->isAdmin())->toBeTrue();
        expect($admin->isRegularAdmin())->toBeTrue();
        expect($admin->isMember())->toBeFalse();
        
        // Assert: Member checks
        expect($member->isMasterAdmin())->toBeFalse();
        expect($member->isAdmin())->toBeFalse();
        expect($member->isRegularAdmin())->toBeFalse();
        expect($member->isMember())->toBeTrue();
    });
});

describe('edge cases', function () {
    it('handles empty following relationship', function () {
        // Arrange: Create user with no following
        $user = User::factory()->create();
        
        // Act: Get following count
        $count = $user->following()->count();
        
        // Assert: Should have 0 following
        expect($count)->toBe(0);
    });

    it('handles empty followers relationship', function () {
        // Arrange: Create user with no followers
        $user = User::factory()->create();
        
        // Act: Get followers count
        $count = $user->followers()->count();
        
        // Assert: Should have 0 followers
        expect($count)->toBe(0);
    });

    it('handles user with no blog posts', function () {
        // Arrange: Create user with no blog posts
        $user = User::factory()->create();
        
        // Act: Get blog posts count
        $count = $user->blogPosts()->count();
        
        // Assert: Should have 0 blog posts
        expect($count)->toBe(0);
    });
});
