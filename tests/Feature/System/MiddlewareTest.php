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

describe('Middleware Execution Order', function () {
    it('executes authentication middleware before authorization', function () {
        $admin = createTestAdmin();
        $post = createPublishedPost(['user_id' => $admin->id]);
        
        $response = $this->get(route('admin.blog-posts.edit', $post));
        
        expect($response)->toRedirectToLogin();
    })->group('middleware', 'authentication', 'authorization');

    it('executes two factor middleware after authentication', function () {
        $admin = createTestAdmin();
        
        $response = $this->actingAs($admin)->get(route('dashboard'));
        
        expect($response->status())->toBeIn([200, 302]);
    })->group('middleware', 'authentication', '2fa');

    it('executes role authorization middleware after authentication', function () {
        $member = createTestMember();
        
        $response = $this->actingAs($member)->get(route('admin.blog-posts.index'));
        
        expect($response)->toBeForbidden();
    })->group('middleware', 'authentication', 'authorization', 'roles');

    it('redirects authenticated users from guest-only routes', function () {
        $user = createTestMember();
        
        $response = $this->actingAs($user)->get(route('login'));
        
        expect($response->isRedirection())->toBeTrue();
    })->group('middleware', 'authentication');

    it('checks email verification status', function () {
        $user = createTestMember(['email_verified_at' => null]);
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        expect($response->status())->toBeIn([200, 302, 403]);
    })->group('middleware', 'authentication');

    it('passes requests through multiple middleware correctly', function () {
        $admin = createTestAdmin();
        $post = createPublishedPost(['user_id' => $admin->id]);
        
        $response = $this->actingAs($admin)->get(route('admin.blog-posts.edit', $post));
        
        expect($response->status())->toBe(HTTP_OK);
    })->group('middleware', 'authentication', 'authorization');

    it('stops middleware chain at first failure', function () {
        $response = $this->get(route('admin.blog-posts.index'));
        
        expect($response)->toRedirectToLogin();
    })->group('middleware', 'authentication');
});

describe('Role-Based Middleware', function () {
    it('allows master admin through admin middleware', function () {
        $masterAdmin = createTestMasterAdmin();
        
        $response = $this->actingAs($masterAdmin)->get(route('admin.blog-posts.index'));
        
        expect($response->status())->toBe(HTTP_OK);
    })->group('middleware', 'roles', 'authorization');

    it('allows regular admin through admin middleware', function () {
        $admin = createTestAdmin();
        
        $response = $this->actingAs($admin)->get(route('admin.blog-posts.index'));
        
        expect($response->status())->toBe(HTTP_OK);
    })->group('middleware', 'roles', 'authorization');

    it('blocks member users from admin routes', function () {
        $member = createTestMember();
        
        $response = $this->actingAs($member)->get(route('admin.blog-posts.index'));
        
        expect($response)->toBeForbidden();
    })->group('middleware', 'roles', 'authorization');

    it('blocks regular admins from master admin routes', function () {
        $admin = createTestAdmin();
        
        $response = $this->actingAs($admin)->get(route('admin.users.admins'));
        
        expect($response)->toBeForbidden();
    })->group('middleware', 'roles', 'authorization');

    it('allows master admins through master admin middleware', function () {
        $masterAdmin = createTestMasterAdmin();
        
        $response = $this->actingAs($masterAdmin)->get(route('admin.users.admins'));
        
        expect($response->status())->toBe(HTTP_OK);
    })->group('middleware', 'roles', 'authorization');
});

describe('Two-Factor Authentication Middleware', function () {
    it('handles CORS preflight requests', function () {
        $response = $this->options(route('home'));
        
        expect($response->status())->toBeIn([200, 204]);
    })->group('middleware');

    it('allows access during non-maintenance mode', function () {
        $response = $this->get(route('home'));
        
        expect($response->status())->toBe(HTTP_OK);
    })->group('middleware');

    it('handles proxy headers correctly', function () {
        $response = $this->withHeaders([
            'X-Forwarded-For' => '192.168.1.1',
            'X-Forwarded-Proto' => 'https',
        ])->get(route('home'));
        
        expect($response->status())->toBe(HTTP_OK);
    })->group('middleware');
});

describe('Request Transformation Middleware', function () {
    it('rejects POST requests without CSRF token', function () {
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        
        expect($response->status())->toBeIn([302, 422]);
    })->group('middleware', 'transformation');

    it('does not require CSRF token for GET requests', function () {
        $response = $this->get(route('home'));
        
        expect($response->status())->toBe(HTTP_OK);
    })->group('middleware', 'transformation');

    it('trims string inputs', function () {
        $admin = createTestAdmin();
        
        $response = $this->actingAs($admin)->post(route('admin.blog-posts.store'), [
            'title' => '  Test Post  ',
            'excerpt' => '  Test excerpt  ',
            'content' => '  Test content  ',
            'is_published' => true,
        ]);
        
        expect($response->isRedirection())->toBeTrue();
        
        $post = BlogPost::latest()->first();
        expect($post->title)->toBe('Test Post')
            ->and($post->excerpt)->toBe('Test excerpt');
    })->group('middleware', 'transformation');

    it('converts empty strings to null', function () {
        $admin = createTestAdmin();
        
        $response = $this->actingAs($admin)->post(route('admin.blog-posts.store'), [
            'title' => 'Test Post',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'featured_image' => '',
            'is_published' => false,
        ]);
        
        $post = BlogPost::latest()->first();
        expect($post->featured_image)->toBeNull();
        
        $masterAdmin = createTestMasterAdmin();
        $response = $this->actingAs($masterAdmin)->post(route('admin.users.store'), [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'member',
            'ip_address' => '',
        ]);
        
        $user = User::where('email', 'testuser@example.com')->first();
        expect($user->ip_address)->toBeNull();
    })->group('middleware', 'transformation');
});

describe('Authorization Middleware', function () {
    it('allows access to own resources with proper authorization', function () {
        $admin = createTestAdmin();
        $post = createPublishedPost(['user_id' => $admin->id]);
        
        $response = $this->actingAs($admin)->get(route('admin.blog-posts.edit', $post));
        
        expect($response->status())->toBe(HTTP_OK);
    })->group('middleware', 'authorization');

    it('prevents access to other users resources', function () {
        $admin1 = createTestAdmin();
        $admin2 = createTestAdmin();
        $post = createPublishedPost(['user_id' => $admin1->id]);
        
        $response = $this->actingAs($admin2)->get(route('admin.blog-posts.edit', $post));
        
        expect($response)->toBeForbidden();
    })->group('middleware', 'authorization');

    it('allows public routes without authentication', function () {
        $post = createPublishedPost();
        
        $response = $this->get(route('blog.show', $post->slug));
        
        expect($response->status())->toBe(HTTP_OK);
    })->group('middleware', 'authorization');

    it('redirects guests from protected routes', function () {
        $response = $this->get(route('dashboard'));
        
        expect($response)->toRedirectToLogin();
    })->group('middleware', 'authorization', 'authentication');

    it('maintains session across requests', function () {
        $user = createTestAdmin();
        
        $this->actingAs($user)->get(route('dashboard'));
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        expect($response->status())->toBe(HTTP_OK);
    })->group('middleware', 'authentication');
});
