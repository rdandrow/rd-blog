<?php

use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->author = User::factory()->create([
        'name' => 'John Doe',
    ]);
    
    $this->post = BlogPost::factory()->create([
        'user_id' => $this->author->id,
        'title' => 'Test Post',
        'slug' => 'test-post',
        'excerpt' => 'Test excerpt',
        'content' => 'Test content for the blog post',
        'featured_image' => 'https://example.com/image.jpg',
        'tags' => ['Laravel', 'PHP'],
        'is_featured' => true,
        'published_at' => '2026-01-06 10:30:00',
        'reading_time' => 5,
    ]);
});

describe('basic transformation', function () {
    test('transforms blog post to array with correct structure', function () {
        // Arrange: Create resource
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($this->post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Has correct structure
        expect($array)->toBeArray()
            ->toHaveKeys(['id', 'title', 'slug', 'excerpt', 'featured_image', 'author', 'published_at', 'reading_time', 'tags', 'is_featured'])
            ->and($array['title'])->toBe('Test Post')
            ->and($array['author'])->toBeArray()
            ->and($array['tags'])->toBeArray();
    });

    test('includes all required fields', function () {
        // Arrange: Create resource
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($this->post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: All required fields present
        expect($array)->toHaveKey('id')
            ->and($array)->toHaveKey('title')
            ->and($array)->toHaveKey('slug')
            ->and($array)->toHaveKey('excerpt')
            ->and($array)->toHaveKey('featured_image')
            ->and($array)->toHaveKey('author')
            ->and($array)->toHaveKey('published_at')
            ->and($array)->toHaveKey('reading_time')
            ->and($array)->toHaveKey('tags')
            ->and($array)->toHaveKey('is_featured');
    });

    test('formats published_at as iso string', function () {
        // Arrange: Create resource
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($this->post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: published_at is ISO formatted
        expect($array['published_at'])->toBeString()
            ->and($array['published_at'])->toContain('2026-01-06')
            ->and($array['published_at'])->toContain('T');
    });

    test('includes author information', function () {
        // Arrange: Create resource
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($this->post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Author data is correct
        expect($array['author'])->toHaveKeys(['id', 'name', 'avatar'])
            ->and($array['author']['id'])->toBe($this->author->id)
            ->and($array['author']['name'])->toBe('John Doe')
            ->and($array['author']['avatar'])->toBeNull(); // avatar is null by default
    });

    test('includes tags array', function () {
        // Arrange: Create resource
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($this->post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Tags are present
        expect($array['tags'])->toBeArray()
            ->and($array['tags'])->toContain('Laravel')
            ->and($array['tags'])->toContain('PHP')
            ->and($array['tags'])->toHaveCount(2);
    });
});

describe('conditional content', function () {
    test('includes content on blog show route', function () {
        // Arrange: Mock request with blog.show route
        $request = Request::create('/blog/test-post', 'GET');
        $request->setRouteResolver(function () {
            return new class {
                public function getName() {
                    return 'blog.show';
                }
                public function named(...$patterns) {
                    return in_array('blog.show', $patterns);
                }
            };
        });
        
        $resource = new BlogPostResource($this->post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Content is included
        expect($array)->toHaveKey('content')
            ->and($array['content'])->toBe('Test content for the blog post');
    });

    test('excludes content on other routes', function () {
        // Arrange: Mock request without blog.show route
        $request = Request::create('/api/blog', 'GET');
        $request->setRouteResolver(function () {
            return new class {
                public function getName() {
                    return 'blog.index';
                }
                public function named(...$patterns) {
                    return false; // Not blog.show
                }
            };
        });
        
        $resource = new BlogPostResource($this->post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Content is not included (when() returns MissingValue)
        expect($array)->toHaveKey('content')
            ->and($array['content'])->toBeInstanceOf(\Illuminate\Http\Resources\MissingValue::class);
    });
});

describe('edge cases', function () {
    test('handles null published_at', function () {
        // Arrange: Create post with null published_at
        $post = BlogPost::factory()->create([
            'user_id' => $this->author->id,
            'published_at' => null,
        ]);
        
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: published_at is null
        expect($array['published_at'])->toBeNull();
    });

    test('handles null featured_image', function () {
        // Arrange: Create post with null featured_image
        $post = BlogPost::factory()->create([
            'user_id' => $this->author->id,
            'featured_image' => null,
        ]);
        
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: featured_image is null
        expect($array['featured_image'])->toBeNull();
    });

    test('handles null author avatar', function () {
        // Arrange: Create author (avatar is null by default)
        $authorNoAvatar = User::factory()->create();
        
        $post = BlogPost::factory()->create([
            'user_id' => $authorNoAvatar->id,
        ]);
        
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Author avatar is null
        expect($array['author']['avatar'])->toBeNull();
    });
});

describe('array structure', function () {
    test('returns correct array keys', function () {
        // Arrange: Create resource
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($this->post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Has expected keys
        $expectedKeys = ['id', 'title', 'slug', 'excerpt', 'content', 'featured_image', 'author', 'published_at', 'reading_time', 'tags', 'is_featured'];
        
        foreach ($expectedKeys as $key) {
            expect($array)->toHaveKey($key);
        }
    });

    test('author object has correct structure', function () {
        // Arrange: Create resource
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($this->post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Author has correct structure
        expect($array['author'])->toBeArray()
            ->and($array['author'])->toHaveKeys(['id', 'name', 'avatar'])
            ->and($array['author'])->toHaveCount(3);
    });
});

describe('additional scenarios', function () {
    test('handles empty tags array', function () {
        // Arrange: Create post with no tags
        $post = BlogPost::factory()->create([
            'user_id' => $this->author->id,
            'tags' => [],
        ]);
        
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Tags is empty array
        expect($array['tags'])->toBeArray()
            ->and($array['tags'])->toBeEmpty();
    });



    test('preserves is_featured boolean value', function () {
        // Arrange: Create post with is_featured false
        $postNotFeatured = BlogPost::factory()->create([
            'user_id' => $this->author->id,
            'is_featured' => false,
        ]);
        
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($postNotFeatured);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: is_featured is false
        expect($array['is_featured'])->toBeBool()
            ->and($array['is_featured'])->toBeFalse();
    });

    test('includes reading_time as integer', function () {
        // Arrange: Create resource
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($this->post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: reading_time is an integer (auto-calculated from content)
        expect($array['reading_time'])->toBeInt()
            ->and($array['reading_time'])->toBeGreaterThan(0);
    });

    test('includes all post attributes correctly', function () {
        // Arrange: Create resource
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($this->post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: All attributes match
        expect($array['id'])->toBe($this->post->id)
            ->and($array['title'])->toBe('Test Post')
            ->and($array['slug'])->toBe('test-post')
            ->and($array['excerpt'])->toBe('Test excerpt')
            ->and($array['featured_image'])->toBe('https://example.com/image.jpg')
            ->and($array['is_featured'])->toBeTrue()
            ->and($array['reading_time'])->toBeInt();
    });

    test('content excluded from blog index route', function () {
        // Arrange: Mock request with explicit blog.index route
        $request = Request::create('/blog', 'GET');
        $request->setRouteResolver(function () {
            return new class {
                public function getName() {
                    return 'blog.index';
                }
                public function named(...$patterns) {
                    return false; // Not blog.show
                }
            };
        });
        
        $resource = new BlogPostResource($this->post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Content is MissingValue (excluded)
        expect($array['content'])->toBeInstanceOf(\Illuminate\Http\Resources\MissingValue::class);
    });

    test('handles multiple tags correctly', function () {
        // Arrange: Create post with many tags
        $post = BlogPost::factory()->create([
            'user_id' => $this->author->id,
            'tags' => ['Laravel', 'PHP', 'Testing', 'TDD', 'Pest'],
        ]);
        
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: All tags present
        expect($array['tags'])->toBeArray()
            ->and($array['tags'])->toHaveCount(5)
            ->and($array['tags'])->toContain('Laravel')
            ->and($array['tags'])->toContain('Pest');
    });
});
