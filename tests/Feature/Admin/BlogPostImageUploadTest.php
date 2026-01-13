<?php

/**
 * Blog Post Image Upload Test Suite
 *
 * Tests image upload functionality for markdown editor content,
 * including validation, authorization, and rate limiting.
 */

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

describe('Image Upload for Markdown Content', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('allows admin users to upload valid images', function () {
        $admin = createTestAdmin();
        $image = UploadedFile::fake()->image('test-image.jpg', 800, 600)->size(500); // 500KB

        $response = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
            'image' => $image,
        ]);

        expect($response->status())->toBe(200);

        $data = $response->json();
        expect($data)
            ->toHaveKey('success', true)
            ->toHaveKey('url')
            ->toHaveKey('path');

        // Verify image was stored
        $diskPath = str_replace('/storage/', '', $data['path']);
        Storage::disk('public')->assertExists($diskPath);
    })->group('blog-posts', 'image-upload', 'authorized');

    it('returns image URL in correct format', function () {
        $admin = createTestAdmin();
        $image = UploadedFile::fake()->image('test.png');

        $response = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
            'image' => $image,
        ]);

        $data = $response->json();
        
        expect($data['url'])
            ->toContain('/storage/blog-images/');
        
        expect($data['path'])
            ->toStartWith('/storage/blog-images/');
    })->group('blog-posts', 'image-upload', 'url-format');

    it('accepts various image formats', function ($extension, $mimeType) {
        $admin = createTestAdmin();
        $image = UploadedFile::fake()->image("test.{$extension}")->mimeType($mimeType);

        $response = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
            'image' => $image,
        ]);

        expect($response->status())->toBe(200);
        expect($response->json())->toHaveKey('success', true);
    })->with([
        ['jpg', 'image/jpeg'],
        ['jpeg', 'image/jpeg'],
        ['png', 'image/png'],
        ['gif', 'image/gif'],
        ['webp', 'image/webp'],
    ])->group('blog-posts', 'image-upload', 'formats');
});

describe('Image Upload Validation', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('requires image field', function () {
        $admin = createTestAdmin();

        $response = authenticatedPost($admin, route('admin.blog-posts.upload-image'), []);

        expect($response->status())->toBe(302); // Validation redirect
        $response->assertSessionHasErrors('image');
    })->group('blog-posts', 'image-upload', 'validation');

    it('rejects non-image files', function () {
        $admin = createTestAdmin();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
            'image' => $file,
        ]);

        expect($response->status())->toBe(302);
        $response->assertSessionHasErrors('image');
    })->group('blog-posts', 'image-upload', 'validation', 'rejected');

    it('rejects images exceeding 2MB size limit', function () {
        $admin = createTestAdmin();
        $image = UploadedFile::fake()->image('large-image.jpg')->size(3000); // 3MB

        $response = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
            'image' => $image,
        ]);

        expect($response->status())->toBe(302);
        $response->assertSessionHasErrors('image');
    })->group('blog-posts', 'image-upload', 'validation', 'size-limit');

    it('accepts images at 2MB size limit', function () {
        $admin = createTestAdmin();
        $image = UploadedFile::fake()->image('max-size.jpg')->size(2048); // Exactly 2MB

        $response = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
            'image' => $image,
        ]);

        expect($response->status())->toBe(200);
    })->group('blog-posts', 'image-upload', 'validation', 'size-boundary');

    it('rejects unsupported image formats', function ($extension) {
        $admin = createTestAdmin();
        $file = UploadedFile::fake()->create("image.{$extension}", 100);

        $response = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
            'image' => $file,
        ]);

        expect($response->status())->toBe(302);
        $response->assertSessionHasErrors('image');
    })->with([
        'bmp',
        'svg',
        'tiff',
        'ico',
    ])->group('blog-posts', 'image-upload', 'validation', 'unsupported-formats');
});

describe('Image Upload Authorization', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('allows master admin users to upload images', function () {
        $masterAdmin = createTestMasterAdmin();
        $image = UploadedFile::fake()->image('test.jpg');

        $response = authenticatedPost($masterAdmin, route('admin.blog-posts.upload-image'), [
            'image' => $image,
        ]);

        expect($response->status())->toBe(200);
    })->group('blog-posts', 'image-upload', 'authorization', 'master-admin');

    it('denies member users from uploading images', function () {
        $member = createTestMember();
        $image = UploadedFile::fake()->image('test.jpg');

        $response = authenticatedPost($member, route('admin.blog-posts.upload-image'), [
            'image' => $image,
        ]);

        expect($response)->toBeForbidden();
    })->group('blog-posts', 'image-upload', 'authorization', 'denied');

    it('redirects guests to login', function () {
        $image = UploadedFile::fake()->image('test.jpg');

        $response = $this->post(route('admin.blog-posts.upload-image'), [
            'image' => $image,
        ]);

        expect($response)->toRedirectToLogin();
    })->group('blog-posts', 'image-upload', 'authorization', 'guest');
});

describe('Image Upload Rate Limiting', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('allows up to 20 image upload requests per minute', function () {
        $admin = createTestAdmin();

        // Make 20 requests - all should succeed
        for ($i = 0; $i < 20; $i++) {
            $image = UploadedFile::fake()->image("test-{$i}.jpg");
            
            $response = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
                'image' => $image,
            ]);

            expect($response->status())->toBe(200);
        }

        // Verify all 20 images were uploaded
        expect(count(Storage::disk('public')->allFiles('blog-images')))->toBe(20);
    })->group('blog-posts', 'image-upload', 'rate-limiting');

    it('blocks the 21st image upload request within a minute', function () {
        $admin = createTestAdmin();

        // Make 20 successful requests
        for ($i = 0; $i < 20; $i++) {
            $image = UploadedFile::fake()->image("test-{$i}.jpg");
            authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
                'image' => $image,
            ]);
        }

        // 21st request should be rate limited
        $image = UploadedFile::fake()->image('test-21.jpg');
        $response = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
            'image' => $image,
        ]);

        expect($response->status())->toBe(429); // Too Many Requests
        
        // Verify only 20 images were uploaded
        expect(count(Storage::disk('public')->allFiles('blog-images')))->toBe(20);
    })->group('blog-posts', 'image-upload', 'rate-limiting', 'blocked');

    it('applies rate limiting per user independently', function () {
        $admin1 = createTestAdmin(['email' => 'admin1@test.com']);
        $admin2 = createTestAdmin(['email' => 'admin2@test.com']);

        // Admin 1 makes 20 requests
        for ($i = 0; $i < 20; $i++) {
            $image = UploadedFile::fake()->image("admin1-{$i}.jpg");
            authenticatedPost($admin1, route('admin.blog-posts.upload-image'), [
                'image' => $image,
            ]);
        }

        // Admin 2 should still be able to upload (independent rate limit)
        $image = UploadedFile::fake()->image('admin2-test.jpg');
        $response = authenticatedPost($admin2, route('admin.blog-posts.upload-image'), [
            'image' => $image,
        ]);

        expect($response->status())->toBe(200);
        expect(count(Storage::disk('public')->allFiles('blog-images')))->toBe(21);
    })->group('blog-posts', 'image-upload', 'rate-limiting', 'per-user');
});

describe('Image Upload Storage', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('stores images in blog-images directory', function () {
        $admin = createTestAdmin();
        $image = UploadedFile::fake()->image('test.jpg');

        $response = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
            'image' => $image,
        ]);

        $data = $response->json();
        
        expect($data['path'])->toStartWith('/storage/blog-images/');
        $diskPath = str_replace('/storage/', '', $data['path']);
        Storage::disk('public')->assertExists($diskPath);
    })->group('blog-posts', 'image-upload', 'storage');

    it('generates unique filenames for uploaded images', function () {
        $admin = createTestAdmin();
        
        // Upload same filename twice
        $image1 = UploadedFile::fake()->image('test.jpg');
        $response1 = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
            'image' => $image1,
        ]);

        $image2 = UploadedFile::fake()->image('test.jpg');
        $response2 = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
            'image' => $image2,
        ]);

        $path1 = $response1->json()['path'];
        $path2 = $response2->json()['path'];

        expect($path1)->not->toBe($path2);
        
        $diskPath1 = str_replace('/storage/', '', $path1);
        $diskPath2 = str_replace('/storage/', '', $path2);
        Storage::disk('public')->assertExists($diskPath1);
        Storage::disk('public')->assertExists($diskPath2);
    })->group('blog-posts', 'image-upload', 'storage', 'unique-names');

    it('preserves file extension when storing', function () {
        $admin = createTestAdmin();
        $image = UploadedFile::fake()->image('test.png');

        $response = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
            'image' => $image,
        ]);

        $path = $response->json()['path'];
        
        expect($path)->toEndWith('.png');
    })->group('blog-posts', 'image-upload', 'storage', 'extension');
});

describe('Image Upload Error Handling', function () {
    it('handles storage failures gracefully', function () {
        $admin = createTestAdmin();
        
        // Make storage read-only to simulate failure
        Storage::fake('public');
        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andThrow(new \Exception('Storage unavailable'));

        $image = UploadedFile::fake()->image('test.jpg');
        $response = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
            'image' => $image,
        ]);

        expect($response->status())->toBe(500);
        
        $data = $response->json();
        expect($data)
            ->toHaveKey('success', false)
            ->toHaveKey('error');
    })->group('blog-posts', 'image-upload', 'error-handling')->skip('Requires complex mocking');

    it('returns error when no file is provided', function () {
        $admin = createTestAdmin();

        $response = authenticatedPost($admin, route('admin.blog-posts.upload-image'), [
            'image' => null,
        ]);

        expect($response->status())->toBeGreaterThanOrEqual(302); // Validation error
    })->group('blog-posts', 'image-upload', 'error-handling');
});
