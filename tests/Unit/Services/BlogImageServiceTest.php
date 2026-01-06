<?php

use App\Services\BlogImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->service = new BlogImageService();
});

describe('upload method', function () {
    test('generates unique filename with timestamp', function () {
        // Arrange: Mock Storage facade and UploadedFile
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('test-image.jpg');
        
        // Act: Upload file
        $path = $this->service->upload($file);
        
        // Assert: Path contains timestamp pattern and correct structure
        expect($path)
            ->toStartWith('/storage/blog-images/')
            ->toMatch('/\/storage\/blog-images\/\d+_test-image\.jpg$/');
        
        // Verify file was stored
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $path));
    });

    test('preserves file extension', function () {
        // Arrange: Mock file with PNG extension
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('photo.png');
        
        // Act: Upload file
        $path = $this->service->upload($file);
        
        // Assert: Extension is preserved
        expect($path)->toEndWith('.png');
    });

    test('slugifies original filename', function () {
        // Arrange: Mock file with special characters
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('My Test Image!.jpg');
        
        // Act: Upload file
        $path = $this->service->upload($file);
        
        // Assert: Filename is slugified (spaces and special chars removed)
        expect($path)->toContain('my-test-image');
    });

    test('returns correct storage path format', function () {
        // Arrange: Mock uploaded file
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('example.jpg');
        
        // Act: Upload file
        $path = $this->service->upload($file);
        
        // Assert: Path follows /storage/blog-images/timestamp_name.ext pattern
        expect($path)
            ->toStartWith('/storage/')
            ->toContain('blog-images/')
            ->toEndWith('.jpg');
    });
});

describe('delete method', function () {
    test('deletes file from storage when path starts with storage', function () {
        // Arrange: Create fake file in storage
        Storage::fake('public');
        Storage::disk('public')->put('blog-images/test.jpg', 'fake content');
        
        // Act: Delete file using storage path
        $this->service->delete('/storage/blog-images/test.jpg');
        
        // Assert: File was deleted
        Storage::disk('public')->assertMissing('blog-images/test.jpg');
    });

    test('converts storage path to disk path correctly', function () {
        // Arrange: Create fake file
        Storage::fake('public');
        Storage::disk('public')->put('blog-images/12345_photo.png', 'content');
        
        // Act: Delete using /storage/ prefixed path
        $this->service->delete('/storage/blog-images/12345_photo.png');
        
        // Assert: Correctly converted path and deleted
        Storage::disk('public')->assertMissing('blog-images/12345_photo.png');
    });

    test('ignores external urls with https', function () {
        // Arrange: Setup storage fake
        Storage::fake('public');
        
        // Act: Attempt to delete external URL
        $this->service->delete('https://example.com/image.jpg');
        
        // Assert: No exception thrown, method handles gracefully
        expect(true)->toBeTrue(); // Test passes if no exception
    });

    test('handles null image path gracefully', function () {
        // Arrange: Setup storage fake
        Storage::fake('public');
        
        // Act: Attempt to delete null path
        $this->service->delete(null);
        
        // Assert: No exception thrown
        expect(true)->toBeTrue();
    });
});

describe('handleCreate method', function () {
    test('uploads file when file is provided', function () {
        // Arrange: Mock file and storage
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('new-post.jpg');
        $url = null;
        
        // Act: Handle create with file
        $result = $this->service->handleCreate($file, $url);
        
        // Assert: Returns storage path for uploaded file
        expect($result)
            ->toStartWith('/storage/blog-images/')
            ->toContain('new-post');
        
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $result));
    });

    test('returns url when no file provided', function () {
        // Arrange: No file, only URL
        $file = null;
        $url = 'https://example.com/external-image.jpg';
        
        // Act: Handle create with URL only
        $result = $this->service->handleCreate($file, $url);
        
        // Assert: Returns the provided URL unchanged
        expect($result)->toBe('https://example.com/external-image.jpg');
    });
});

describe('handleUpdate method', function () {
    test('uploads new file and deletes old when file provided', function () {
        // Arrange: Create existing file and prepare new file
        Storage::fake('public');
        Storage::disk('public')->put('blog-images/old-image.jpg', 'old content');
        
        $newFile = UploadedFile::fake()->image('new-image.jpg');
        $currentImagePath = '/storage/blog-images/old-image.jpg';
        
        // Act: Handle update with new file
        $result = $this->service->handleUpdate($newFile, null, false, $currentImagePath);
        
        // Assert: Old file deleted, new file uploaded
        Storage::disk('public')->assertMissing('blog-images/old-image.jpg');
        expect($result)
            ->toStartWith('/storage/blog-images/')
            ->toContain('new-image');
        
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $result));
    });

    test('deletes current image when remove flag is true', function () {
        // Arrange: Create existing file
        Storage::fake('public');
        Storage::disk('public')->put('blog-images/to-remove.jpg', 'content');
        
        $currentImagePath = '/storage/blog-images/to-remove.jpg';
        
        // Act: Handle update with remove flag
        $result = $this->service->handleUpdate(null, null, true, $currentImagePath);
        
        // Assert: File deleted, returns null
        Storage::disk('public')->assertMissing('blog-images/to-remove.jpg');
        expect($result)->toBeNull();
    });

    test('replaces local image with new url when url provided', function () {
        // Arrange: Create existing local file
        Storage::fake('public');
        Storage::disk('public')->put('blog-images/local-image.jpg', 'content');
        
        $currentImagePath = '/storage/blog-images/local-image.jpg';
        $newUrl = 'https://cdn.example.com/new-image.jpg';
        
        // Act: Handle update with new URL
        $result = $this->service->handleUpdate(null, $newUrl, false, $currentImagePath);
        
        // Assert: Local file deleted, new URL returned
        Storage::disk('public')->assertMissing('blog-images/local-image.jpg');
        expect($result)->toBe('https://cdn.example.com/new-image.jpg');
    });

    test('keeps existing image when no changes requested', function () {
        // Arrange: Existing image path
        Storage::fake('public');
        Storage::disk('public')->put('blog-images/existing.jpg', 'content');
        
        $currentImagePath = '/storage/blog-images/existing.jpg';
        
        // Act: Handle update with no new file, no new URL, no remove flag
        $result = $this->service->handleUpdate(null, null, false, $currentImagePath);
        
        // Assert: Returns existing path unchanged
        expect($result)->toBe('/storage/blog-images/existing.jpg');
        Storage::disk('public')->assertExists('blog-images/existing.jpg');
    });

    test('keeps existing image when same url provided', function () {
        // Arrange: Existing external URL
        $currentImagePath = 'https://example.com/image.jpg';
        
        // Act: Handle update with same URL
        $result = $this->service->handleUpdate(null, $currentImagePath, false, $currentImagePath);
        
        // Assert: Returns same URL unchanged
        expect($result)->toBe('https://example.com/image.jpg');
    });
});
