<?php

/**
 * User Follow Test Suite
 *
 * Tests the follow/unfollow functionality between users (readers following authors)
 * including toggle behavior, follower counting, and relationship management.
 */

use App\Models\User;

describe('Following Authors', function () {
    it('allows authenticated users to follow admin users (authors)', function () {
        $member = createTestMember();
        $author = createTestAdmin();

        $response = $this->actingAs($member)->post(route('user.follow.toggle', $author->id));

        expect($response)->toHaveSuccessMessage('Following successfully');
        expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();
    })->group('follows', 'toggle', 'authenticated');

    it('redirects guests attempting to follow authors', function () {
        $author = createTestAdmin();

        $response = $this->post(route('user.follow.toggle', $author->id));

        expect($response)->toRedirectToLogin();
    })->group('follows', 'toggle', 'guest');

    it('returns 404 for non-existent users', function () {
        $member = createTestMember();

        $response = $this->actingAs($member)->post(route('user.follow.toggle', 99999));

        expect($response)->toBeNotFound();
    })->group('follows', 'toggle', 'validation');

    it('prevents users from following themselves', function () {
        $author = createTestAdmin();

        $response = $this->actingAs($author)->post(route('user.follow.toggle', $author->id));

        expect($response)->toHaveErrorMessage('You cannot follow yourself');
        expect($author->following()->where('following_id', $author->id)->exists())->toBeFalse();
    })->group('follows', 'toggle', 'validation');

    it('prevents users from following member users (non-authors)', function () {
        $member1 = createTestMember(['email' => 'member1@test.com']);
        $member2 = createTestMember(['email' => 'member2@test.com']);

        $response = $this->actingAs($member1)->post(route('user.follow.toggle', $member2->id));

        expect($response)->toHaveErrorMessage('You can only follow authors');
        expect($member1->following()->where('following_id', $member2->id)->exists())->toBeFalse();
    })->group('follows', 'toggle', 'validation');

    it('allows users to follow master admin users', function () {
        $member = createTestMember();
        $masterAdmin = createTestMasterAdmin();

        $response = $this->actingAs($member)->post(route('user.follow.toggle', $masterAdmin->id));

        expect($response)->toHaveSuccessMessage('Following successfully');
        expect($member->following()->where('following_id', $masterAdmin->id)->exists())->toBeTrue();
    })->group('follows', 'toggle', 'authenticated');

    it('allows admin users to follow other admin users', function () {
        $author1 = createTestAdmin(['email' => 'author1@test.com']);
        $author2 = createTestAdmin(['email' => 'author2@test.com']);

        $response = $this->actingAs($author1)->post(route('user.follow.toggle', $author2->id));

        expect($response)->toHaveSuccessMessage('Following successfully');
        expect($author1->following()->where('following_id', $author2->id)->exists())->toBeTrue();
    })->group('follows', 'toggle', 'authenticated');

    it('allows members to follow multiple authors', function () {
        $member = createTestMember();
        $author1 = createTestAdmin(['email' => 'author1@test.com']);
        $author2 = createTestAdmin(['email' => 'author2@test.com']);
        $author3 = createTestAdmin(['email' => 'author3@test.com']);

        $this->actingAs($member)->post(route('user.follow.toggle', $author1->id));
        $this->actingAs($member)->post(route('user.follow.toggle', $author2->id));
        $this->actingAs($member)->post(route('user.follow.toggle', $author3->id));

        expect($member->following()->count())->toBe(3)
            ->and($member->following()->pluck('users.id')->toArray())->toContain($author1->id, $author2->id, $author3->id);
    })->group('follows', 'toggle', 'authenticated');

    it('allows authors to have multiple followers', function () {
        $author = createTestAdmin();
        $member1 = createTestMember(['email' => 'member1@test.com']);
        $member2 = createTestMember(['email' => 'member2@test.com']);
        $member3 = createTestMember(['email' => 'member3@test.com']);

        $member1->following()->attach($author->id);
        $member2->following()->attach($author->id);
        $member3->following()->attach($author->id);

        expect($author->followers()->count())->toBe(3)
            ->and($author->followers()->pluck('users.id')->toArray())->toContain($member1->id, $member2->id, $member3->id);
    })->group('follows', 'relationships');
});

describe('Unfollowing Authors', function () {
    it('allows authenticated users to unfollow authors they are following', function () {
        $member = createTestMember();
        $author = createTestAdmin();

        $member->following()->attach($author->id);
        expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();

        $response = $this->actingAs($member)->post(route('user.follow.toggle', $author->id));

        expect($response)->toHaveSuccessMessage('Unfollowed successfully');
        expect($member->following()->where('following_id', $author->id)->exists())->toBeFalse();
    })->group('follows', 'toggle', 'authenticated');

    it('toggles follow twice to return to original state', function () {
        $member = createTestMember();
        $author = createTestAdmin();

        expect($member->following()->where('following_id', $author->id)->exists())->toBeFalse();

        $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
        expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();

        $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
        expect($member->following()->where('following_id', $author->id)->exists())->toBeFalse();
    })->group('follows', 'toggle', 'authenticated');

    it('toggles follow multiple times correctly', function () {
        $member = createTestMember();
        $author = createTestAdmin();

        $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
        expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();

        $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
        expect($member->following()->where('following_id', $author->id)->exists())->toBeFalse();

        $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
        expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();

        $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
        expect($member->following()->where('following_id', $author->id)->exists())->toBeFalse();
    })->group('follows', 'toggle', 'authenticated');

    it('does not affect other users follows when unfollowing', function () {
        $member1 = createTestMember(['email' => 'member1@test.com']);
        $member2 = createTestMember(['email' => 'member2@test.com']);
        $author = createTestAdmin();

        $member1->following()->attach($author->id);
        $member2->following()->attach($author->id);

        expect($author->followers()->count())->toBe(2);

        $this->actingAs($member1)->post(route('user.follow.toggle', $author->id));

        expect($member1->following()->where('following_id', $author->id)->exists())->toBeFalse()
            ->and($member2->following()->where('following_id', $author->id)->exists())->toBeTrue()
            ->and($author->followers()->count())->toBe(1);
    })->group('follows', 'toggle', 'isolation');

    it('does not affect follows on other authors when unfollowing one', function () {
        $member = createTestMember();
        $author1 = createTestAdmin(['email' => 'author1@test.com']);
        $author2 = createTestAdmin(['email' => 'author2@test.com']);

        $member->following()->attach($author1->id);
        $member->following()->attach($author2->id);

        expect($member->following()->count())->toBe(2);

        $this->actingAs($member)->post(route('user.follow.toggle', $author1->id));

        expect($member->following()->where('following_id', $author1->id)->exists())->toBeFalse()
            ->and($member->following()->where('following_id', $author2->id)->exists())->toBeTrue()
            ->and($member->following()->count())->toBe(1);
    })->group('follows', 'toggle', 'isolation');

    it('clears the relationship completely', function () {
        $member = createTestMember();
        $author = createTestAdmin();

        $member->following()->attach($author->id);
        
        $this->assertDatabaseHas('user_follows', [
            'follower_id' => $member->id,
            'following_id' => $author->id,
        ]);

        $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
        
        $this->assertDatabaseMissing('user_follows', [
            'follower_id' => $member->id,
            'following_id' => $author->id,
        ]);
    })->group('follows', 'database');
});

describe('Follow Database Integrity', function () {
    it('prevents duplicate follows from same user on same author', function () {
        $member = createTestMember();
        $author = createTestAdmin();

        $member->following()->attach($author->id);

        expect(fn() => $member->following()->attach($author->id))
            ->toThrow(\Illuminate\Database\QueryException::class);
    })->group('follows', 'database', 'constraints');

    it('creates follows with timestamps', function () {
        $member = createTestMember();
        $author = createTestAdmin();

        $this->actingAs($member)->post(route('user.follow.toggle', $author->id));

        $follow = $member->following()->where('following_id', $author->id)->first();

        expect($follow->pivot->created_at)->not->toBeNull()
            ->and($follow->pivot->updated_at)->not->toBeNull();
    })->group('follows', 'database', 'timestamps');
});

describe('Follow Relationships', function () {
    it('returns correct users in following relationship', function () {
        $member = createTestMember();
        $author1 = createTestAdmin(['email' => 'author1@test.com']);
        $author2 = createTestAdmin(['email' => 'author2@test.com']);

        $member->following()->attach([$author1->id, $author2->id]);

        $following = $member->following;
        
        expect($following)->toHaveCount(2)
            ->and($following->pluck('id')->toArray())->toContain($author1->id, $author2->id);
    })->group('follows', 'relationships');

    it('returns correct users in followers relationship', function () {
        $author = createTestAdmin();
        $member1 = createTestMember(['email' => 'member1@test.com']);
        $member2 = createTestMember(['email' => 'member2@test.com']);

        $member1->following()->attach($author->id);
        $member2->following()->attach($author->id);

        $followers = $author->followers;
        
        expect($followers)->toHaveCount(2)
            ->and($followers->pluck('id')->toArray())->toContain($member1->id, $member2->id);
    })->group('follows', 'relationships');

    it('has inverse relationships between following and followers', function () {
        $member = createTestMember();
        $author = createTestAdmin();

        $member->following()->attach($author->id);

        expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue()
            ->and($author->followers()->where('follower_id', $member->id)->exists())->toBeTrue();
    })->group('follows', 'relationships');

    it('preserves user data in following relationship', function () {
        $member = createTestMember();
        $author = createTestAdmin([
            'name' => 'Test Author',
            'email' => 'author@test.com',
        ]);

        $member->following()->attach($author->id);

        $followedAuthor = $member->following()->first();
        
        expect($followedAuthor->name)->toBe('Test Author')
            ->and($followedAuthor->email)->toBe('author@test.com')
            ->and($followedAuthor->role)->toBe('admin');
    })->group('follows', 'relationships');

    it('preserves user data in followers relationship', function () {
        $author = createTestAdmin();
        $member = createTestMember([
            'name' => 'Test Member',
            'email' => 'member@test.com',
        ]);

        $member->following()->attach($author->id);

        $follower = $author->followers()->first();
        
        expect($follower->name)->toBe('Test Member')
            ->and($follower->email)->toBe('member@test.com')
            ->and($follower->role)->toBe('member');
    })->group('follows', 'relationships');
});

describe('Cascade Deletion', function () {
    it('removes all follows when user is deleted', function () {
        $member = createTestMember();
        $author1 = createTestAdmin(['email' => 'author1@test.com']);
        $author2 = createTestAdmin(['email' => 'author2@test.com']);

        $member->following()->attach([$author1->id, $author2->id]);

        expect($member->following()->count())->toBe(2);

        $member->delete();

        expect($author1->followers()->count())->toBe(0)
            ->and($author2->followers()->count())->toBe(0);
    })->group('follows', 'cascade', 'deletion');

    it('removes all followers when author is deleted', function () {
        $author = createTestAdmin();
        $member1 = createTestMember(['email' => 'member1@test.com']);
        $member2 = createTestMember(['email' => 'member2@test.com']);

        $member1->following()->attach($author->id);
        $member2->following()->attach($author->id);

        expect($author->followers()->count())->toBe(2);

        $author->delete();

        expect($member1->following()->count())->toBe(0)
            ->and($member2->following()->count())->toBe(0);
    })->group('follows', 'cascade', 'deletion');
});

describe('Follow Edge Cases', function () {
    it('allows members to follow admins', function () {
        $member = createTestMember();
        $admin = createTestAdmin();

        $response = $this->actingAs($member)->post(route('user.follow.toggle', $admin->id));

        expect($response)->toHaveSuccessMessage('Following successfully');
        expect($member->following()->where('following_id', $admin->id)->exists())->toBeTrue();
    })->group('follows', 'edge-cases', 'roles');

    it('denies admins from following members', function () {
        $admin = createTestAdmin();
        $member = createTestMember();

        $response = $this->actingAs($admin)->post(route('user.follow.toggle', $member->id));

        expect($response)->toHaveErrorMessage('You can only follow authors');
        expect($admin->following()->where('following_id', $member->id)->exists())->toBeFalse();
    })->group('follows', 'edge-cases', 'roles');

    it('denies master admins from following members', function () {
        $masterAdmin = createTestMasterAdmin();
        $member = createTestMember();

        $response = $this->actingAs($masterAdmin)->post(route('user.follow.toggle', $member->id));

        expect($response)->toHaveErrorMessage('You can only follow authors');
        expect($masterAdmin->following()->where('following_id', $member->id)->exists())->toBeFalse();
    })->group('follows', 'edge-cases', 'roles');

    it('allows users to follow and unfollow same author multiple times', function () {
        $member = createTestMember();
        $author = createTestAdmin();

        $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
        expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();

        $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
        expect($member->following()->where('following_id', $author->id)->exists())->toBeFalse();

        $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
        expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();

        expect($author->followers()->count())->toBe(1);
    })->group('follows', 'edge-cases');
});

describe('Follow Queries', function () {
    it('can query follower count for an author', function () {
        $author = User::factory()->admin()->create();
        $members = User::factory()->count(5)->create();
        
        foreach ($members as $member) {
            $member->following()->attach($author->id);
        }

        $followerCount = $author->followers()->count();
        
        expect($followerCount)->toBe(5);
    })->group('follows', 'queries');

    it('can query following count for a user', function () {
        $member = User::factory()->create();
        $authors = User::factory()->admin()->count(3)->create();
        
        foreach ($authors as $author) {
            $member->following()->attach($author->id);
        }

        $followingCount = $member->following()->count();
        
        expect($followingCount)->toBe(3);
    })->group('follows', 'queries');

    it('can check if specific user is following an author', function () {
        $member1 = createTestMember(['email' => 'member1@test.com']);
        $member2 = createTestMember(['email' => 'member2@test.com']);
        $author = createTestAdmin();

        $member1->following()->attach($author->id);

        $member1IsFollowing = $member1->following()->where('following_id', $author->id)->exists();
        $member2IsFollowing = $member2->following()->where('following_id', $author->id)->exists();

        expect($member1IsFollowing)->toBeTrue()
            ->and($member2IsFollowing)->toBeFalse();
    })->group('follows', 'queries');
});
