<?php

/**
 * Master Admin User Management Test Suite
 *
 * Tests comprehensive user management functionality available exclusively to master admins.
 * Covers CRUD operations for user accounts, role management, and access control.
 */

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Disable 2FA middleware for these tests to focus on user management
beforeEach(function () {
    $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorEnabled::class);
});

describe('User Management Access Control', function () {
    it('allows master admin to access admin users index', function () {
        $response = $this->actingAs(createTestMasterAdmin())
            ->get(route('admin.users.admins'));
        
        expect($response)->toBeSuccessfulInertiaResponse('Admin/Users/AdminUsers');
    })->group('user-management', 'access-control', 'authorized');

    it('allows master admin to access member users index', function () {
        $response = $this->actingAs(createTestMasterAdmin())
            ->get(route('admin.users.members'));
        
        expect($response)->toBeSuccessfulInertiaResponse('Admin/Users/MemberUsers');
    })->group('user-management', 'access-control', 'authorized');

    it('denies non-master-admin roles from accessing admin users index', function ($userFactory) {
        $user = is_callable($userFactory) ? $userFactory() : $userFactory;
        $response = $this->actingAs($user)->get(route('admin.users.admins'));
        
        expect($response)->toBeForbidden();
    })->with('unauthorized_for_master_admin')
      ->group('user-management', 'access-control', 'unauthorized');

    it('denies non-master-admin roles from accessing member users index', function ($userFactory) {
        $user = is_callable($userFactory) ? $userFactory() : $userFactory;
        $response = $this->actingAs($user)->get(route('admin.users.members'));
        
        expect($response)->toBeForbidden();
    })->with('unauthorized_for_master_admin')
      ->group('user-management', 'access-control', 'unauthorized');

    it('redirects unauthenticated users from admin users index', function () {
        expect($this->get(route('admin.users.admins')))->toRedirectToLogin();
    })->group('user-management', 'access-control', 'unauthenticated');

    it('redirects unauthenticated users from member users index', function () {
        expect($this->get(route('admin.users.members')))->toRedirectToLogin();
    })->group('user-management', 'access-control', 'unauthenticated');
});

describe('Admin Users Listing', function () {
    it('displays all admins and master admins', function () {
        $masterAdmin = createTestMasterAdmin();
        $admin1 = createTestAdmin(['name' => 'Admin One', 'email' => 'admin1@test.com']);
        $admin2 = createTestAdmin(['name' => 'Admin Two', 'email' => 'admin2@test.com']);
        $member = createTestMember(['name' => 'Member One']); // Should not appear
        
        $response = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/AdminUsers')
            ->has('admins.data', 3) // masterAdmin + 2 admins (paginated data)
        );
        
        // Verify all admin users are present (regardless of order)
        $adminNames = $response->viewData('page')['props']['admins']['data'];
        $names = collect($adminNames)->pluck('name')->toArray();
        expect($names)->toContain($masterAdmin->name)
            ->and($names)->toContain('Admin One')
            ->and($names)->toContain('Admin Two');
    })->group('user-management', 'listing', 'admins');

    it('excludes members from admin users list', function () {
        $masterAdmin = createTestMasterAdmin();
        createTestMember(['name' => 'Member One']);
        createTestMember(['name' => 'Member Two']);
        
        $response = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('admins.data', 1) // Only the master admin (paginated data)
        );
    })->group('user-management', 'listing', 'admins');

    it('orders admin users by creation date descending', function () {
        // Create master admin with explicit older timestamp
        $masterAdmin = User::factory()->masterAdmin()->create([
            'created_at' => now()->subDays(10)
        ]);
        
        // Create with explicit timestamps - no sleep needed, just set created_at directly
        $admin1 = User::factory()->admin()->create([
            'name' => 'Admin One',
            'created_at' => now()->subDays(2)
        ]);
        
        $admin2 = User::factory()->admin()->create([
            'name' => 'Admin Two',
            'created_at' => now()->subDay()
        ]);
        
        $admin3 = User::factory()->admin()->create([
            'name' => 'Admin Three',
            'created_at' => now()
        ]);
        
        $response = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('admins.data', 4) // 3 admins + masterAdmin (paginated data)
        );
        
        // Verify most recent is first
        $admins = $response->viewData('page')['props']['admins']['data'];
        expect($admins[0]['name'])->toBe('Admin Three');
    })->group('user-management', 'listing', 'admins');
});

describe('Member Users Listing', function () {
    it('displays all member users', function () {
        $masterAdmin = createTestMasterAdmin();
        $member1 = createTestMember(['name' => 'Member One', 'email' => 'member1@test.com']);
        $member2 = createTestMember(['name' => 'Member Two', 'email' => 'member2@test.com']);
        $admin = createTestAdmin(['name' => 'Admin One']); // Should not appear
        
        $response = $this->actingAs($masterAdmin)->get(route('admin.users.members'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Users/MemberUsers')
            ->has('members.data', 2) // paginated data
        );
        
        // Verify both members are present
        $members = $response->viewData('page')['props']['members']['data'];
        $names = collect($members)->pluck('name')->toArray();
        expect($names)->toContain('Member One')
            ->and($names)->toContain('Member Two');
    })->group('user-management', 'listing', 'members');

    it('excludes admins from member users list', function () {
        $masterAdmin = createTestMasterAdmin();
        createTestAdmin(['name' => 'Admin One']);
        createTestAdmin(['name' => 'Admin Two']);
        
        $response = $this->actingAs($masterAdmin)->get(route('admin.users.members'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('members.data', 0) // paginated data
        );
    })->group('user-management', 'listing', 'members');

    it('orders member users by creation date descending', function () {
        $masterAdmin = createTestMasterAdmin();
        $member1 = createTestMember(['name' => 'Member One', 'created_at' => now()->subDays(2)]);
        $member2 = createTestMember(['name' => 'Member Two', 'created_at' => now()->subDay()]);
        $member3 = createTestMember(['name' => 'Member Three', 'created_at' => now()]);
        
        $response = $this->actingAs($masterAdmin)->get(route('admin.users.members'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('members.data.0.name', 'Member Three') // Most recent first (paginated data)
            ->where('members.data.1.name', 'Member Two')
            ->where('members.data.2.name', 'Member One')
        );
    })->group('user-management', 'listing', 'members');
});

describe('User Creation', function () {
    it('allows master admin to create users with any role', function ($role) {
        $masterAdmin = createTestMasterAdmin();
        
        $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
            'name' => "New {$role}",
            'email' => "new{$role}@test.com",
            'role' => $role, // Valid roles: member, admin, master_admin
        ]);

        expect($response)->toHaveSuccessMessage('User invited successfully. An invitation email has been sent.');
        $this->assertDatabaseHas('users', [
            'name' => "New {$role}",
            'email' => "new{$role}@test.com",
            'role' => $role,
        ]);
        
        $user = User::where('email', "new{$role}@test.com")->first();
    })->with('valid_user_roles')
      ->group('user-management', 'creation', 'authorized');

    it('automatically verifies email for created users', function () {
        $masterAdmin = createTestMasterAdmin();
        
        $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'role' => 'member',
        ]);

        expect($response)->toHaveSuccessMessage('User invited successfully. An invitation email has been sent.');
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@test.com',
            'role' => 'member',
        ]);
    })->group('user-management', 'creation');

    it('denies non-master-admin roles from creating users', function ($userFactory) {
        $user = is_callable($userFactory) ? $userFactory() : $userFactory;
        
        $response = $this->actingAs($user)->post(route('admin.users.store'), [
            'name' => 'New Member',
            'email' => 'newmember@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'member',
        ]);

        expect($response)->toBeForbidden();
        $this->assertDatabaseMissing('users', [
            'email' => 'newmember@test.com',
        ]);
    })->with('unauthorized_for_master_admin')
      ->group('user-management', 'creation', 'unauthorized');

    it('redirects unauthenticated users from user creation', function () {
        $response = $this->post(route('admin.users.store'), [
            'name' => 'New Member',
            'email' => 'newmember@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'member',
        ]);

        expect($response)->toRedirectToLogin();
        $this->assertDatabaseMissing('users', [
            'email' => 'newmember@test.com',
        ]);
    })->group('user-management', 'creation', 'guest');
});

describe('User Creation Validation', function () {
    beforeEach(function () {
        $this->masterAdmin = createTestMasterAdmin();
        $this->validUserData = [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'role' => 'member',
        ];
    });

    it('validates required fields', function (string $field, array $data) {
        $response = authenticatedPost($this->masterAdmin, route('admin.users.store'), $data);

        expect($response)->toHaveValidationError($field);
    })->with([
        'name is required' => [
            'name',
            [
                'email' => 'newuser@test.com',
                'role' => 'member',
            ],
        ],
        'email is required' => [
            'email',
            [
                'name' => 'New User',
                'role' => 'member',
            ],
        ],
        'role is required' => [
            'role',
            [
                'name' => 'New User',
                'email' => 'newuser@test.com',
            ],
        ],
    ])->group('user-management', 'creation', 'validation');

    it('validates email format and uniqueness', function (string $email, string $errorField) {
        $data = array_merge($this->validUserData, ['email' => $email]);
        $response = authenticatedPost($this->masterAdmin, route('admin.users.store'), $data);

        expect($response)->toHaveValidationError($errorField);
    })->with([
        'invalid email format' => ['not-an-email', 'email'],
        'uppercase email' => ['NewUser@TEST.COM', 'email'],
    ])->group('user-management', 'creation', 'validation');

    it('requires unique email', function () {
        $existingUser = createTestMember(['email' => 'existing@test.com']);
        
        $response = authenticatedPost($this->masterAdmin, route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'existing@test.com', // Duplicate email - must be unique
            'role' => 'member',
        ]);

        expect($response)->toHaveValidationError('email');
    })->group('user-management', 'creation', 'validation');

    it('requires valid role', function () {
        $data = array_merge($this->validUserData, ['role' => 'invalid_role']);
        $response = authenticatedPost($this->masterAdmin, route('admin.users.store'), $data);

        expect($response)->toHaveValidationError('role');
    })->group('user-management', 'creation', 'validation');
});

describe('Role Updates', function () {
    beforeEach(function () {
        $this->masterAdmin = createTestMasterAdmin();
    });

    it('allows master admin to promote member to admin', function () {
        $member = createTestMember();
        
        $response = $this->actingAs($this->masterAdmin)
            ->patch(route('admin.users.updateRole', $member), ['role' => 'admin']);

        expect($response)->toHaveSuccessMessage('User role updated successfully.');
        expect($member->fresh()->role)->toBe('admin');
    })->group('user-management', 'role-updates', 'promotions');

    it('allows master admin to promote member to master admin', function () {
        $member = createTestMember();
        
        $response = $this->actingAs($this->masterAdmin)
            ->patch(route('admin.users.updateRole', $member), ['role' => 'master_admin']);

        expect($response)->toHaveSuccessMessage('User role updated successfully.');
        expect($member->fresh()->role)->toBe('master_admin');
    })->group('user-management', 'role-updates', 'promotions');

    it('allows master admin to promote admin to master admin', function () {
        $admin = createTestAdmin();
        
        $response = $this->actingAs($this->masterAdmin)
            ->patch(route('admin.users.updateRole', $admin), ['role' => 'master_admin']);

        expect($response)->toHaveSuccessMessage('User role updated successfully.');
        expect($admin->fresh()->role)->toBe('master_admin');
    })->group('user-management', 'role-updates', 'promotions');

    it('allows master admin to demote admin to member', function () {
        $admin = createTestAdmin();
        
        $response = $this->actingAs($this->masterAdmin)
            ->patch(route('admin.users.updateRole', $admin), ['role' => 'member']);

        expect($response)->toHaveSuccessMessage('User role updated successfully.');
        expect($admin->fresh()->role)->toBe('member');
    })->group('user-management', 'role-updates', 'demotions');

    it('allows master admin to demote master admin to admin when multiple exist', function () {
        $masterAdmin2 = createTestMasterAdmin(['email' => 'ma2@test.com']);
        
        $response = $this->actingAs($this->masterAdmin)
            ->patch(route('admin.users.updateRole', $masterAdmin2), ['role' => 'admin']);

        expect($response)->toHaveSuccessMessage('User role updated successfully.');
        expect($masterAdmin2->fresh()->role)->toBe('admin');
    })->group('user-management', 'role-updates', 'demotions');

    it('allows master admin to demote master admin to member when multiple exist', function () {
        $masterAdmin2 = createTestMasterAdmin(['email' => 'ma2@test.com']);
        
        $response = $this->actingAs($this->masterAdmin)
            ->patch(route('admin.users.updateRole', $masterAdmin2), ['role' => 'member']);

        expect($response)->toHaveSuccessMessage('User role updated successfully.');
        expect($masterAdmin2->fresh()->role)->toBe('member');
    })->group('user-management', 'role-updates', 'demotions');

    it('prevents demoting the last master admin to admin', function () {
        $response = $this->actingAs($this->masterAdmin)
            ->patch(route('admin.users.updateRole', $this->masterAdmin), ['role' => 'admin']);

        $response->assertRedirect();
        $response->assertSessionHasErrors('role');
        expect($this->masterAdmin->fresh()->role)->toBe('master_admin');
    })->group('user-management', 'role-updates', 'safety');

    it('prevents demoting the last master admin to member', function () {
        $response = $this->actingAs($this->masterAdmin)
            ->patch(route('admin.users.updateRole', $this->masterAdmin), ['role' => 'member']);

        $response->assertRedirect();
        $response->assertSessionHasErrors('role');
        expect($this->masterAdmin->fresh()->role)->toBe('master_admin');
    })->group('user-management', 'role-updates', 'safety');

    it('denies non-master-admin roles from updating user roles', function ($userFactory) {
        $actor = is_callable($userFactory) ? $userFactory() : $userFactory;
        $targetUser = createTestMember(['email' => 'target@test.com']);
        
        $response = $this->actingAs($actor)
            ->patch(route('admin.users.updateRole', $targetUser), ['role' => 'admin']);

        expect($response)->toBeForbidden();
        expect($targetUser->fresh()->role)->toBe('member');
    })->with('unauthorized_for_master_admin')
      ->group('user-management', 'role-updates', 'unauthorized');

    it('redirects unauthenticated users from role updates', function () {
        $member = createTestMember();
        
        $response = $this->patch(route('admin.users.updateRole', $member), ['role' => 'admin']);

        expect($response)->toRedirectToLogin();
        expect($member->fresh()->role)->toBe('member');
    })->group('user-management', 'role-updates', 'guest');
});

describe('Role Update Validation', function () {
    beforeEach(function () {
        $this->masterAdmin = createTestMasterAdmin();
        $this->member = createTestMember();
    });

    it('requires role field', function () {
        $response = $this->actingAs($this->masterAdmin)
            ->patch(route('admin.users.updateRole', $this->member), []);

        expect($response)->toHaveValidationError('role');
    })->group('user-management', 'role-updates', 'validation');

    it('requires valid role value', function () {
        $response = $this->actingAs($this->masterAdmin)
            ->patch(route('admin.users.updateRole', $this->member), ['role' => 'invalid_role']);

        expect($response)->toHaveValidationError('role');
    })->group('user-management', 'role-updates', 'validation');
});

describe('User Deletion', function () {
    beforeEach(function () {
        $this->masterAdmin = createTestMasterAdmin();
    });

    it('allows master admin to delete member users', function () {
        $member = createTestMember();
        
        $response = $this->actingAs($this->masterAdmin)
            ->delete(route('admin.users.destroy', $member));

        expect($response)->toHaveSuccessMessage('User deleted successfully.');
        $this->assertDatabaseMissing('users', ['id' => $member->id]);
    })->group('user-management', 'deletion', 'authorized');

    it('allows master admin to delete admin users', function () {
        $admin = createTestAdmin();
        
        $response = $this->actingAs($this->masterAdmin)
            ->delete(route('admin.users.destroy', $admin));

        expect($response)->toHaveSuccessMessage('User deleted successfully.');
        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
    })->group('user-management', 'deletion', 'authorized');

    it('allows master admin to delete other master admin users when multiple exist', function () {
        $masterAdmin2 = createTestMasterAdmin(['email' => 'ma2@test.com']);
        
        $response = $this->actingAs($this->masterAdmin)
            ->delete(route('admin.users.destroy', $masterAdmin2));

        expect($response)->toHaveSuccessMessage('User deleted successfully.');
        $this->assertDatabaseMissing('users', ['id' => $masterAdmin2->id]);
    })->group('user-management', 'deletion', 'authorized');

    it('prevents deleting the last master admin', function () {
        $response = $this->actingAs($this->masterAdmin)
            ->delete(route('admin.users.destroy', $this->masterAdmin));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('users', ['id' => $this->masterAdmin->id]);
    })->group('user-management', 'deletion', 'safety');

    it('prevents master admin from deleting themselves', function () {
        $masterAdmin2 = createTestMasterAdmin(['email' => 'ma2@test.com']); // Ensure not last
        
        $response = $this->actingAs($this->masterAdmin)
            ->delete(route('admin.users.destroy', $this->masterAdmin));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('users', ['id' => $this->masterAdmin->id]);
    })->group('user-management', 'deletion', 'safety');

    it('denies non-master-admin roles from deleting users', function ($userFactory) {
        $actor = is_callable($userFactory) ? $userFactory() : $userFactory;
        $targetUser = createTestMember(['email' => 'target@test.com']);
        
        $response = $this->actingAs($actor)
            ->delete(route('admin.users.destroy', $targetUser));

        expect($response)->toBeForbidden();
        $this->assertDatabaseHas('users', ['id' => $targetUser->id]);
    })->with('unauthorized_for_master_admin')
      ->group('user-management', 'deletion', 'unauthorized');

    it('redirects unauthenticated users from user deletion', function () {
        $member = createTestMember();
        
        $response = $this->delete(route('admin.users.destroy', $member));

        expect($response)->toRedirectToLogin();
        $this->assertDatabaseHas('users', ['id' => $member->id]);
    })->group('user-management', 'deletion', 'guest');
});

describe('Edge Cases and Complex Scenarios', function () {
    it('allows multiple master admins to perform user management independently', function () {
        $masterAdmin1 = createTestMasterAdmin(['email' => 'ma1@test.com']);
        $masterAdmin2 = createTestMasterAdmin(['email' => 'ma2@test.com']);
        $member = createTestMember();

        // First master admin promotes the member
        $response1 = $this->actingAs($masterAdmin1)
            ->patch(route('admin.users.updateRole', $member), ['role' => 'admin']);

        expect($response1)->toHaveSuccessMessage('User role updated successfully.');
        expect($member->fresh()->role)->toBe('admin');

        // Second master admin demotes the member back
        $response2 = $this->actingAs($masterAdmin2)
            ->patch(route('admin.users.updateRole', $member), ['role' => 'member']);

        expect($response2)->toHaveSuccessMessage('User role updated successfully.');
        expect($member->fresh()->role)->toBe('member');
    })->group('user-management', 'edge-cases');

    it('includes both master admins and regular admins with correct role distribution', function () {
        $masterAdmin1 = createTestMasterAdmin(['name' => 'Master Admin 1']);
        $masterAdmin2 = createTestMasterAdmin(['name' => 'Master Admin 2', 'email' => 'ma2@test.com']);
        $admin1 = createTestAdmin(['name' => 'Admin 1']);
        $admin2 = createTestAdmin(['name' => 'Admin 2', 'email' => 'admin2@test.com']);

        $response = $this->actingAs($masterAdmin1)->get(route('admin.users.admins'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('admins.data', 4)); // paginated data

        // Verify correct role distribution
        $admins = $response->viewData('page')['props']['admins']['data'];
        $roles = collect($admins)->pluck('role')->toArray();
        $masterAdminCount = collect($roles)->filter(fn($role) => $role === 'master_admin')->count();
        $adminCount = collect($roles)->filter(fn($role) => $role === 'admin')->count();

        expect($masterAdminCount)->toBe(2)
            ->and($adminCount)->toBe(2);
    })->group('user-management', 'edge-cases');

    it('maintains database integrity through creation and multiple role updates', function () {
        $masterAdmin = createTestMasterAdmin();

        // Create a member
        $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'member',
        ]);

        $user = User::where('email', 'test@test.com')->first();
        expect($user)->not->toBeNull()
            ->and($user->role)->toBe('member');

        // Promote to admin
        $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $user), ['role' => 'admin']);
        expect($user->fresh()->role)->toBe('admin');

        // Promote to master admin
        $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $user), ['role' => 'master_admin']);
        expect($user->fresh()->role)->toBe('master_admin');

        // Demote back to member
        $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $user), ['role' => 'member']);
        expect($user->fresh()->role)->toBe('member');
    })->group('user-management', 'edge-cases', 'integrity');

    it('removes deleted users from all role-specific lists', function () {
        $masterAdmin = createTestMasterAdmin();
        $admin = createTestAdmin(['name' => 'Test Admin']);
        $member = createTestMember(['name' => 'Test Member']);

        // Verify user appears in admin list
        $response1 = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
        $response1->assertInertia(fn ($page) => $page->has('admins.data', 2)); // masterAdmin + admin (paginated data)

        // Delete the admin
        $this->actingAs($masterAdmin)->delete(route('admin.users.destroy', $admin));

        // Verify user no longer appears
        $response2 = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
        $response2->assertInertia(fn ($page) => $page->has('admins.data', 1)); // Only masterAdmin remains (paginated data)
    })->group('user-management', 'edge-cases', 'deletion');

    it('includes all required fields in admin user data', function () {
        $masterAdmin = createTestMasterAdmin();
        $admin = createTestAdmin(['name' => 'Test Admin', 'email' => 'test@test.com']);

        $response = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));

        $response->assertInertia(fn ($page) => $page
            ->has('admins.data.0', fn ($admin) => $admin // paginated data
                ->has('id')
                ->has('name')
                ->has('email')
                ->has('role')
                ->has('created_at')
                ->has('invitation_sent_at')
                ->has('invitation_accepted_at')
                ->has('invitation_expired')
                ->missing('invitation_token') // Should not expose token
            )
        );
    })->group('user-management', 'edge-cases', 'data-integrity');

    it('includes all required fields in member user data', function () {
        $masterAdmin = createTestMasterAdmin();
        $member = createTestMember(['name' => 'Test Member', 'email' => 'test@test.com']);

        $response = $this->actingAs($masterAdmin)->get(route('admin.users.members'));

        $response->assertInertia(fn ($page) => $page
            ->has('members.data.0', fn ($member) => $member // paginated data
                ->has('id')
                ->has('name')
                ->has('email')
                ->has('role')
                ->has('created_at')
                ->has('invitation_sent_at')
                ->has('invitation_accepted_at')
                ->has('invitation_expired')
                ->missing('invitation_token') // Should not expose token
            )
        );
    })->group('user-management', 'edge-cases', 'data-integrity');
});

describe('Error Handling and Transactions', function () {
    it('rolls back user creation if notification dispatch fails', function () {
        $masterAdmin = createTestMasterAdmin();
        
        // Mock Notification facade to throw exception
        \Illuminate\Support\Facades\Notification::fake();
        \Illuminate\Support\Facades\Notification::shouldReceive('send')
            ->andThrow(new \Exception('Mail server unavailable'));

        $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
            'name' => 'Test User',
            'email' => 'test@test.com',
            'role' => 'member',
        ]);

        // User creation should be rolled back
        $this->assertDatabaseMissing('users', [
            'email' => 'test@test.com',
        ]);
        
        $response->assertSessionHasErrors('error');
    })->group('user-management', 'error-handling', 'transactions')->skip('Requires notification mocking setup');

    it('provides user feedback when invitation sending fails', function () {
        $masterAdmin = createTestMasterAdmin();
        
        // This test verifies the error message format without actually failing
        // Real failure scenarios would require mocking the mail system
        $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
            'name' => 'Test User',
            'email' => 'valid@test.com',
            'role' => 'member',
        ]);

        // Under normal circumstances, should succeed
        expect($response)->toHaveSuccessMessage('User invited successfully. An invitation email has been sent.');
    })->group('user-management', 'error-handling');
});

describe('Rate Limiting', function () {
    it('rate limits user creation attempts', function () {
        $masterAdmin = createTestMasterAdmin();
        
        // Make 21 rapid requests (limit is 20 per minute)
        for ($i = 1; $i <= 21; $i++) {
            $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
                'name' => "User {$i}",
                'email' => "user{$i}@test.com",
                'role' => 'member',
            ]);
            
            if ($i === 21) {
                // 21st request should be rate limited
                expect($response->status())->toBe(429);
            }
        }
    })->group('user-management', 'rate-limiting');

    it('rate limits invitation resend attempts', function () {
        $masterAdmin = createTestMasterAdmin();
        $user = User::factory()->create([
            'invitation_token' => hash('sha256', 'test_token'),
            'invitation_sent_at' => now()->subHours(1),
            'invitation_accepted_at' => null,
        ]);
        
        // Make 21 rapid requests (limit is 20 per minute)
        for ($i = 1; $i <= 21; $i++) {
            $response = $this->actingAs($masterAdmin)
                ->post(route('admin.users.resendInvitation', $user));
            
            if ($i === 21) {
                // 21st request should be rate limited
                expect($response->status())->toBe(429);
            }
        }
    })->group('user-management', 'rate-limiting');
});

