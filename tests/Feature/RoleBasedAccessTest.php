<?php

use App\Models\BlogPost;
use App\Models\User;

// Disable 2FA middleware for these tests to focus on role-based access
beforeEach(function () {
    $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorEnabled::class);
});

// Role Detection Tests
test('user model correctly identifies master admin role', function () {
    $masterAdmin = createTestMasterAdmin();
    
    expect($masterAdmin->isMasterAdmin())->toBeTrue();
    expect($masterAdmin->isAdmin())->toBeTrue(); // Master admin is also admin
    expect($masterAdmin->isRegularAdmin())->toBeFalse();
    expect($masterAdmin->isMember())->toBeFalse();
    expect($masterAdmin->role)->toBe('master_admin');
});

test('user model correctly identifies admin role', function () {
    $admin = createTestAdmin();
    
    expect($admin->isMasterAdmin())->toBeFalse();
    expect($admin->isAdmin())->toBeTrue();
    expect($admin->isRegularAdmin())->toBeTrue();
    expect($admin->isMember())->toBeFalse();
    expect($admin->role)->toBe('admin');
});

test('user model correctly identifies member role', function () {
    $member = createTestMember();
    
    expect($member->isMasterAdmin())->toBeFalse();
    expect($member->isAdmin())->toBeFalse();
    expect($member->isRegularAdmin())->toBeFalse();
    expect($member->isMember())->toBeTrue();
    expect($member->role)->toBe('member');
});

// Dashboard Access Tests
test('admin users can access dashboard', function () {
    $admin = createTestAdmin();
    
    $response = $this->actingAs($admin)->get(route('dashboard'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Dashboard'));
});

test('master admin users can access dashboard', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->get(route('dashboard'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Dashboard'));
});

test('member users cannot access dashboard', function () {
    $member = createTestMember();
    
    $response = $this->actingAs($member)->get(route('dashboard'));
    
    $response->assertStatus(403);
});

test('guests cannot access dashboard', function () {
    $response = $this->get(route('dashboard'));
    
    $response->assertRedirect(route('login'));
});

// Blog Post Management Access Tests
test('admin users can access blog post index', function () {
    $admin = createTestAdmin();
    
    $response = $this->actingAs($admin)->get(route('admin.blog-posts.index'));
    
    $response->assertStatus(200);
});

test('admin users can access blog post create page', function () {
    $admin = createTestAdmin();
    
    $response = $this->actingAs($admin)->get(route('admin.blog-posts.create'));
    
    $response->assertStatus(200);
});

test('admin users can create blog posts', function () {
    $admin = createTestAdmin();
    
    $response = $this->actingAs($admin)->post(route('admin.blog-posts.store'), [
        'title' => 'Test Post',
        'excerpt' => 'Test excerpt',
        'content' => 'Test content',
        'is_published' => true,
    ]);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('blog_posts', [
        'title' => 'Test Post',
        'user_id' => $admin->id,
    ]);
});

test('master admin users can access blog post management', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.blog-posts.index'));
    
    $response->assertStatus(200);
});

test('master admin users can create blog posts', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.blog-posts.store'), [
        'title' => 'Master Admin Post',
        'excerpt' => 'Test excerpt',
        'content' => 'Test content',
        'is_published' => true,
    ]);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('blog_posts', [
        'title' => 'Master Admin Post',
        'user_id' => $masterAdmin->id,
    ]);
});

test('member users cannot access blog post index', function () {
    $member = createTestMember();
    
    $response = $this->actingAs($member)->get(route('admin.blog-posts.index'));
    
    $response->assertStatus(403);
});

test('member users cannot access blog post create page', function () {
    $member = createTestMember();
    
    $response = $this->actingAs($member)->get(route('admin.blog-posts.create'));
    
    $response->assertStatus(403);
});

test('member users cannot create blog posts', function () {
    $member = createTestMember();
    
    $response = $this->actingAs($member)->post(route('admin.blog-posts.store'), [
        'title' => 'Test Post',
        'excerpt' => 'Test excerpt',
        'content' => 'Test content',
        'is_published' => true,
    ]);
    
    $response->assertStatus(403);
    $this->assertDatabaseMissing('blog_posts', [
        'title' => 'Test Post',
        'user_id' => $member->id,
    ]);
});

test('guests cannot access blog post management', function () {
    $response = $this->get(route('admin.blog-posts.index'));
    
    $response->assertRedirect(route('login'));
});

// Blog Post Editing Authorization Tests
test('admin users can only edit their own blog posts', function () {
    $admin1 = createTestAdmin();
    $admin2 = createTestAdmin();
    
    $post = BlogPost::factory()->create(['user_id' => $admin2->id]);
    
    $response = $this->actingAs($admin1)->get(route('admin.blog-posts.edit', $post));
    
    $response->assertStatus(403);
});

test('master admin users can only edit their own blog posts', function () {
    $masterAdmin = createTestMasterAdmin();
    $admin = createTestAdmin();
    
    $post = BlogPost::factory()->create(['user_id' => $admin->id]);
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.blog-posts.edit', $post));
    
    $response->assertStatus(403);
});

// User Management Access Tests (Master Admin Only)
test('master admin can access admin users list', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
    
    $response->assertStatus(200);
});

test('master admin can access member users list', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.users.members'));
    
    $response->assertStatus(200);
});

test('master admin can create new users', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
    ]);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'role' => 'admin',
    ]);
});

test('master admin can update user roles', function () {
    $masterAdmin = createTestMasterAdmin();
    $user = createTestMember(['role' => 'member']);
    
    $response = $this->actingAs($masterAdmin)->patch(route('admin.users.updateRole', $user), [
        'role' => 'admin',
    ]);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => 'admin',
    ]);
});

test('master admin can delete users', function () {
    $masterAdmin = createTestMasterAdmin();
    $user = createTestMember();
    
    $response = $this->actingAs($masterAdmin)->delete(route('admin.users.destroy', $user));
    
    $response->assertRedirect();
    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('regular admin cannot access user management', function () {
    $admin = createTestAdmin();
    
    $response = $this->actingAs($admin)->get(route('admin.users.admins'));
    
    $response->assertStatus(403);
});

test('regular admin cannot create users', function () {
    $admin = createTestAdmin();
    
    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
    ]);
    
    $response->assertStatus(403);
    $this->assertDatabaseMissing('users', [
        'email' => 'newuser@example.com',
    ]);
});

test('regular admin cannot update user roles', function () {
    $admin = createTestAdmin();
    $user = createTestMember(['role' => 'member']);
    
    $response = $this->actingAs($admin)->patch(route('admin.users.updateRole', $user), [
        'role' => 'admin',
    ]);
    
    $response->assertStatus(403);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => 'member', // Role unchanged
    ]);
});

test('regular admin cannot delete users', function () {
    $admin = createTestAdmin();
    $user = createTestMember();
    
    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));
    
    $response->assertStatus(403);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
    ]);
});

test('member users cannot access user management', function () {
    $member = createTestMember();
    
    $response = $this->actingAs($member)->get(route('admin.users.admins'));
    
    $response->assertStatus(403);
});

test('guests cannot access user management', function () {
    $response = $this->get(route('admin.users.admins'));
    
    $response->assertRedirect(route('login'));
});

// Public Access Tests (should be accessible to all)
test('guests can view public blog list', function () {
    $response = $this->get(route('blog'));
    
    $response->assertStatus(200);
});

test('guests can view individual blog posts', function () {
    $post = createPublishedPost();
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertStatus(200);
});

test('guests can view author profiles', function () {
    $author = createTestAdmin();
    
    $response = $this->get(route('author.profile', $author->id));
    
    $response->assertStatus(200);
});

test('member users can view public blog list', function () {
    $member = createTestMember();
    
    $response = $this->actingAs($member)->get(route('blog'));
    
    $response->assertStatus(200);
});

test('admin users can view public blog list', function () {
    $admin = createTestAdmin();
    
    $response = $this->actingAs($admin)->get(route('blog'));
    
    $response->assertStatus(200);
});

// Comment Permissions Tests
test('authenticated users can create comments', function () {
    $member = createTestMember();
    $post = createPublishedPost();
    
    $response = $this->actingAs($member)->post(route('comments.store', $post->slug), [
        'content' => 'Test comment',
    ]);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('comments', [
        'user_id' => $member->id,
        'content' => 'Test comment',
    ]);
});

test('admin users can delete any comment', function () {
    $admin = createTestAdmin();
    $member = createTestMember();
    $post = createPublishedPost();
    
    $comment = \App\Models\Comment::factory()->create([
        'blog_post_id' => $post->id,
        'user_id' => $member->id,
    ]);
    
    $response = $this->actingAs($admin)->delete(route('comments.destroy', $comment));
    
    $response->assertRedirect();
    $this->assertDatabaseMissing('comments', [
        'id' => $comment->id,
    ]);
});

test('member users can only delete their own comments', function () {
    $member1 = createTestMember();
    $member2 = createTestMember();
    $post = createPublishedPost();
    
    $comment = \App\Models\Comment::factory()->create([
        'blog_post_id' => $post->id,
        'user_id' => $member2->id,
    ]);
    
    $response = $this->actingAs($member1)->delete(route('comments.destroy', $comment));
    
    $response->assertStatus(403);
    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
    ]);
});

// Like Permissions Tests
test('authenticated users can like blog posts', function () {
    $member = createTestMember();
    $post = createPublishedPost();
    $response = $this->actingAs($member)->post(route('blog.like.toggle', $post->slug));
    
    $response->assertRedirect();
    $this->assertDatabaseHas('blog_post_likes', [
        'user_id' => $member->id,
        'blog_post_id' => $post->id,
    ]);
});

test('admin users can view blog posts', function () {
    $admin = createTestAdmin();
    $post = createPublishedPost();
    
    $response = $this->actingAs($admin)->post(route('blog.like.toggle', $post->slug));
    
    $response->assertRedirect();
    $this->assertDatabaseHas('blog_post_likes', [
        'user_id' => $admin->id,
        'blog_post_id' => $post->id,
    ]);
});

test('guests cannot like blog posts', function () {
    $post = createPublishedPost();
    
    $response = $this->post(route('blog.like.toggle', $post->slug));
    
    $response->assertRedirect(route('login'));
});

// Follow Permission Tests
test('all authenticated users can follow authors', function () {
    $member = createTestMember();
    $author = createTestAdmin();
    
    $response = $this->actingAs($member)->post(route('user.follow.toggle', $author->id));
    
    $response->assertRedirect();
    expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();
});

test('admin users can follow other admin users', function () {
    $admin1 = createTestAdmin();
    $admin2 = createTestAdmin();
    
    $response = $this->actingAs($admin1)->post(route('user.follow.toggle', $admin2->id));
    
    $response->assertRedirect();
    expect($admin1->following()->where('following_id', $admin2->id)->exists())->toBeTrue();
});

test('guests cannot follow authors', function () {
    $author = createTestAdmin();
    
    $response = $this->post(route('user.follow.toggle', $author->id));
    
    $response->assertRedirect(route('login'));
});

// Edge Cases and Mixed Scenarios
test('role permissions persist across requests', function () {
    $admin = createTestAdmin();
    
    // First request
    $response1 = $this->actingAs($admin)->get(route('dashboard'));
    $response1->assertStatus(200);
    
    // Second request
    $response2 = $this->actingAs($admin)->get(route('admin.blog-posts.index'));
    $response2->assertStatus(200);
});

test('changing user role immediately affects permissions', function () {
    $user = createTestMember(['role' => 'member']);
    
    // Member cannot access dashboard
    $response1 = $this->actingAs($user)->get(route('dashboard'));
    $response1->assertStatus(403);
    
    // Change role to admin
    $user->update(['role' => 'admin']);
    $user->refresh();
    
    // Now can access dashboard
    $response2 = $this->actingAs($user)->get(route('dashboard'));
    $response2->assertStatus(200);
});

test('master admin retains all admin permissions', function () {
    $masterAdmin = createTestMasterAdmin();
    
    // Can access dashboard
    $response1 = $this->actingAs($masterAdmin)->get(route('dashboard'));
    $response1->assertStatus(200);
    
    // Can access blog posts
    $response2 = $this->actingAs($masterAdmin)->get(route('admin.blog-posts.index'));
    $response2->assertStatus(200);
    
    // Can access user management
    $response3 = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
    $response3->assertStatus(200);
});

test('multiple admins can work independently', function () {
    $admin1 = createTestAdmin();
    $admin2 = createTestAdmin();
    
    // Both can access dashboard
    $response1 = $this->actingAs($admin1)->get(route('dashboard'));
    $response1->assertStatus(200);
    
    $response2 = $this->actingAs($admin2)->get(route('dashboard'));
    $response2->assertStatus(200);
    
    // Both can create posts
    $this->actingAs($admin1)->post(route('admin.blog-posts.store'), [
        'title' => 'Admin 1 Post',
        'excerpt' => 'Excerpt',
        'content' => 'Content',
        'is_published' => true,
    ]);
    
    $this->actingAs($admin2)->post(route('admin.blog-posts.store'), [
        'title' => 'Admin 2 Post',
        'excerpt' => 'Excerpt',
        'content' => 'Content',
        'is_published' => true,
    ]);
    
    $this->assertDatabaseHas('blog_posts', ['title' => 'Admin 1 Post', 'user_id' => $admin1->id]);
    $this->assertDatabaseHas('blog_posts', ['title' => 'Admin 2 Post', 'user_id' => $admin2->id]);
});

test('role-based access works with 2FA enabled', function () {
    // Test that users with 2FA confirmed can access protected routes
    // Note: EnsureTwoFactorEnabled middleware is disabled for role-based access tests
    $admin = createTestAdmin(); // Has 2FA by default
    
    expect($admin->two_factor_confirmed_at)->not->toBeNull();
    
    // Admin with 2FA should be able to access dashboard (2FA middleware disabled in these tests)
    $response = $this->actingAs($admin)->get(route('dashboard'));
    $response->assertStatus(200);
});

test('unauthenticated requests to protected routes redirect to login', function () {
    $routes = [
        route('dashboard'),
        route('admin.blog-posts.index'),
        route('admin.blog-posts.create'),
        route('admin.users.admins'),
    ];
    
    foreach ($routes as $route) {
        $response = $this->get($route);
        $response->assertRedirect(route('login'));
    }
});
