<?php

/**
 * Role-Based Access Control Test Suite
 *
 * Tests comprehensive role-based access controls across all user roles
 * (master_admin, admin, member) and verifies proper authorization enforcement.
 */

use App\Models\BlogPost;
use App\Models\User;

// Disable 2FA middleware for these tests to focus on role-based access
beforeEach(function () {
    $this->withoutMiddleware(\App\Http\Middleware\EnsureTwoFactorEnabled::class);
});

describe('User Role Identification', function () {
    it('correctly identifies master admin role', function () {
        $masterAdmin = createTestMasterAdmin();
        
        expect($masterAdmin)
            ->isMasterAdmin()->toBeTrue()
            ->isAdmin()->toBeTrue() // Master admin is also admin
            ->isRegularAdmin()->toBeFalse() // Regular admin excludes master_admin
            ->isMember()->toBeFalse()
            ->role->toBe('master_admin'); // Enum value in database
    })->group('roles', 'identification');

    it('correctly identifies admin role', function () {
        $admin = createTestAdmin();
        
        expect($admin)
            ->isMasterAdmin()->toBeFalse()
            ->isAdmin()->toBeTrue()
            ->isRegularAdmin()->toBeTrue()
            ->isMember()->toBeFalse()
            ->role->toBe('admin');
    })->group('roles', 'identification');

    it('correctly identifies member role', function () {
        $member = createTestMember();
        
        expect($member)
            ->isMasterAdmin()->toBeFalse()
            ->isAdmin()->toBeFalse()
            ->isRegularAdmin()->toBeFalse()
            ->isMember()->toBeTrue()
            ->role->toBe('member');
    })->group('roles', 'identification');
});

describe('Dashboard Access', function () {
    it('allows admin roles to access dashboard', function ($userFactory) {
        $user = is_callable($userFactory) ? $userFactory() : $userFactory;
        $response = authenticatedGet($user, route('dashboard'));
        
        expect($response)->toBeSuccessfulInertiaResponse('Dashboard');
    })->with('admin_roles')
      ->group('dashboard', 'access-control', 'authorized');

    it('denies member access to dashboard', function () {
        $response = authenticatedGet(createTestMember(), route('dashboard'));
        
        expect($response)->toBeForbidden(); // Forbidden - members don't have dashboard access
    })->group('dashboard', 'access-control', 'unauthorized');

    it('redirects guests from dashboard', function () {
        expect($this->get(route('dashboard')))->toRedirectToLogin();
    })->group('dashboard', 'access-control', 'guest');
});

describe('Blog Post Management Access', function () {
    it('allows admin roles to access blog post routes', function ($userFactory, string $route, string $method) {
        $user = is_callable($userFactory) ? $userFactory() : $userFactory;
        
        if ($method === 'GET') {
            $response = authenticatedGet($user, route($route));
            $response->assertStatus(HTTP_OK);
        } else {
            $response = authenticatedPost($user, route($route), [
                'title' => 'Test Post',
                'excerpt' => 'Test excerpt',
                'content' => 'Test content',
                'is_published' => true,
            ]);
            $response->assertRedirect();
            $this->assertDatabaseHas('blog_posts', [
                'title' => 'Test Post',
                'user_id' => $user->id,
            ]);
        }
    })->with('admin_roles')
      ->with([
          'blog post index' => ['admin.blog-posts.index', 'GET'],
          'blog post create' => ['admin.blog-posts.create', 'GET'],
          'blog post store' => ['admin.blog-posts.store', 'POST'],
      ])
      ->group('blog-posts', 'access-control', 'authorized');

    it('denies members from accessing blog post management', function (string $route, string $method, array $data = []) {
        $member = createTestMember();
        
        if ($method === 'GET') {
            $response = authenticatedGet($member, route($route));
        } else {
            $response = authenticatedPost($member, route($route), $data);
        }
        
        expect($response)->toBeForbidden();
        
        if ($method === 'POST') {
            $this->assertDatabaseMissing('blog_posts', [
                'title' => 'Test Post',
                'user_id' => $member->id,
            ]);
        }
    })->with([
        'blog post index' => ['admin.blog-posts.index', 'GET'],
        'blog post create page' => ['admin.blog-posts.create', 'GET'],
        'blog post creation' => ['admin.blog-posts.store', 'POST', [
            'title' => 'Test Post',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'is_published' => true,
        ]],
    ])->group('blog-posts', 'access-control', 'unauthorized');

    it('redirects guests from blog post management', function () {
        expect($this->get(route('admin.blog-posts.index')))->toRedirectToLogin();
    })->group('blog-posts', 'access-control', 'guest');
});

describe('Blog Post Editing Authorization', function () {
    it('allows admins to edit only their own posts', function () {
        $admin1 = createTestAdmin();
        $admin2 = createTestAdmin();
        $post = BlogPost::factory()->create(['user_id' => $admin2->id]);
        
        $response = $this->actingAs($admin1)
            ->get(route('admin.blog-posts.edit', $post));
        
        expect($response)->toBeForbidden();
    })->group('blog-posts', 'authorization', 'own-content');

    it('allows master admin to edit only their own posts', function () {
        $masterAdmin = createTestMasterAdmin();
        $admin = createTestAdmin();
        $post = BlogPost::factory()->create(['user_id' => $admin->id]);
        
        $response = $this->actingAs($masterAdmin)
            ->get(route('admin.blog-posts.edit', $post));
        
        expect($response)->toBeForbidden();
    })->group('blog-posts', 'authorization', 'master-admin');
});

describe('User Management Access (Master Admin Only)', function () {
    it('allows master admin to access admin users list', function () {
        $response = $this->actingAs(createTestMasterAdmin())
            ->get(route('admin.users.admins'));
        
        expect($response)->toBeSuccessfulInertiaResponse('Admin/Users/AdminUsers');
    })->group('user-management', 'access-control', 'authorized');

    it('allows master admin to access member users list', function () {
        $response = $this->actingAs(createTestMasterAdmin())
            ->get(route('admin.users.members'));
        
        expect($response)->toBeSuccessfulInertiaResponse('Admin/Users/MemberUsers');
    })->group('user-management', 'access-control', 'authorized');

    it('allows master admin to create new users', function () {
        $response = $this->actingAs(createTestMasterAdmin())
            ->post(route('admin.users.store'), [
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
    })->group('user-management', 'crud', 'authorized');

    it('allows master admin to update user roles', function () {
        $user = createTestMember(['role' => 'member']);
        
        $response = $this->actingAs(createTestMasterAdmin())
            ->patch(route('admin.users.updateRole', $user), [
                'role' => 'admin',
            ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'admin',
        ]);
    })->group('user-management', 'crud', 'authorized');

    it('allows master admin to delete users', function () {
        $user = createTestMember();
        
        $response = $this->actingAs(createTestMasterAdmin())
            ->delete(route('admin.users.destroy', $user));
        
        $response->assertRedirect();
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    })->group('user-management', 'crud', 'authorized');

    it('denies non-master-admin roles from accessing user management', function ($userFactory) {
        $user = is_callable($userFactory) ? $userFactory() : $userFactory;
        $response = $this->actingAs($user)->get(route('admin.users.admins'));
        
        expect($response)->toBeForbidden();
    })->with('unauthorized_for_master_admin')
      ->group('user-management', 'access-control', 'unauthorized');

    it('denies non-master-admin roles from creating users', function ($userFactory) {
        $user = is_callable($userFactory) ? $userFactory() : $userFactory;
        
        $response = $this->actingAs($user)->post(route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        expect($response)->toBeForbidden();
        $this->assertDatabaseMissing('users', [
            'email' => 'newuser@example.com',
        ]);
    })->with('unauthorized_for_master_admin')
      ->group('user-management', 'crud', 'unauthorized');

    it('denies non-master-admin roles from updating user roles', function ($userFactory) {
        $actor = is_callable($userFactory) ? $userFactory() : $userFactory;
        $targetUser = createTestMember(['role' => 'member']);
        
        $response = $this->actingAs($actor)
            ->patch(route('admin.users.updateRole', $targetUser), [
                'role' => 'admin',
            ]);

        expect($response)->toBeForbidden();
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'role' => 'member', // Role unchanged
        ]);
    })->with('unauthorized_for_master_admin')
      ->group('user-management', 'crud', 'unauthorized');

    it('denies non-master-admin roles from deleting users', function ($userFactory) {
        $actor = is_callable($userFactory) ? $userFactory() : $userFactory;
        $targetUser = createTestMember();
        
        $response = $this->actingAs($actor)
            ->delete(route('admin.users.destroy', $targetUser));

        expect($response)->toBeForbidden();
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
        ]);
    })->with('unauthorized_for_master_admin')
      ->group('user-management', 'crud', 'unauthorized');

    it('redirects guests from user management', function () {
        expect($this->get(route('admin.users.admins')))->toRedirectToLogin();
    })->group('user-management', 'access-control', 'guest');
});

describe('Public Access', function () {
    it('allows guests to view public blog list', function () {
        $response = $this->get(route('blog'));
        
        $response->assertStatus(200);
    })->group('public', 'guest');

    it('allows guests to view individual blog posts', function () {
        $post = createPublishedPost();
        
        $response = $this->get(route('blog.show', $post->slug));
        
        $response->assertStatus(200);
    })->group('public', 'guest');

    it('allows guests to view author profiles', function () {
        $author = createTestAdmin();
        
        $response = $this->get(route('author.profile', $author->id));
        
        $response->assertStatus(200);
    })->group('public', 'guest');

    it('allows all user roles to view public blog list', function ($userFactory) {
        $user = is_callable($userFactory) ? $userFactory() : $userFactory;
        $response = $this->actingAs($user)->get(route('blog'));
        
        $response->assertStatus(200);
    })->with('user_roles')
      ->group('public', 'authenticated');
});

describe('Comment Permissions', function () {
    it('allows authenticated users to create comments', function () {
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
    })->group('comments', 'permissions', 'authorized');

    it('allows admin roles to delete any comment', function ($userFactory) {
        $admin = is_callable($userFactory) ? $userFactory() : $userFactory;
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
    })->with('admin_roles')
      ->group('comments', 'permissions', 'admin');

    it('denies members from deleting other users comments', function () {
        $member1 = createTestMember();
        $member2 = createTestMember();
        $post = createPublishedPost();
        
        $comment = \App\Models\Comment::factory()->create([
            'blog_post_id' => $post->id,
            'user_id' => $member2->id,
        ]);

        $response = $this->actingAs($member1)->delete(route('comments.destroy', $comment));

        expect($response)->toBeForbidden();
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
        ]);
    })->group('comments', 'permissions', 'unauthorized');
});

describe('Like Permissions', function () {
    it('allows all authenticated users to like blog posts', function ($userFactory) {
        $user = is_callable($userFactory) ? $userFactory() : $userFactory;
        $post = createPublishedPost();
        
        $response = $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_post_likes', [
            'user_id' => $user->id,
            'blog_post_id' => $post->id,
        ]);
    })->with('user_roles')
      ->group('likes', 'permissions', 'authorized');

    it('redirects guests when attempting to like blog posts', function () {
        $post = createPublishedPost();
        
        expect($this->post(route('blog.like.toggle', $post->slug)))->toRedirectToLogin();
    })->group('likes', 'permissions', 'guest');
});

describe('Follow Permissions', function () {
    it('allows all authenticated users to follow authors', function () {
        $member = createTestMember();
        $author = createTestAdmin();
        
        $response = $this->actingAs($member)->post(route('user.follow.toggle', $author->id));

        $response->assertRedirect();
        expect($member->following()->where('following_id', $author->id)->exists())->toBeTrue();
    })->group('follows', 'permissions', 'authorized');

    it('allows admin users to follow other admin users', function () {
        $admin1 = createTestAdmin();
        $admin2 = createTestAdmin();
        
        $response = $this->actingAs($admin1)->post(route('user.follow.toggle', $admin2->id));

        $response->assertRedirect();
        expect($admin1->following()->where('following_id', $admin2->id)->exists())->toBeTrue();
    })->group('follows', 'permissions', 'authorized');

    it('redirects guests when attempting to follow authors', function () {
        $author = createTestAdmin();
        
        expect($this->post(route('user.follow.toggle', $author->id)))->toRedirectToLogin();
    })->group('follows', 'permissions', 'guest');
});

describe('Edge Cases and Mixed Scenarios', function () {
    it('persists role permissions across multiple requests', function () {
        $admin = createTestAdmin();
        
        // First request
        $response1 = $this->actingAs($admin)->get(route('dashboard'));
        $response1->assertStatus(200);
        
        // Second request
        $response2 = $this->actingAs($admin)->get(route('admin.blog-posts.index'));
        $response2->assertStatus(200);
    })->group('roles', 'edge-cases');

    it('immediately reflects role changes in permissions', function () {
        $user = createTestMember(['role' => 'member']);
        
        // Member cannot access dashboard
        $response1 = $this->actingAs($user)->get(route('dashboard'));
        expect($response1)->toBeForbidden();
        
        // Change role to admin
        $user->update(['role' => 'admin']);
        $user->refresh();
        
        // Now can access dashboard
        $response2 = $this->actingAs($user)->get(route('dashboard'));
        $response2->assertStatus(200);
    })->group('roles', 'edge-cases');

    it('verifies master admin retains all admin permissions', function () {
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
    })->group('roles', 'edge-cases', 'master-admin');

    it('allows multiple admins to work independently', function () {
        $admin1 = createTestAdmin();
        $admin2 = createTestAdmin();
        
        // Both can access dashboard
        $this->actingAs($admin1)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($admin2)->get(route('dashboard'))->assertStatus(200);
        
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
    })->group('roles', 'edge-cases');

    it('verifies role-based access works with 2FA enabled', function () {
        // Test that users with 2FA confirmed can access protected routes
        // Note: EnsureTwoFactorEnabled middleware is disabled for role-based access tests
        $admin = createTestAdmin(); // Has 2FA by default
        
        expect($admin->two_factor_confirmed_at)->not->toBeNull();
        
        // Admin with 2FA should be able to access dashboard (2FA middleware disabled in these tests)
        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertStatus(200);
    })->group('roles', 'edge-cases', '2fa');

    it('redirects unauthenticated requests to login', function () {
        $routes = [
            route('dashboard'),
            route('admin.blog-posts.index'),
            route('admin.blog-posts.create'),
            route('admin.users.admins'),
        ];
        
        foreach ($routes as $route) {
            expect($this->get($route))->toRedirectToLogin();
        }
    })->group('roles', 'edge-cases', 'guest');
});
