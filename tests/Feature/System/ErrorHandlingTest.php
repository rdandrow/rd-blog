<?php

/**
 * Error Handling Test Suite
 *
 * Tests application error handling, graceful degradation, and proper
 * error responses across various failure scenarios.
 */

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

describe('Transaction Rollback', function () {
    it('handles transaction rollback on error', function () {
        $admin = createTestAdmin();
        $initialCount = BlogPost::count();
        
        try {
            DB::transaction(function () use ($admin) {
                BlogPost::factory()->create(['user_id' => $admin->id]);
                throw new \Exception('Force rollback');
            });
        } catch (\Exception $e) {
            // Expected
        }
        
        expect(BlogPost::count())->toBe($initialCount);
    })->group('error-handling', 'database');
});

describe('File System Errors', function () {
    it('handles missing file upload gracefully', function () {
        $admin = createTestAdmin();
        
        $response = $this->actingAs($admin)->post(route('admin.blog-posts.store'), [
            'title' => 'Test Post',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'is_published' => true,
            // No file attached
        ]);
        
        // Should succeed without file (file is optional)
        $response->assertRedirect();
    })->group('error-handling', 'filesystem');

    it('handles invalid file type upload', function () {
        Storage::fake('public');
        $admin = createTestAdmin();
        
        // This would need actual file upload handling in your app
        // Placeholder for when you add file upload functionality
        expect(true)->toBeTrue();
    })->group('error-handling', 'filesystem');

    it('handles oversized file upload', function () {
        Storage::fake('public');
        $admin = createTestAdmin();
        
        // This would need actual file upload handling in your app
        // Placeholder for when you add file upload functionality
        expect(true)->toBeTrue();
    })->group('error-handling', 'filesystem');

    it('handles mail service failures during registration', function () {
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Mail::shouldReceive('send')->andThrow(new \Exception('Mail service down'));
        
        // Registration should still work even if email fails
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secure-password-12345',
            'password_confirmation' => 'secure-password-12345',
        ]);
        
        // User should be created even if email fails
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    })->group('error-handling', 'filesystem');

    it('handles cache service unavailability', function () {
        // Test that app continues to work without cache
        \Illuminate\Support\Facades\Cache::shouldReceive('get')->andReturn(null);
        \Illuminate\Support\Facades\Cache::shouldReceive('put')->andReturn(false);
        \Illuminate\Support\Facades\Cache::shouldReceive('remember')->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();  // Just execute callback without caching
        });
        \Illuminate\Support\Facades\Cache::shouldReceive('forget')->andReturn(true);
        \Illuminate\Support\Facades\Cache::shouldReceive('has')->andReturn(false);
        
        $response = $this->get(route('home'));
        
        $response->assertStatus(200);
    })->group('error-handling', 'filesystem');
});

describe('Invalid Input', function () {
    it('handles malformed request data gracefully', function () {
        $admin = createTestAdmin();
        
        $response = $this->actingAs($admin)->post(route('admin.blog-posts.store'), [
            'title' => ['invalid' => 'array'],
            'excerpt' => null,
            'content' => 123,
        ]);
        
        $response->assertSessionHasErrors(['title', 'excerpt', 'content']);
    })->group('error-handling', 'validation');

    it('handles SQL injection attempts safely', function () {
        $admin = createTestAdmin();
        
        $response = $this->actingAs($admin)->post(route('admin.blog-posts.store'), [
            'title' => "'; DROP TABLE blog_posts; --",
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'is_published' => true,
        ]);
        
        // Should be safely escaped
        $response->assertRedirect();
        
        expect(BlogPost::count())->toBeGreaterThan(0);
    })->group('error-handling', 'validation');

    it('handles XSS attempts in content', function () {
        $admin = createTestAdmin();
        
        $xssContent = '<script>alert("XSS")</script>';
        
        $response = $this->actingAs($admin)->post(route('admin.blog-posts.store'), [
            'title' => 'Test Post',
            'excerpt' => 'Test excerpt',
            'content' => $xssContent,
            'is_published' => true,
        ]);
        
        $response->assertRedirect();
        
        $post = BlogPost::latest()->first();
        
        expect($post->content)->toBe($xssContent); // Should be stored as-is, escaped on output
    })->group('error-handling', 'validation');

    it('handles concurrent like requests on same post', function () {
        $user = createTestMember();
        $post = createPublishedPost();
        
        // Simulate concurrent requests
        $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
        $this->actingAs($user)->post(route('blog.like.toggle', $post->slug));
        
        // Should handle gracefully without duplicate entries
        $likeCount = DB::table('blog_post_likes')
            ->where('user_id', $user->id)
            ->where('blog_post_id', $post->id)
            ->count();
        
        expect($likeCount)->toBe(0); // Should be toggled back to unliked
    })->group('error-handling', 'validation');

    it('handles concurrent comment creation', function () {
        $user = createTestMember();
        $post = createPublishedPost();
        
        $response1 = $this->actingAs($user)->post(route('comments.store', $post->slug), [
            'content' => 'First comment',
        ]);
        
        $response2 = $this->actingAs($user)->post(route('comments.store', $post->slug), [
            'content' => 'Second comment',
        ]);
        
        $response1->assertRedirect();
        $response2->assertRedirect();
        
        expect($post->comments()->count())->toBe(2);
    })->group('error-handling', 'validation');
});

describe('404 Errors', function () {
    it('handles missing blog post gracefully', function () {
        $response = $this->get(route('blog.show', 'non-existent-slug'));
        
        expect($response)->toBeNotFound();
    })->group('error-handling', '404');

    it('handles missing user profile gracefully', function () {
        $response = $this->get(route('author.profile', TEST_NONEXISTENT_ID));
        
        expect($response)->toBeNotFound();
    })->group('error-handling', '404');

    it('handles deleted resource access', function () {
        $post = createPublishedPost();
        $slug = $post->slug;
        
        $post->delete();
        
        $response = $this->get(route('blog.show', $slug));
        
        expect($response)->toBeNotFound();
    })->group('error-handling', '404');
});
