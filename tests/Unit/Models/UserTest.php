<?php

use App\Models\User;
use App\Models\BlogPost;

uses(Tests\TestCase::class);

describe('role helper methods', function () {
    beforeEach(function () {
        // Create users in-memory for role tests
        $this->masterAdmin = new User(['role' => 'master_admin']);
        $this->admin = new User(['role' => 'admin']);
        $this->member = new User(['role' => 'member']);
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
})->group('models', 'user', 'unit');

describe('relationship methods', function () {
    it('has following relationship', function () {
        // Arrange: Create a user in-memory
        $user = new User();
        
        // Act: Get the following relationship
        $relationship = $user->following();
        
        // Assert: Should return a BelongsToMany relationship
        expect($relationship)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    it('has followers relationship', function () {
        // Arrange: Create a user in-memory
        $user = new User();
        
        // Act: Get the followers relationship
        $relationship = $user->followers();
        
        // Assert: Should return a BelongsToMany relationship
        expect($relationship)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });

    it('has blog posts relationship', function () {
        // Arrange: Create a user in-memory
        $user = new User();
        
        // Act: Get the blog posts relationship
        $relationship = $user->blogPosts();
        
        // Assert: Should return a HasMany relationship
        expect($relationship)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    it('following relationship uses correct pivot table and columns', function () {
        // Arrange: Create a user in-memory
        $user = new User();
        
        // Act: Get the following relationship
        $relationship = $user->following();
        
        // Assert: Should use correct pivot table and keys
        expect($relationship->getTable())->toBe('user_follows')
            ->and($relationship->getForeignPivotKeyName())->toBe('follower_id')
            ->and($relationship->getRelatedPivotKeyName())->toBe('following_id');
    });

    it('followers relationship uses correct pivot table and columns', function () {
        // Arrange: Create a user in-memory
        $user = new User();
        
        // Act: Get the followers relationship
        $relationship = $user->followers();
        
        // Assert: Should use correct pivot table and keys
        expect($relationship->getTable())->toBe('user_follows')
            ->and($relationship->getForeignPivotKeyName())->toBe('following_id')
            ->and($relationship->getRelatedPivotKeyName())->toBe('follower_id');
    });
})->group('models', 'user', 'relationships', 'unit');

describe('role combinations', function () {
    it('correctly differentiates all three roles', function () {
        // Arrange: Create users with each role in-memory
        $masterAdmin = new User(['role' => 'master_admin']);
        $admin = new User(['role' => 'admin']);
        $member = new User(['role' => 'member']);
        
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
})->group('models', 'user', 'unit');
