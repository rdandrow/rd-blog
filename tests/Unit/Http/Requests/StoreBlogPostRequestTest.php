<?php

use App\Http\Requests\StoreBlogPostRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->request = new StoreBlogPostRequest();
});

describe('authorization', function () {
    test('authorizes authenticated users', function () {
        // Arrange: Set authenticated user
        $this->request->setUserResolver(fn() => $this->user);
        
        // Act: Check authorization
        $result = $this->request->authorize();
        
        // Assert: Returns true
        expect($result)->toBeTrue();
    });

    test('denies guest users', function () {
        // Arrange: Set null user (guest)
        $this->request->setUserResolver(fn() => null);
        
        // Act: Check authorization
        $result = $this->request->authorize();
        
        // Assert: Returns false
        expect($result)->toBeFalse();
    });
});

describe('required fields', function () {
    test('validates title is required', function () {
        // Arrange: Missing title
        $rules = $this->request->rules();
        $validator = Validator::make(['title' => null], $rules);
        
        // Act: Run validation
        $fails = $validator->fails();
        
        // Assert: Validation fails
        expect($fails)->toBeTrue()
            ->and($validator->errors()->has('title'))->toBeTrue();
    });

    test('validates excerpt is required', function () {
        // Arrange: Missing excerpt
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => null,
        ], $rules);
        
        // Act: Run validation
        $fails = $validator->fails();
        
        // Assert: Validation fails
        expect($fails)->toBeTrue()
            ->and($validator->errors()->has('excerpt'))->toBeTrue();
    });

    test('validates content is required', function () {
        // Arrange: Missing content
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => null,
        ], $rules);
        
        // Act: Run validation
        $fails = $validator->fails();
        
        // Assert: Validation fails
        expect($fails)->toBeTrue()
            ->and($validator->errors()->has('content'))->toBeTrue();
    });
});

describe('field length validation', function () {
    test('validates title max length 255', function () {
        // Arrange: Title exceeding 255 characters
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => str_repeat('a', 256),
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
        ], $rules);
        
        // Act: Run validation
        $fails = $validator->fails();
        
        // Assert: Validation fails
        expect($fails)->toBeTrue()
            ->and($validator->errors()->has('title'))->toBeTrue();
    });

    test('validates excerpt max length 500', function () {
        // Arrange: Excerpt exceeding 500 characters
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => str_repeat('a', 501),
            'content' => 'Test content',
        ], $rules);
        
        // Act: Run validation
        $fails = $validator->fails();
        
        // Assert: Validation fails
        expect($fails)->toBeTrue()
            ->and($validator->errors()->has('excerpt'))->toBeTrue();
    });

    test('validates tags max length 50', function () {
        // Arrange: Tag exceeding 50 characters
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'tags' => [str_repeat('a', 51)],
        ], $rules);
        
        // Act: Run validation
        $fails = $validator->fails();
        
        // Assert: Validation fails
        expect($fails)->toBeTrue()
            ->and($validator->errors()->has('tags.0'))->toBeTrue();
    });
});

describe('image validation', function () {
    test('validates featured image file is image', function () {
        // Arrange: Non-image file
        Storage::fake('public');
        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'featured_image_file' => $file,
        ], $rules);
        
        // Act & Assert: Validation fails
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('featured_image_file'))->toBeTrue();
    });

    test('validates featured image file mime types', function () {
        // Arrange: Valid image types
        Storage::fake('public');
        $validMimes = ['jpeg', 'png', 'jpg', 'gif', 'webp'];
        
        foreach ($validMimes as $mime) {
            $file = UploadedFile::fake()->image("test.$mime");
            
            $rules = $this->request->rules();
            $validator = Validator::make([
                'title' => 'Test Title',
                'excerpt' => 'Test excerpt',
                'content' => 'Test content',
                'featured_image_file' => $file,
            ], $rules);
            
            // Assert: Validation passes for each valid mime type
            expect($validator->fails())->toBeFalse();
        }
    });

    test('validates featured image file max size 2mb', function () {
        // Arrange: Image exceeding 2MB (2048 KB)
        Storage::fake('public');
        $file = UploadedFile::fake()->image('large.jpg')->size(2049);
        
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'featured_image_file' => $file,
        ], $rules);
        
        // Act & Assert: Validation fails
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('featured_image_file'))->toBeTrue();
    });

    test('allows featured image as string url', function () {
        // Arrange: String URL instead of file
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'featured_image' => 'https://example.com/image.jpg',
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });
});

describe('boolean and array validation', function () {
    test('validates is_featured is boolean', function () {
        // Arrange: Non-boolean value
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'is_featured' => 'not-a-boolean',
        ], $rules);
        
        // Act & Assert: Validation fails
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('is_featured'))->toBeTrue();
    });

    test('validates is_published is boolean', function () {
        // Arrange: Non-boolean value
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'is_published' => 'not-a-boolean',
        ], $rules);
        
        // Act & Assert: Validation fails
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('is_published'))->toBeTrue();
    });

    test('validates tags is array', function () {
        // Arrange: Non-array value for tags
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'tags' => 'not-an-array',
        ], $rules);
        
        // Act & Assert: Validation fails
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('tags'))->toBeTrue();
    });
});

describe('optional fields', function () {
    test('allows nullable featured image', function () {
        // Arrange: No featured image
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'featured_image' => null,
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });

    test('allows nullable tags', function () {
        // Arrange: No tags
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'tags' => null,
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });
});

describe('custom messages', function () {
    test('returns custom message for title required', function () {
        // Arrange
        $messages = $this->request->messages();
        
        // Assert
        expect($messages)->toHaveKey('title.required')
            ->and($messages['title.required'])->toBe('The blog post title is required.');
    });

    test('returns custom message for excerpt required', function () {
        // Arrange
        $messages = $this->request->messages();
        
        // Assert
        expect($messages)->toHaveKey('excerpt.required')
            ->and($messages['excerpt.required'])->toBe('The blog post excerpt is required.');
    });

    test('returns custom message for content required', function () {
        // Arrange
        $messages = $this->request->messages();
        
        // Assert
        expect($messages)->toHaveKey('content.required')
            ->and($messages['content.required'])->toBe('The blog post content is required.');
    });

    test('returns custom message for featured image file validation', function () {
        // Arrange
        $messages = $this->request->messages();
        
        // Assert
        expect($messages)->toHaveKey('featured_image_file.image')
            ->and($messages['featured_image_file.image'])->toBe('The file must be an image.')
            ->and($messages)->toHaveKey('featured_image_file.max')
            ->and($messages['featured_image_file.max'])->toBe('The image may not be greater than 2MB.');
    });

    test('returns custom message for tags validation', function () {
        // Arrange
        $messages = $this->request->messages();
        
        // Assert
        expect($messages)->toHaveKey('tags.array')
            ->and($messages['tags.array'])->toBe('Tags must be an array.')
            ->and($messages)->toHaveKey('tags.*.max')
            ->and($messages['tags.*.max'])->toBe('Each tag may not be greater than 50 characters.');
    });
});

describe('edge cases', function () {
    test('accepts valid data with all fields', function () {
        // Arrange: Valid complete data
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test.jpg')->size(1024);
        
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Blog Post',
            'excerpt' => 'This is a test excerpt',
            'content' => 'This is the full content of the blog post.',
            'featured_image' => 'https://example.com/image.jpg',
            'featured_image_file' => $file,
            'tags' => ['laravel', 'php', 'testing'],
            'is_featured' => true,
            'is_published' => false,
            'published_at' => '2026-01-06',
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });

    test('accepts minimal valid data', function () {
        // Arrange: Only required fields
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });

    test('validates title at boundary 255 characters', function () {
        // Arrange: Title exactly 255 characters
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => str_repeat('a', 255),
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });

    test('validates excerpt at boundary 500 characters', function () {
        // Arrange: Excerpt exactly 500 characters
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => str_repeat('a', 500),
            'content' => 'Test content',
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });

    test('validates tag at boundary 50 characters', function () {
        // Arrange: Tag exactly 50 characters
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'tags' => [str_repeat('a', 50)],
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });

    test('validates multiple tags with valid data', function () {
        // Arrange: Multiple valid tags
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'tags' => ['php', 'laravel', 'testing', 'unit-tests'],
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });

    test('validates boolean values as integers', function () {
        // Arrange: Boolean as 0 and 1
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'is_featured' => 1,
            'is_published' => 0,
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });

    test('validates published_at as date', function () {
        // Arrange: Valid date
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'published_at' => '2026-01-06 10:30:00',
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });

    test('rejects invalid date for published_at', function () {
        // Arrange: Invalid date
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'published_at' => 'not-a-date',
        ], $rules);
        
        // Act & Assert: Validation fails
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('published_at'))->toBeTrue();
    });
});
