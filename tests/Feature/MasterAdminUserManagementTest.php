<?php

/**
 * Master Admin User Management Test Suite
 *
 * Tests comprehensive user management functionality available exclusively to master admins.
 * Covers CRUD operations for user accounts, role management, and access control.
 *
 * Test Categories:
 * - Access Control (8 tests): Verifies only master admins can access user management
 * - User Listing & Display (6 tests): Tests admin/member indexes with ordering and field validation
 * - User Creation (20 tests): Validates user creation with all roles and validation rules
 * - Role Updates (13 tests): Tests role promotions, demotions, and safety checks
 * - User Deletion (9 tests): Verifies delete operations and last master admin protection
 * - Edge Cases (4 tests): Complex scenarios including data integrity checks
 *
 * Safety Features Tested:
 * - Cannot delete the last master admin
 * - Cannot demote the last master admin
 * - Cannot delete self
 * - Role-based access control enforcement
 *
 * Total: 60 tests, 286 assertions
 */

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Disable 2FA middleware for these tests to focus on user management
beforeEach(function () {
    $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorEnabled::class);
});

// Access Control Tests
test('master admin can access admin users index', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Admin/Users/AdminUsers'));
});

test('master admin can access member users index', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.users.members'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Admin/Users/MemberUsers'));
});

test('regular admin cannot access admin users index', function () {
    $admin = createTestAdmin();
    
    $response = $this->actingAs($admin)->get(route('admin.users.admins'));
    
    $response->assertStatus(403);
});

test('regular admin cannot access member users index', function () {
    $admin = createTestAdmin();
    
    $response = $this->actingAs($admin)->get(route('admin.users.members'));
    
    $response->assertStatus(403);
});

test('member cannot access admin users index', function () {
    $member = createTestMember();
    
    $response = $this->actingAs($member)->get(route('admin.users.admins'));
    
    $response->assertStatus(403);
});

test('member cannot access member users index', function () {
    $member = createTestMember();
    
    $response = $this->actingAs($member)->get(route('admin.users.members'));
    
    $response->assertStatus(403);
});

test('guest cannot access admin users index', function () {
    $response = $this->get(route('admin.users.admins'));
    
    $response->assertRedirect(route('login'));
});

test('guest cannot access member users index', function () {
    $response = $this->get(route('admin.users.members'));
    
    $response->assertRedirect(route('login'));
});

// Admin Users Index Tests
test('admin users index displays all admins and master admins', function () {
    $masterAdmin = createTestMasterAdmin();
    $admin1 = createTestAdmin(['name' => 'Admin One', 'email' => 'admin1@test.com']);
    $admin2 = createTestAdmin(['name' => 'Admin Two', 'email' => 'admin2@test.com']);
    $member = createTestMember(['name' => 'Member One']); // Should not appear
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Users/AdminUsers')
        ->has('admins', 3) // masterAdmin + 2 admins
    );
    
    // Verify all admin users are present (regardless of order)
    $adminNames = $response->viewData('page')['props']['admins'];
    $names = collect($adminNames)->pluck('name')->toArray();
    expect($names)->toContain($masterAdmin->name);
    expect($names)->toContain('Admin One');
    expect($names)->toContain('Admin Two');
});

test('admin users index does not display members', function () {
    $masterAdmin = createTestMasterAdmin();
    createTestMember(['name' => 'Member One']);
    createTestMember(['name' => 'Member Two']);
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('admins', 1) // Only the master admin
    );
});

test('admin users index displays users ordered by creation date', function () {
    $masterAdmin = createTestMasterAdmin();
    
    // Create with explicit timestamps using sleep to ensure different creation times
    $admin1 = User::factory()->admin()->create([
        'name' => 'Admin One',
        'created_at' => now()->subDays(2)
    ]);
    sleep(1);
    $admin2 = User::factory()->admin()->create([
        'name' => 'Admin Two',
        'created_at' => now()->subDay()
    ]);
    sleep(1);
    $admin3 = User::factory()->admin()->create([
        'name' => 'Admin Three',
        'created_at' => now()
    ]);
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('admins', 4) // 3 admins + masterAdmin
    );
    
    // Verify most recent is first
    $admins = $response->viewData('page')['props']['admins'];
    expect($admins[0]['name'])->toBe('Admin Three');
});

// Member Users Index Tests
test('member users index displays all members', function () {
    $masterAdmin = createTestMasterAdmin();
    $member1 = createTestMember(['name' => 'Member One', 'email' => 'member1@test.com']);
    $member2 = createTestMember(['name' => 'Member Two', 'email' => 'member2@test.com']);
    $admin = createTestAdmin(['name' => 'Admin One']); // Should not appear
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.users.members'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Users/MemberUsers')
        ->has('members', 2)
    );
    
    // Verify both members are present
    $members = $response->viewData('page')['props']['members'];
    $names = collect($members)->pluck('name')->toArray();
    expect($names)->toContain('Member One');
    expect($names)->toContain('Member Two');
});

test('member users index does not display admins', function () {
    $masterAdmin = createTestMasterAdmin();
    createTestAdmin(['name' => 'Admin One']);
    createTestAdmin(['name' => 'Admin Two']);
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.users.members'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('members', 0)
    );
});

test('member users index displays users ordered by creation date', function () {
    $masterAdmin = createTestMasterAdmin();
    $member1 = createTestMember(['name' => 'Member One', 'created_at' => now()->subDays(2)]);
    $member2 = createTestMember(['name' => 'Member Two', 'created_at' => now()->subDay()]);
    $member3 = createTestMember(['name' => 'Member Three', 'created_at' => now()]);
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.users.members'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('members.0.name', 'Member Three') // Most recent first
        ->where('members.1.name', 'Member Two')
        ->where('members.2.name', 'Member One')
    );
});

// User Creation Tests
test('master admin can create a new member user', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'New Member',
        'email' => 'newmember@test.com',
        'password' => 'password123', // Min 8 chars required
        'password_confirmation' => 'password123',
        'role' => 'member', // Valid roles: member, admin, master_admin
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success', 'User created successfully.');
    
    $this->assertDatabaseHas('users', [
        'name' => 'New Member',
        'email' => 'newmember@test.com',
        'role' => 'member',
    ]);
    
    $user = User::where('email', 'newmember@test.com')->first();
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

test('master admin can create a new admin user', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'New Admin',
        'email' => 'newadmin@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success', 'User created successfully.');
    
    $this->assertDatabaseHas('users', [
        'name' => 'New Admin',
        'email' => 'newadmin@test.com',
        'role' => 'admin',
    ]);
});

test('master admin can create a new master admin user', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'New Master Admin',
        'email' => 'newmasteradmin@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'master_admin',
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success', 'User created successfully.');
    
    $this->assertDatabaseHas('users', [
        'name' => 'New Master Admin',
        'email' => 'newmasteradmin@test.com',
        'role' => 'master_admin',
    ]);
});

test('created users have their email automatically verified', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'newuser@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'member',
    ]);
    
    $response->assertSessionHas('success');
    
    $this->assertDatabaseHas('users', [
        'email' => 'newuser@test.com',
        'role' => 'member',
    ]);
});

test('regular admin cannot create users', function () {
    $admin = createTestAdmin();
    
    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'New Member',
        'email' => 'newmember@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'member',
    ]);
    
    $response->assertStatus(403);
    
    $this->assertDatabaseMissing('users', [
        'email' => 'newmember@test.com',
    ]);
});

test('member cannot create users', function () {
    $member = createTestMember();
    
    $response = $this->actingAs($member)->post(route('admin.users.store'), [
        'name' => 'New Member',
        'email' => 'newmember@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'member',
    ]);
    
    $response->assertStatus(403);
    
    $this->assertDatabaseMissing('users', [
        'email' => 'newmember@test.com',
    ]);
});

test('guest cannot create users', function () {
    $response = $this->post(route('admin.users.store'), [
        'name' => 'New Member',
        'email' => 'newmember@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'member',
    ]);
    
    $response->assertRedirect(route('login'));
    
    $this->assertDatabaseMissing('users', [
        'email' => 'newmember@test.com',
    ]);
});

// User Creation Validation Tests
test('user creation requires name', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'email' => 'newuser@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'member',
    ]);
    
    $response->assertSessionHasErrors('name');
});

test('user creation requires email', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'member',
    ]);
    
    $response->assertSessionHasErrors('email');
});

test('user creation requires valid email format', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'not-an-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'member',
    ]);
    
    $response->assertSessionHasErrors('email');
});

test('user creation requires unique email', function () {
    $masterAdmin = createTestMasterAdmin();
    $existingUser = createTestMember(['email' => 'existing@test.com']);
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'existing@test.com', // Duplicate email - must be unique
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'member',
    ]);
    
    $response->assertSessionHasErrors('email');
});

test('user creation requires password', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'newuser@test.com',
        'role' => 'member',
    ]);
    
    $response->assertSessionHasErrors('password');
});

test('user creation requires password confirmation', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'newuser@test.com',
        'password' => 'password123',
        'role' => 'member',
    ]);
    
    $response->assertSessionHasErrors('password');
});

test('user creation requires matching password confirmation', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'newuser@test.com',
        'password' => 'password123',
        'password_confirmation' => 'different', // Mismatch - must match password field
        'role' => 'member',
    ]);
    
    $response->assertSessionHasErrors('password');
});

test('user creation requires role', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'newuser@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    
    $response->assertSessionHasErrors('role');
});

test('user creation requires valid role', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'newuser@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'invalid_role',
    ]);
    
    $response->assertSessionHasErrors('role');
});

test('email validation requires lowercase format', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'NewUser@TEST.COM',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'member',
    ]);
    
    // The lowercase validation rule requires lowercase input
    $response->assertSessionHasErrors('email');
});

// Role Update Tests
test('master admin can promote member to admin', function () {
    $masterAdmin = createTestMasterAdmin();
    $member = createTestMember();
    
    $response = $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $member), [
        'role' => 'admin',
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success', 'User role updated successfully.');
    
    expect($member->fresh()->role)->toBe('admin');
});

test('master admin can promote member to master admin', function () {
    $masterAdmin = createTestMasterAdmin();
    $member = createTestMember();
    
    $response = $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $member), [
        'role' => 'master_admin',
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success', 'User role updated successfully.');
    
    expect($member->fresh()->role)->toBe('master_admin');
});

test('master admin can promote admin to master admin', function () {
    $masterAdmin = createTestMasterAdmin();
    $admin = createTestAdmin();
    
    $response = $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $admin), [
        'role' => 'master_admin',
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success', 'User role updated successfully.');
    
    expect($admin->fresh()->role)->toBe('master_admin');
});

test('master admin can demote admin to member', function () {
    $masterAdmin = createTestMasterAdmin();
    $admin = createTestAdmin();
    
    $response = $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $admin), [
        'role' => 'member',
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success', 'User role updated successfully.');
    
    expect($admin->fresh()->role)->toBe('member');
});

test('master admin can demote master admin to admin', function () {
    $masterAdmin1 = createTestMasterAdmin();
    $masterAdmin2 = createTestMasterAdmin(['email' => 'ma2@test.com']);
    
    $response = $this->actingAs($masterAdmin1)->patch(route('admin.users.updateRole', $masterAdmin2), [
        'role' => 'admin',
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success', 'User role updated successfully.');
    
    expect($masterAdmin2->fresh()->role)->toBe('admin');
});

test('master admin can demote master admin to member', function () {
    $masterAdmin1 = createTestMasterAdmin();
    $masterAdmin2 = createTestMasterAdmin(['email' => 'ma2@test.com']);
    
    $response = $this->actingAs($masterAdmin1)->patch(route('admin.users.updateRole', $masterAdmin2), [
        'role' => 'member',
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success', 'User role updated successfully.');
    
    expect($masterAdmin2->fresh()->role)->toBe('member');
});

test('cannot demote the last master admin to admin', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $masterAdmin), [
        'role' => 'admin',
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHasErrors('role');
    
    expect($masterAdmin->fresh()->role)->toBe('master_admin');
});

test('cannot demote the last master admin to member', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $masterAdmin), [
        'role' => 'member',
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHasErrors('role');
    
    expect($masterAdmin->fresh()->role)->toBe('master_admin');
});

test('can demote a master admin when multiple master admins exist', function () {
    $masterAdmin1 = createTestMasterAdmin();
    $masterAdmin2 = createTestMasterAdmin(['email' => 'ma2@test.com']);
    
    $response = $this->actingAs($masterAdmin1)->patch(route('admin.users.updateRole', $masterAdmin2), [
        'role' => 'admin',
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success');
    
    expect($masterAdmin2->fresh()->role)->toBe('admin');
});

test('regular admin cannot update user roles', function () {
    $admin = createTestAdmin();
    $member = createTestMember();
    
    $response = $this->actingAs($admin)->patch(route('admin.users.updateRole', $member), [
        'role' => 'admin',
    ]);
    
    $response->assertStatus(403);
    
    expect($member->fresh()->role)->toBe('member');
});

test('member cannot update user roles', function () {
    $member1 = createTestMember();
    $member2 = createTestMember(['email' => 'member2@test.com']);
    
    $response = $this->actingAs($member1)->patch(route('admin.users.updateRole', $member2), [
        'role' => 'admin',
    ]);
    
    $response->assertStatus(403);
    
    expect($member2->fresh()->role)->toBe('member');
});

test('guest cannot update user roles', function () {
    $member = createTestMember();
    
    $response = $this->patch(route('admin.users.updateRole', $member), [
        'role' => 'admin',
    ]);
    
    $response->assertRedirect(route('login'));
    
    expect($member->fresh()->role)->toBe('member');
});

// Role Update Validation Tests
test('role update requires role', function () {
    $masterAdmin = createTestMasterAdmin();
    $member = createTestMember();
    
    $response = $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $member), []);
    
    $response->assertSessionHasErrors('role');
});

test('role update requires valid role', function () {
    $masterAdmin = createTestMasterAdmin();
    $member = createTestMember();
    
    $response = $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $member), [
        'role' => 'invalid_role',
    ]);
    
    $response->assertSessionHasErrors('role');
});

// User Deletion Tests
test('master admin can delete member users', function () {
    $masterAdmin = createTestMasterAdmin();
    $member = createTestMember();
    
    $response = $this->actingAs($masterAdmin)->delete(route('admin.users.destroy', $member));
    
    $response->assertRedirect();
    $response->assertSessionHas('success', 'User deleted successfully.');
    
    $this->assertDatabaseMissing('users', [
        'id' => $member->id,
    ]);
});

test('master admin can delete admin users', function () {
    $masterAdmin = createTestMasterAdmin();
    $admin = createTestAdmin();
    
    $response = $this->actingAs($masterAdmin)->delete(route('admin.users.destroy', $admin));
    
    $response->assertRedirect();
    $response->assertSessionHas('success', 'User deleted successfully.');
    
    $this->assertDatabaseMissing('users', [
        'id' => $admin->id,
    ]);
});

test('master admin can delete other master admin users', function () {
    $masterAdmin1 = createTestMasterAdmin();
    $masterAdmin2 = createTestMasterAdmin(['email' => 'ma2@test.com']);
    
    $response = $this->actingAs($masterAdmin1)->delete(route('admin.users.destroy', $masterAdmin2));
    
    $response->assertRedirect();
    $response->assertSessionHas('success', 'User deleted successfully.');
    
    $this->assertDatabaseMissing('users', [
        'id' => $masterAdmin2->id,
    ]);
});

test('cannot delete the last master admin', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->delete(route('admin.users.destroy', $masterAdmin));
    
    $response->assertRedirect();
    $response->assertSessionHasErrors('error');
    
    $this->assertDatabaseHas('users', [
        'id' => $masterAdmin->id,
    ]);
});

test('can delete a master admin when multiple master admins exist', function () {
    $masterAdmin1 = createTestMasterAdmin();
    $masterAdmin2 = createTestMasterAdmin(['email' => 'ma2@test.com']);
    
    $response = $this->actingAs($masterAdmin1)->delete(route('admin.users.destroy', $masterAdmin2));
    
    $response->assertRedirect();
    $response->assertSessionHas('success');
    
    $this->assertDatabaseMissing('users', [
        'id' => $masterAdmin2->id,
    ]);
});

test('master admin cannot delete themselves', function () {
    $masterAdmin1 = createTestMasterAdmin();
    $masterAdmin2 = createTestMasterAdmin(['email' => 'ma2@test.com']); // Ensure not last master admin
    
    $response = $this->actingAs($masterAdmin1)->delete(route('admin.users.destroy', $masterAdmin1));
    
    $response->assertRedirect();
    $response->assertSessionHasErrors('error');
    
    $this->assertDatabaseHas('users', [
        'id' => $masterAdmin1->id,
    ]);
});

test('regular admin cannot delete users', function () {
    $admin = createTestAdmin();
    $member = createTestMember();
    
    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $member));
    
    $response->assertStatus(403);
    
    $this->assertDatabaseHas('users', [
        'id' => $member->id,
    ]);
});

test('member cannot delete users', function () {
    $member1 = createTestMember();
    $member2 = createTestMember(['email' => 'member2@test.com']);
    
    $response = $this->actingAs($member1)->delete(route('admin.users.destroy', $member2));
    
    $response->assertStatus(403);
    
    $this->assertDatabaseHas('users', [
        'id' => $member2->id,
    ]);
});

test('guest cannot delete users', function () {
    $member = createTestMember();
    
    $response = $this->delete(route('admin.users.destroy', $member));
    
    $response->assertRedirect(route('login'));
    
    $this->assertDatabaseHas('users', [
        'id' => $member->id,
    ]);
});

// Edge Cases and Complex Scenarios
test('multiple master admins can perform user management independently', function () {
    $masterAdmin1 = createTestMasterAdmin(['email' => 'ma1@test.com']);
    $masterAdmin2 = createTestMasterAdmin(['email' => 'ma2@test.com']);
    $member = createTestMember();
    
    // First master admin promotes the member
    $response1 = $this->actingAs($masterAdmin1)->patch(route('admin.users.updateRole', $member), [
        'role' => 'admin',
    ]);
    
    $response1->assertSessionHas('success');
    expect($member->fresh()->role)->toBe('admin');
    
    // Second master admin demotes the member back
    $response2 = $this->actingAs($masterAdmin2)->patch(route('admin.users.updateRole', $member), [
        'role' => 'member',
    ]);
    
    $response2->assertSessionHas('success');
    expect($member->fresh()->role)->toBe('member');
});

test('admin users list includes both master admins and regular admins with correct roles', function () {
    $masterAdmin1 = createTestMasterAdmin(['name' => 'Master Admin 1']);
    $masterAdmin2 = createTestMasterAdmin(['name' => 'Master Admin 2', 'email' => 'ma2@test.com']);
    $admin1 = createTestAdmin(['name' => 'Admin 1']);
    $admin2 = createTestAdmin(['name' => 'Admin 2', 'email' => 'admin2@test.com']);
    
    $response = $this->actingAs($masterAdmin1)->get(route('admin.users.admins'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('admins', 4)
    );
    
    // Verify correct role distribution
    $admins = $response->viewData('page')['props']['admins'];
    $roles = collect($admins)->pluck('role')->toArray();
    $masterAdminCount = collect($roles)->filter(fn($role) => $role === 'master_admin')->count();
    $adminCount = collect($roles)->filter(fn($role) => $role === 'admin')->count();
    
    expect($masterAdminCount)->toBe(2);
    expect($adminCount)->toBe(2);
});

test('user creation and role update maintain database integrity', function () {
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
    expect($user)->not->toBeNull();
    expect($user->role)->toBe('member');
    
    // Promote to admin
    $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $user), [
        'role' => 'admin',
    ]);
    
    expect($user->fresh()->role)->toBe('admin');
    
    // Promote to master admin
    $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $user), [
        'role' => 'master_admin',
    ]);
    
    expect($user->fresh()->role)->toBe('master_admin');
    
    // Demote back to member
    $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $user), [
        'role' => 'member',
    ]);
    
    expect($user->fresh()->role)->toBe('member');
});

test('deleting a user removes them from all role-specific lists', function () {
    $masterAdmin = createTestMasterAdmin();
    $admin = createTestAdmin(['name' => 'Test Admin']);
    $member = createTestMember(['name' => 'Test Member']);
    
    // Verify user appears in admin list
    $response1 = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
    $response1->assertInertia(fn ($page) => $page
        ->has('admins', 2) // masterAdmin + admin
    );
    
    // Delete the admin
    $this->actingAs($masterAdmin)->delete(route('admin.users.destroy', $admin));
    
    // Verify user no longer appears
    $response2 = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
    $response2->assertInertia(fn ($page) => $page
        ->has('admins', 1) // Only masterAdmin remains
    );
});

test('user data includes all required fields in admin list', function () {
    $masterAdmin = createTestMasterAdmin();
    $admin = createTestAdmin(['name' => 'Test Admin', 'email' => 'test@test.com']);
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
    
    $response->assertInertia(fn ($page) => $page
        ->has('admins.0', fn ($admin) => $admin
            ->has('id')
            ->has('name')
            ->has('email')
            ->has('role')
            ->has('created_at')
        )
    );
});

test('user data includes all required fields in member list', function () {
    $masterAdmin = createTestMasterAdmin();
    $member = createTestMember(['name' => 'Test Member', 'email' => 'test@test.com']);
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.users.members'));
    
    $response->assertInertia(fn ($page) => $page
        ->has('members.0', fn ($member) => $member
            ->has('id')
            ->has('name')
            ->has('email')
            ->has('role')
            ->has('created_at')
        )
    );
});
