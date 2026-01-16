<?php

/**
 * Blog Post Rate Limiting Test Suite
 *
 * Tests rate limiting functionality for blog post creation and updates
 * to prevent abuse and ensure system stability.
 */

use App\Models\BlogPost;
use App\Models\User;

// Rate limiting thresholds
const RATE_LIMIT_MAX_REQUESTS = 10;

describe('Blog Post Creation Rate Limiting', function () {
    beforeEach(function () {
        $this->validPostData = [
            'title' => 'Test Post',
            'excerpt' => 'Test excerpt for rate limit test',
            'content' => 'Test content for rate limit test',
            'is_featured' => false,
            'is_published' => false,
        ];
    });

    it('allows up to 10 blog post creation requests per minute', function () {
        $admin = createTestAdmin();

        // Make 10 requests - all should succeed
        for ($i = 0; $i < RATE_LIMIT_MAX_REQUESTS; $i++) {
            $data = array_merge($this->validPostData, ['title' => "Test Post {$i}"]);
            $response = authenticatedPost($admin, route('admin.blog-posts.store'), $data);

            expect($response->status())->toBe(302); // Redirect on success
        }

        // Verify all 10 posts were created
        expect(BlogPost::where('user_id', $admin->id)->count())->toBe(RATE_LIMIT_MAX_REQUESTS);
    })->group('blog-posts', 'rate-limiting', 'create');

    it('blocks the 11th blog post creation request within a minute', function () {
        $admin = createTestAdmin();

        // Make 10 successful requests
        for ($i = 0; $i < RATE_LIMIT_MAX_REQUESTS; $i++) {
            $data = array_merge($this->validPostData, ['title' => "Test Post {$i}"]);
            authenticatedPost($admin, route('admin.blog-posts.store'), $data);
        }

        // 11th request should be rate limited
        $data = array_merge($this->validPostData, ['title' => 'Test Post 11']);
        $response = authenticatedPost($admin, route('admin.blog-posts.store'), $data);

        expect($response->status())->toBe(429) // Too Many Requests
            ->and(BlogPost::where('user_id', $admin->id)->count())->toBe(RATE_LIMIT_MAX_REQUESTS);
    })->group('blog-posts', 'rate-limiting', 'create', 'blocked');

    it('applies rate limiting per user independently', function () {
        $admin1 = createTestAdmin(['email' => 'admin1@test.com']);
        $admin2 = createTestAdmin(['email' => 'admin2@test.com']);

        // Admin 1 makes 10 requests
        for ($i = 0; $i < RATE_LIMIT_MAX_REQUESTS; $i++) {
            $data = array_merge($this->validPostData, ['title' => "Admin 1 Post {$i}"]);
            authenticatedPost($admin1, route('admin.blog-posts.store'), $data);
        }

        // Admin 2 should still be able to make requests (independent rate limit)
        $data = array_merge($this->validPostData, ['title' => 'Admin 2 Post']);
        $response = authenticatedPost($admin2, route('admin.blog-posts.store'), $data);

        expect($response->status())->toBe(302) // Success
            ->and(BlogPost::where('user_id', $admin1->id)->count())->toBe(RATE_LIMIT_MAX_REQUESTS)
            ->and(BlogPost::where('user_id', $admin2->id)->count())->toBe(1);
    })->group('blog-posts', 'rate-limiting', 'create', 'per-user');
});

describe('Blog Post Update Rate Limiting', function () {
    beforeEach(function () {
        $this->validUpdateData = [
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'is_featured' => false,
            'is_published' => false,
        ];
    });

    it('allows up to 10 blog post update requests per minute', function () {
        $admin = createTestAdmin();
        $post = BlogPost::factory()->create(['user_id' => $admin->id]);

        // Make 10 update requests - all should succeed
        for ($i = 0; $i < RATE_LIMIT_MAX_REQUESTS; $i++) {
            $data = array_merge($this->validUpdateData, ['title' => "Updated Title {$i}"]);
            $response = authenticatedPut($admin, route('admin.blog-posts.update', $post), $data);

            expect($response->status())->toBe(302); // Redirect on success
        }

        $post->refresh();
        expect($post->title)->toBe('Updated Title 9');
    })->group('blog-posts', 'rate-limiting', 'update');

    it('blocks the 11th blog post update request within a minute', function () {
        $admin = createTestAdmin();
        $post = BlogPost::factory()->create(['user_id' => $admin->id]);

        // Make 10 successful update requests
        for ($i = 0; $i < RATE_LIMIT_MAX_REQUESTS; $i++) {
            $data = array_merge($this->validUpdateData, ['title' => "Updated Title {$i}"]);
            authenticatedPut($admin, route('admin.blog-posts.update', $post), $data);
        }

        // 11th request should be rate limited
        $data = array_merge($this->validUpdateData, ['title' => 'This should be blocked']);
        $response = authenticatedPut($admin, route('admin.blog-posts.update', $post), $data);

        $post->refresh();
        
        expect($response->status())->toBe(429) // Too Many Requests
            ->and($post->title)->not->toBe('This should be blocked');
    })->group('blog-posts', 'rate-limiting', 'update', 'blocked');
});

describe('Blog Post Rate Limiting - Other Operations', function () {
    it('does not rate limit GET requests (viewing posts)', function () {
        $admin = createTestAdmin();
        BlogPost::factory()->count(15)->create(['user_id' => $admin->id]);

        // Make 15 GET requests - all should succeed (no rate limit on reads)
        for ($i = 0; $i < 15; $i++) {
            $response = authenticatedGet($admin, route('admin.blog-posts.index'));
            expect($response->status())->toBe(200);
        }
    })->group('blog-posts', 'rate-limiting', 'no-limit', 'get');

    it('does not rate limit DELETE requests', function () {
        $admin = createTestAdmin();
        $posts = BlogPost::factory()->count(15)->create(['user_id' => $admin->id]);

        // Delete 15 posts - all should succeed (no rate limit on deletes)
        foreach ($posts as $post) {
            $response = authenticatedDelete($admin, route('admin.blog-posts.destroy', $post));
            expect($response->status())->toBe(302);
        }

        expect(BlogPost::where('user_id', $admin->id)->count())->toBe(0);
    })->group('blog-posts', 'rate-limiting', 'no-limit', 'delete');
});
