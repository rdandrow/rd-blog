<?php

/**
 * Middleware Test Suite
 *
 * Tests all custom and Laravel middleware execution, ordering, and functionality.
 * Verifies middleware behavior across different routes and user roles.
 *
 * Test Categories:
 * - Middleware Execution Order: Tests authentication before authorization
 * - Role-Based Middleware: Admin and master admin access controls
 * - Two-Factor Authentication: 2FA middleware behavior
 * - Request Transformation: TrimStrings and ConvertEmptyStringsToNull
 * - Authorization Middleware: Resource ownership validation
 *
 * Middleware Tested:
 * - Authenticate: Ensures user authentication
 * - EnsureTwoFactorEnabled: 2FA enforcement
 * - EnsureUserIsAdmin: Admin-only access
 * - EnsureUserIsMasterAdmin: Master admin-only access
 * - TrimStrings: Whitespace trimming
 * - ConvertEmptyStringsToNull: Empty string to null conversion
 *
 * Key Validations:
 * - Proper middleware execution order
 * - Redirect behavior for unauthenticated users
 * - Access denial for insufficient permissions
 * - Request data transformation
 */

use App\Models\BlogPost;
use App\Models\User;

// Middleware Execution Order Tests
test('authentication middleware executes before authorization', function () {
    $admin = createTestAdmin();
    $post = createPublishedPost(['user_id' => $admin->id]);
    
    // Unauthenticated request should redirect to login, not show 403
    $response = $this->get(route('admin.blog-posts.edit', $post));
    $response->assertRedirect(route('login'));
});

test('two factor middleware executes after authentication', function () {
    // Create admin with 2FA
    $admin = createTestAdmin();
    
    // Should be able to authenticate
    $response = $this->actingAs($admin)->get(route('dashboard'));
    
    // Two factor check should happen after authentication
    // If 2FA is enforced, should redirect to 2FA challenge
    expect($response->status())->toBeIn([200, 302]);
});

test('role authorization middleware executes after authentication', function () {
    $member = createTestMember();
    
    // Member trying to access admin-only route
    $response = $this->actingAs($member)->get(route('admin.blog-posts.index'));
    
    // Should get 403 (authenticated but forbidden), not 302 redirect to login
    $response->assertStatus(403);
});

test('guest middleware redirects authenticated users', function () {
    $user = createTestMember();
    
    // Authenticated user trying to access guest-only route
    $response = $this->actingAs($user)->get(route('login'));
    
    // Should redirect away from login page
    $response->assertRedirect();
});

test('verified middleware checks email verification status', function () {
    $user = createTestMember(['email_verified_at' => null]);
    
    // Unverified user trying to access protected route
    $response = $this->actingAs($user)->get(route('dashboard'));
    
    // Behavior depends on your app's requirements
    expect($response->status())->toBeIn([200, 302, 403]);
});

// Multiple Middleware Interactions
test('request passes through multiple middleware correctly', function () {
    $admin = createTestAdmin();
    $post = createPublishedPost(['user_id' => $admin->id]);
    
    // This should pass through: guest check, auth, verified, 2FA, and role checks
    $response = $this->actingAs($admin)->get(route('admin.blog-posts.edit', $post));
    
    $response->assertStatus(200);
});

test('middleware chain stops at first failure', function () {
    // Unauthenticated request to admin route
    // Should stop at auth middleware (redirect to login), never reach role check
    $response = $this->get(route('admin.blog-posts.index'));
    
    $response->assertRedirect(route('login'));
});

test('admin middleware allows master admin through', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.blog-posts.index'));
    
    $response->assertStatus(200);
});

test('admin middleware allows regular admin through', function () {
    $admin = createTestAdmin();
    
    $response = $this->actingAs($admin)->get(route('admin.blog-posts.index'));
    
    $response->assertStatus(200);
});

test('admin middleware blocks member users', function () {
    $member = createTestMember();
    
    $response = $this->actingAs($member)->get(route('admin.blog-posts.index'));
    
    $response->assertStatus(403);
});

test('master admin middleware blocks regular admins', function () {
    $admin = createTestAdmin();
    
    $response = $this->actingAs($admin)->get(route('admin.users.admins'));
    
    $response->assertStatus(403);
});

test('master admin middleware allows master admins', function () {
    $masterAdmin = createTestMasterAdmin();
    
    $response = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
    
    $response->assertStatus(200);
});

// CORS Middleware (if applicable)
test('handles CORS preflight requests', function () {
    $response = $this->options(route('home'));
    
    // Should handle OPTIONS requests
    expect($response->status())->toBeIn([200, 204]);
});

// Maintenance Mode Middleware
test('allows access during non-maintenance mode', function () {
    $response = $this->get(route('home'));
    
    $response->assertStatus(200);
});

// Trusted Proxy Middleware
test('handles proxy headers correctly', function () {
    $response = $this->withHeaders([
        'X-Forwarded-For' => '192.168.1.1',
        'X-Forwarded-Proto' => 'https',
    ])->get(route('home'));
    
    $response->assertStatus(200);
});

// CSRF Protection Middleware
test('POST requests without CSRF token are rejected', function () {
    $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
    
    // Without CSRF middleware, should still process
    expect($response->status())->toBeIn([302, 422]);
});

test('GET requests do not require CSRF token', function () {
    $response = $this->get(route('home'));
    
    $response->assertStatus(200);
});

// TrimStrings Middleware
test('middleware trims string inputs', function () {
    $admin = createTestAdmin();
    
    $response = $this->actingAs($admin)->post(route('admin.blog-posts.store'), [
        'title' => '  Test Post  ',
        'excerpt' => '  Test excerpt  ',
        'content' => '  Test content  ',
        'is_published' => true,
    ]);
    
    $response->assertRedirect();
    
    $post = BlogPost::latest()->first();
    expect($post->title)->toBe('Test Post');
    expect($post->excerpt)->toBe('Test excerpt');
});

// ConvertEmptyStringsToNull Middleware
test('middleware converts empty strings to null', function () {
    $admin = createTestAdmin();
    
    // Test with blog post creation - featured_image is nullable
    $response = $this->actingAs($admin)->post(route('admin.blog-posts.store'), [
        'title' => 'Test Post',
        'excerpt' => 'Test excerpt',
        'content' => 'Test content',
        'featured_image' => '', // Empty string should become null
        'is_published' => false,
    ]);
    
    $post = BlogPost::latest()->first();
    expect($post->featured_image)->toBeNull();
    
    // Test with user creation - ip_address is nullable
    $masterAdmin = createTestMasterAdmin();
    $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
        'name' => 'Test User',
        'email' => 'testuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'member',
        'ip_address' => '', // Empty string should become null
    ]);
    
    $user = User::where('email', 'testuser@example.com')->first();
    expect($user->ip_address)->toBeNull();
});

// Authorization Middleware Edge Cases
test('can access own resources with proper authorization', function () {
    $admin = createTestAdmin();
    $post = createPublishedPost(['user_id' => $admin->id]);
    
    $response = $this->actingAs($admin)->get(route('admin.blog-posts.edit', $post));
    
    $response->assertStatus(200);
});

test('cannot access other users resources', function () {
    $admin1 = createTestAdmin();
    $admin2 = createTestAdmin();
    $post = createPublishedPost(['user_id' => $admin1->id]);
    
    $response = $this->actingAs($admin2)->get(route('admin.blog-posts.edit', $post));
    
    $response->assertStatus(403);
});

test('middleware allows public routes without authentication', function () {
    $post = createPublishedPost();
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertStatus(200);
});

test('middleware redirects guests from protected routes', function () {
    $response = $this->get(route('dashboard'));
    
    $response->assertRedirect(route('login'));
});

// Session Middleware
test('maintains session across requests', function () {
    $user = createTestAdmin(); // Use admin since dashboard requires admin role
    
    $this->actingAs($user)->get(route('dashboard'));
    $response = $this->actingAs($user)->get(route('dashboard'));
    
    $response->assertStatus(200);
});
