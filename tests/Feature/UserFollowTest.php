<?php

/**
 * User Follow Test Suite
 *
 * Tests the follow/unfollow functionality between users (readers following authors)
 * including toggle behavior, follower counting, and relationship management.
 *
 * Test Categories:
 * - Following Authors: Creating follow relationships
 * - Unfollowing Authors: Removing follow relationships
 * - Toggle Behavior: Follow/unfollow in single action
 * - Follower Counts: Tracking followers and following
 * - Access Control: Authentication requirements
 * - Self-Follow Prevention: Users cannot follow themselves
 *
 * Features Tested:
 * - Authenticated users can follow admins (authors)
 * - Toggle behavior (follow/unfollow same endpoint)
 * - Follower/following counts
 * - Duplicate prevention
 * - Self-follow prevention
 * - Guest access prevention
 * - Non-existent user handling
 * - Success/error messages
 */

use App\Models\User;

// Following Authors Tests
test('authenticated users can follow admin users (authors)', function () {
    $member = createTestMember();
    $author = createTestAdmin();

    $response = $this->actingAs($member)->post(route('user.follow.toggle', $author->id));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Following successfully');
    
    expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();
});

test('guests cannot follow authors', function () {
    $author = createTestAdmin();

    $response = $this->post(route('user.follow.toggle', $author->id));

    $response->assertRedirect(route('login'));
});

test('users cannot follow non-existent users', function () {
    $member = createTestMember();

    $response = $this->actingAs($member)->post(route('user.follow.toggle', 99999));

    $response->assertNotFound(); // 404 - user doesn't exist
});

test('users cannot follow themselves', function () {
    $author = createTestAdmin();

    $response = $this->actingAs($author)->post(route('user.follow.toggle', $author->id));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'You cannot follow yourself'); // Business rule: self-follow prevention
    
    expect($author->following()->where('following_id', $author->id)->exists())->toBeFalse();
});

test('users cannot follow member users (non-authors)', function () {
    $member1 = createTestMember();
    $member2 = createTestMember();

    $response = $this->actingAs($member1)->post(route('user.follow.toggle', $member2->id));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'You can only follow authors'); // Only admin/master_admin can be followed
    
    expect($member1->following()->where('following_id', $member2->id)->exists())->toBeFalse();
});

test('users can follow master admin users', function () {
    $member = createTestMember();
    $masterAdmin = createTestMasterAdmin();

    $response = $this->actingAs($member)->post(route('user.follow.toggle', $masterAdmin->id));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Following successfully'); // Master admins are authors too
    
    expect($member->following()->where('following_id', $masterAdmin->id)->exists())->toBeTrue();
});

test('admin users can follow other admin users', function () {
    $author1 = createTestAdmin();
    $author2 = createTestAdmin();

    $response = $this->actingAs($author1)->post(route('user.follow.toggle', $author2->id));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Following successfully');
    
    expect($author1->following()->where('following_id', $author2->id)->exists())->toBeTrue();
});

test('member users can follow multiple authors', function () {
    $member = createTestMember();
    $author1 = createTestAdmin();
    $author2 = createTestAdmin();
    $author3 = createTestAdmin();

    $this->actingAs($member)->post(route('user.follow.toggle', $author1->id));
    $this->actingAs($member)->post(route('user.follow.toggle', $author2->id));
    $this->actingAs($member)->post(route('user.follow.toggle', $author3->id));

    expect($member->following()->count())->toBe(3);
    expect($member->following()->pluck('users.id')->toArray())->toContain($author1->id, $author2->id, $author3->id);
});

test('author can have multiple followers', function () {
    $author = createTestAdmin();
    $member1 = createTestMember();
    $member2 = createTestMember();
    $member3 = createTestMember();

    $member1->following()->attach($author->id);
    $member2->following()->attach($author->id);
    $member3->following()->attach($author->id);

    expect($author->followers()->count())->toBe(3);
    expect($author->followers()->pluck('users.id')->toArray())->toContain($member1->id, $member2->id, $member3->id);
});

// Unfollowing Authors Tests
test('authenticated users can unfollow authors they are following', function () {
    $member = createTestMember();
    $author = createTestAdmin();

    // First, follow the author
    $member->following()->attach($author->id);
    expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();

    // Now, unfollow the author
    $response = $this->actingAs($member)->post(route('user.follow.toggle', $author->id));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Unfollowed successfully');
    
    expect($member->following()->where('following_id', $author->id)->exists())->toBeFalse();
});

test('toggling follow twice returns to original state', function () {
    $member = createTestMember();
    $author = createTestAdmin();

    // Initially not following
    expect($member->following()->where('following_id', $author->id)->exists())->toBeFalse();

    // First toggle - follow
    $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
    expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();

    // Second toggle - unfollow
    $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
    expect($member->following()->where('following_id', $author->id)->exists())->toBeFalse();
});

test('toggling follow multiple times works correctly', function () {
    $member = createTestMember();
    $author = createTestAdmin();

    // Follow
    $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
    expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();

    // Unfollow
    $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
    expect($member->following()->where('following_id', $author->id)->exists())->toBeFalse();

    // Follow again
    $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
    expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();

    // Unfollow again
    $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
    expect($member->following()->where('following_id', $author->id)->exists())->toBeFalse();
});

test('unfollowing an author does not affect other users follows', function () {
    $member1 = createTestMember();
    $member2 = createTestMember();
    $author = createTestAdmin();

    // Both members follow the author
    $member1->following()->attach($author->id);
    $member2->following()->attach($author->id);

    expect($author->followers()->count())->toBe(2);

    // Member 1 unfollows
    $this->actingAs($member1)->post(route('user.follow.toggle', $author->id));

    // Member 1 is not following, but member 2 still is
    expect($member1->following()->where('following_id', $author->id)->exists())->toBeFalse();
    expect($member2->following()->where('following_id', $author->id)->exists())->toBeTrue();
    expect($author->followers()->count())->toBe(1);
});

test('unfollowing one author does not affect follows on other authors', function () {
    $member = createTestMember();
    $author1 = createTestAdmin();
    $author2 = createTestAdmin();

    // Follow both authors
    $member->following()->attach($author1->id);
    $member->following()->attach($author2->id);

    expect($member->following()->count())->toBe(2);

    // Unfollow author 1
    $this->actingAs($member)->post(route('user.follow.toggle', $author1->id));

    // Author 1 is unfollowed, author 2 is still followed
    expect($member->following()->where('following_id', $author1->id)->exists())->toBeFalse();
    expect($member->following()->where('following_id', $author2->id)->exists())->toBeTrue();
    expect($member->following()->count())->toBe(1);
});

// Database Constraints Tests
test('database prevents duplicate follows from same user on same author', function () {
    $member = createTestMember();
    $author = createTestAdmin();

    // Create first follow
    $member->following()->attach($author->id);

    // Attempting to create duplicate should throw exception
    expect(fn() => $member->following()->attach($author->id))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

// Relationships Tests
test('following relationship returns correct users', function () {
    $member = createTestMember();
    $author1 = createTestAdmin();
    $author2 = createTestAdmin();

    $member->following()->attach([$author1->id, $author2->id]);

    $following = $member->following;
    
    expect($following)->toHaveCount(2);
    expect($following->pluck('id')->toArray())->toContain($author1->id, $author2->id);
});

test('followers relationship returns correct users', function () {
    $author = createTestAdmin();
    $member1 = createTestMember();
    $member2 = createTestMember();

    $member1->following()->attach($author->id);
    $member2->following()->attach($author->id);

    $followers = $author->followers;
    
    expect($followers)->toHaveCount(2);
    expect($followers->pluck('id')->toArray())->toContain($member1->id, $member2->id);
});

test('following and followers relationships are inverse of each other', function () {
    $member = createTestMember();
    $author = createTestAdmin();

    $member->following()->attach($author->id);

    // Member is following author
    expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();
    
    // Author has member as follower
    expect($author->followers()->where('follower_id', $member->id)->exists())->toBeTrue();
});

// Cascade Deletion Tests
test('deleting a user removes all their follows', function () {
    $member = createTestMember();
    $author1 = createTestAdmin();
    $author2 = createTestAdmin();

    // Member follows multiple authors
    $member->following()->attach([$author1->id, $author2->id]);

    expect($member->following()->count())->toBe(2);

    // Delete the member
    $member->delete();

    // All their follows should be deleted
    expect($author1->followers()->count())->toBe(0);
    expect($author2->followers()->count())->toBe(0);
});

test('deleting a user removes all their followers', function () {
    $author = createTestAdmin();
    $member1 = createTestMember();
    $member2 = createTestMember();

    // Multiple members follow the author
    $member1->following()->attach($author->id);
    $member2->following()->attach($author->id);

    expect($author->followers()->count())->toBe(2);

    // Delete the author
    $author->delete();

    // All their followers relationships should be deleted
    expect($member1->following()->count())->toBe(0);
    expect($member2->following()->count())->toBe(0);
});

// Timestamp Tests
test('follows are timestamped correctly', function () {
    $member = createTestMember();
    $author = createTestAdmin();

    $this->actingAs($member)->post(route('user.follow.toggle', $author->id));

    $follow = $member->following()->where('following_id', $author->id)->first();

    expect($follow->pivot->created_at)->not->toBeNull();
    expect($follow->pivot->updated_at)->not->toBeNull();
});

// Edge Cases Tests
test('member users can follow admin users', function () {
    $member = createTestMember();
    $admin = createTestAdmin();

    $response = $this->actingAs($member)->post(route('user.follow.toggle', $admin->id));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Following successfully');
    
    expect($member->following()->where('following_id', $admin->id)->exists())->toBeTrue();
});

test('admin users cannot follow member users', function () {
    $admin = createTestAdmin();
    $member = createTestMember();

    $response = $this->actingAs($admin)->post(route('user.follow.toggle', $member->id));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'You can only follow authors');
    
    expect($admin->following()->where('following_id', $member->id)->exists())->toBeFalse();
});

test('master admin cannot follow member users', function () {
    $masterAdmin = createTestMasterAdmin();
    $member = createTestMember();

    $response = $this->actingAs($masterAdmin)->post(route('user.follow.toggle', $member->id));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'You can only follow authors');
    
    expect($masterAdmin->following()->where('following_id', $member->id)->exists())->toBeFalse();
});

test('can query follower count for an author', function () {
    $author = User::factory()->admin()->create();
    $members = User::factory()->count(5)->create();
    
    foreach ($members as $member) {
        $member->following()->attach($author->id);
    }

    $followerCount = $author->followers()->count();
    
    expect($followerCount)->toBe(5);
});

test('can query following count for a user', function () {
    $member = User::factory()->create();
    $authors = User::factory()->admin()->count(3)->create();
    
    foreach ($authors as $author) {
        $member->following()->attach($author->id);
    }

    $followingCount = $member->following()->count();
    
    expect($followingCount)->toBe(3);
});

test('can check if specific user is following an author', function () {
    $member1 = createTestMember();
    $member2 = createTestMember();
    $author = createTestAdmin();

    // Member 1 follows the author
    $member1->following()->attach($author->id);

    $member1IsFollowing = $member1->following()->where('following_id', $author->id)->exists();
    $member2IsFollowing = $member2->following()->where('following_id', $author->id)->exists();

    expect($member1IsFollowing)->toBeTrue();
    expect($member2IsFollowing)->toBeFalse();
});

test('following relationship preserves user data', function () {
    $member = createTestMember();
    $author = createTestAdmin([
        'name' => 'Test Author',
        'email' => 'author@test.com',
    ]);

    $member->following()->attach($author->id);

    $followedAuthor = $member->following()->first();
    
    expect($followedAuthor->name)->toBe('Test Author');
    expect($followedAuthor->email)->toBe('author@test.com');
    expect($followedAuthor->role)->toBe('admin');
});

test('followers relationship preserves user data', function () {
    $author = createTestAdmin();
    $member = createTestMember([
        'name' => 'Test Member',
        'email' => 'member@test.com',
    ]);

    $member->following()->attach($author->id);

    $follower = $author->followers()->first();
    
    expect($follower->name)->toBe('Test Member');
    expect($follower->email)->toBe('member@test.com');
    expect($follower->role)->toBe('member');
});

test('user can follow and unfollow same author multiple times over time', function () {
    $member = createTestMember();
    $author = createTestAdmin();

    // Follow, unfollow, follow pattern
    $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
    expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();

    $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
    expect($member->following()->where('following_id', $author->id)->exists())->toBeFalse();

    $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
    expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();

    // Final state should be following
    expect($author->followers()->count())->toBe(1);
});

test('unfollowing clears the relationship completely', function () {
    $member = createTestMember();
    $author = createTestAdmin();

    // Follow
    $member->following()->attach($author->id);
    
    $this->assertDatabaseHas('user_follows', [
        'follower_id' => $member->id,
        'following_id' => $author->id,
    ]);

    // Unfollow
    $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
    
    $this->assertDatabaseMissing('user_follows', [
        'follower_id' => $member->id,
        'following_id' => $author->id,
    ]);
});
