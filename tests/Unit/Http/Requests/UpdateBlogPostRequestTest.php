<?php

use App\Http\Requests\UpdateBlogPostRequest;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

uses(Tests\TestCase::class);

/**
 * @group requests
 * @group validation
 * @group authorization
 * @group update-request
 * @group unit
 */

beforeEach(function () {
    $this->author = new User();
    $this->author->id = 1;
    $this->nonAuthor = new User();
    $this->nonAuthor->id = 2;
    $this->post = new BlogPost();
    $this->post->user_id = 1;
    $this->request = new UpdateBlogPostRequest();
    
    // Helper to validate rules and check for field errors
    $this->validateField = function (array $data, string $field): bool {
        $rules = $this->request->rules();
        $validator = Validator::make($data, $rules);
        return $validator->fails() && $validator->errors()->has($field);
    };
});

describe('authorization', function () {
    test('authorizes post author', function () {
        // Arrange: Set author as user and mock route
        $this->request->setUserResolver(fn() => $this->author);
        $this->request->setRouteResolver(function () {
            $post = $this->post;
            return new class($post) {
                public function __construct(private BlogPost $post) {}
                public function parameter($key) {
                    return $this->post;
                }
            };
        });
        
        // Act: Check authorization
        $result = $this->request->authorize();
        
        // Assert: Author can update
        expect($result)->toBeTrue();
    });

    test('denies non author', function () {
        // Arrange: Set non-author as user
        $this->request->setUserResolver(fn() => $this->nonAuthor);
        $this->request->setRouteResolver(function () {
            $post = $this->post;
            return new class($post) {
                public function __construct(private BlogPost $post) {}
                public function parameter($key) {
                    return $this->post;
                }
            };
        });
        
        // Act: Check authorization
        $result = $this->request->authorize();
        
        // Assert: Non-author cannot update
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

describe('boolean fields validation', function () {
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

    test('validates remove_current_image is boolean', function () {
        // Arrange: Non-boolean value
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'remove_current_image' => 'not-a-boolean',
        ], $rules);
        
        // Act & Assert: Validation fails
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('remove_current_image'))->toBeTrue();
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

describe('update-specific tests', function () {
    test('remove_current_image field exists in rules', function () {
        // Act: Get validation rules
        $rules = $this->request->rules();
        
        // Assert: remove_current_image rule exists and is boolean
        expect($rules)->toHaveKey('remove_current_image')
            ->and($rules['remove_current_image'])->toContain('boolean');
    });

    test('authorization uses blog post policy', function () {
        // Arrange: Test that policy method is called by checking return value
        // The authorize() method uses can('update', $blogPost) which calls the policy
        $this->request->setUserResolver(fn() => $this->author);
        $this->request->setRouteResolver(function () {
            $post = $this->post;
            return new class($post) {
                public function __construct(private BlogPost $post) {}
                public function parameter($key) {
                    return $this->post;
                }
            };
        });
        
        // Act: Check authorization
        $result = $this->request->authorize();
        
        // Assert: Policy is used and returns true for author
        expect($result)->toBeTrue();
        
        // Also verify non-author gets false (proving policy logic is applied)
        $this->request->setUserResolver(fn() => $this->nonAuthor);
        $result = $this->request->authorize();
        expect($result)->toBeFalse();
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
    test('accepts valid update with all fields', function () {
        // Arrange: Valid complete data
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test.jpg')->size(1024);
        
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Updated Blog Post',
            'excerpt' => 'This is an updated excerpt',
            'content' => 'This is the updated full content of the blog post.',
            'featured_image' => 'https://example.com/image.jpg',
            'featured_image_file' => $file,
            'remove_current_image' => false,
            'tags' => ['laravel', 'php', 'testing'],
            'is_featured' => true,
            'is_published' => false,
            'published_at' => '2026-01-06',
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });

    test('accepts minimal valid update data', function () {
        // Arrange: Only required fields
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Updated Title',
            'excerpt' => 'Updated excerpt',
            'content' => 'Updated content',
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });

    test('allows remove_current_image true to remove image', function () {
        // Arrange: Flag to remove current image
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'remove_current_image' => true,
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });

    test('validates boundary cases for lengths', function () {
        // Arrange: Exactly at max lengths
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => str_repeat('a', 255),
            'excerpt' => str_repeat('b', 500),
            'content' => 'Test content',
            'tags' => [str_repeat('c', 50)],
        ], $rules);
        
        // Act & Assert: Validation passes at boundaries
        expect($validator->fails())->toBeFalse();
    });

    test('validates boolean as integers', function () {
        // Arrange: Boolean as 0 and 1
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'is_featured' => 1,
            'is_published' => 0,
            'remove_current_image' => 0,
        ], $rules);
        
        // Act & Assert: Validation passes
        expect($validator->fails())->toBeFalse();
    });

    test('allows updating with new image while keeping string url', function () {
        // Arrange: Both featured_image and featured_image_file
        Storage::fake('public');
        $file = UploadedFile::fake()->image('new.jpg');
        
        $rules = $this->request->rules();
        $validator = Validator::make([
            'title' => 'Test Title',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'featured_image' => 'https://example.com/old.jpg',
            'featured_image_file' => $file,
        ], $rules);
        
        // Act & Assert: Validation passes (both fields allowed)
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
