<?php

use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Http\Request;

uses(Tests\TestCase::class);

/**
 * @group resources
 * @group transformation
 * @group blog-post-resource
 * @group unit
 */

beforeEach(function () {
    $this->author = new User(['name' => 'John Doe']);
    $this->author->id = 1;
    $this->author->avatar = null;
    
    $this->post = new BlogPost([
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
    $this->post->id = 1;
    $this->post->user_id = 1;
    $this->post->setRelation('author', $this->author);
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
        $post = new BlogPost(['published_at' => null]);
        $post->id = 2;
        $post->user_id = $this->author->id;
        $post->setRelation('author', $this->author);
        
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: published_at is null
        expect($array['published_at'])->toBeNull();
    });

    test('handles null featured_image', function () {
        // Arrange: Create post with null featured_image
        $post = new BlogPost(['featured_image' => null]);
        $post->id = 2;
        $post->user_id = $this->author->id;
        $post->setRelation('author', $this->author);
        
        $request = Request::create('/api/blog', 'GET');
        $resource = new BlogPostResource($post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: featured_image is null
        expect($array['featured_image'])->toBeNull();
    });

    test('handles null author avatar', function () {
        // Arrange: Create author (avatar is null by default)
        $authorNoAvatar = new User();
        $authorNoAvatar->id = 2;
        $authorNoAvatar->avatar = null;
        
        $post = new BlogPost();
        $post->id = 2;
        $post->user_id = $authorNoAvatar->id;
        $post->setRelation('author', $authorNoAvatar);
        
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
        $post = new BlogPost(['tags' => []]);
        $post->id = 2;
        $post->user_id = $this->author->id;
        $post->setRelation('author', $this->author);
        
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
        $postNotFeatured = new BlogPost(['is_featured' => false]);
        $postNotFeatured->id = 2;
        $postNotFeatured->user_id = $this->author->id;
        $postNotFeatured->setRelation('author', $this->author);
        
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
        $post = new BlogPost(['tags' => ['Laravel', 'PHP', 'Testing', 'TDD', 'Pest']]);
        $post->id = 2;
        $post->user_id = $this->author->id;
        $post->setRelation('author', $this->author);
        
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

describe('Snapshot Testing', function () {
    test('blog post resource matches snapshot for index route', function () {
        // Arrange: Create consistent test data
        $author = new User(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $author->id = 100;
        $author->avatar = 'https://example.com/avatar.jpg';
        
        $post = new BlogPost([
            'title' => 'Snapshot Test Post',
            'slug' => 'snapshot-test-post',
            'excerpt' => 'This is a test post for snapshot testing',
            'content' => 'Full content of the blog post that should not appear on index',
            'featured_image' => 'https://example.com/featured.jpg',
            'tags' => ['Laravel', 'Testing', 'Snapshots'],
            'is_featured' => true,
            'published_at' => '2026-01-08 12:00:00',
            'reading_time' => 8,
        ]);
        $post->id = 100;
        $post->user_id = 100;
        $post->setRelation('author', $author);
        
        $request = Request::create('/blog', 'GET');
        $resource = new BlogPostResource($post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Matches snapshot (content should be excluded on index)
        expect($array)->toMatchSnapshot();
    })->group('snapshots');
    
    test('blog post resource matches snapshot for show route', function () {
        // Arrange: Create consistent test data
        $author = new User(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $author->id = 100;
        $author->avatar = 'https://example.com/avatar.jpg';
        
        $post = new BlogPost([
            'title' => 'Snapshot Test Post',
            'slug' => 'snapshot-test-post',
            'excerpt' => 'This is a test post for snapshot testing',
            'content' => 'Full content of the blog post that should appear on show route',
            'featured_image' => 'https://example.com/featured.jpg',
            'tags' => ['Laravel', 'Testing', 'Snapshots'],
            'is_featured' => true,
            'published_at' => '2026-01-08 12:00:00',
            'reading_time' => 8,
        ]);
        $post->id = 100;
        $post->user_id = 100;
        $post->setRelation('author', $author);
        
        // Simulate blog.show route
        $request = Request::create('/blog/snapshot-test-post', 'GET');
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/blog/{slug}', []);
            $route->name('blog.show');
            return $route;
        });
        
        $resource = new BlogPostResource($post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Matches snapshot (content should be included on show route)
        expect($array)->toMatchSnapshot();
    })->group('snapshots');
    
    test('blog post resource with null values matches snapshot', function () {
        // Arrange: Create post with null optional fields
        $author = new User(['name' => 'No Avatar User', 'email' => 'noavatar@example.com']);
        $author->id = 101;
        $author->avatar = null;
        
        $post = new BlogPost([
            'title' => 'Post Without Image',
            'slug' => 'post-without-image',
            'excerpt' => 'This post has no featured image',
            'content' => 'Content without images',
            'featured_image' => null,
            'tags' => [],
            'is_featured' => false,
            'published_at' => null,
            'reading_time' => 3,
        ]);
        $post->id = 101;
        $post->user_id = 101;
        $post->setRelation('author', $author);
        
        $request = Request::create('/blog', 'GET');
        $resource = new BlogPostResource($post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Matches snapshot with null values
        expect($array)->toMatchSnapshot();
    })->group('snapshots');
    
    test('blog post resource with multiple tags matches snapshot', function () {
        // Arrange: Create post with many tags
        $author = new User(['name' => 'Multi Tag Author', 'email' => 'tags@example.com']);
        $author->id = 102;
        $author->avatar = 'https://example.com/author2.jpg';
        
        $post = new BlogPost([
            'title' => 'Post With Many Tags',
            'slug' => 'post-with-many-tags',
            'excerpt' => 'Testing multiple tags handling',
            'content' => 'Content about various topics',
            'featured_image' => 'https://example.com/multi-tags.jpg',
            'tags' => ['Laravel', 'PHP', 'Testing', 'TDD', 'Pest'],
            'is_featured' => false,
            'published_at' => '2026-01-15 10:00:00',
            'reading_time' => 12,
        ]);
        $post->id = 102;
        $post->user_id = 102;
        $post->setRelation('author', $author);
        
        $request = Request::create('/blog', 'GET');
        $resource = new BlogPostResource($post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Matches snapshot with multiple tags
        expect($array)->toMatchSnapshot();
    })->group('snapshots');
    
    test('blog post resource with complete attributes matches snapshot', function () {
        // Arrange: Create post with all populated attributes
        $author = new User(['name' => 'Complete Author', 'email' => 'complete@example.com']);
        $author->id = 103;
        $author->avatar = 'https://example.com/complete-avatar.jpg';
        
        $post = new BlogPost([
            'title' => 'Complete Test Post',
            'slug' => 'complete-test-post',
            'excerpt' => 'Post with all attributes populated',
            'content' => 'Full detailed content goes here',
            'featured_image' => 'https://example.com/complete-featured.jpg',
            'tags' => ['Complete', 'Testing'],
            'is_featured' => true,
            'published_at' => '2026-02-01 08:30:00',
            'reading_time' => 6,
        ]);
        $post->id = 103;
        $post->user_id = 103;
        $post->setRelation('author', $author);
        
        // Simulate blog.show route to include content
        $request = Request::create('/blog/complete-test-post', 'GET');
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route('GET', '/blog/{slug}', []);
            $route->name('blog.show');
            return $route;
        });
        
        $resource = new BlogPostResource($post);
        
        // Act: Transform to array
        $array = $resource->toArray($request);
        
        // Assert: Matches snapshot with all complete attributes
        expect($array)->toMatchSnapshot();
    })->group('snapshots');
});
