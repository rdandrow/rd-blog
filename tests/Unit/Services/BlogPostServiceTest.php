<?php

use App\Models\BlogPost;
use App\Models\User;
use App\Services\BlogPostService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new BlogPostService();
    
    // Create test users
    $this->author1 = User::factory()->create(['name' => 'Alice Author']);
    $this->author2 = User::factory()->create(['name' => 'Bob Blogger']);
});

describe('applyFilters method', function () {
    test('filters posts by search term in title', function () {
        // Arrange: Create posts with different titles
        BlogPost::factory()->published()->create([
            'title' => 'Laravel Testing Guide',
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->create([
            'title' => 'PHP Best Practices',
            'user_id' => $this->author1->id,
        ]);
        
        $query = BlogPost::query();
        
        // Act: Apply search filter
        $filteredQuery = $this->service->applyFilters($query, ['search' => 'Laravel']);
        
        // Assert: Only Laravel post found
        expect($filteredQuery->count())->toBe(1)
            ->and($filteredQuery->first()->title)->toBe('Laravel Testing Guide');
    });

    test('filters posts by search term in excerpt', function () {
        // Arrange: Create posts with search term in excerpt
        BlogPost::factory()->published()->create([
            'title' => 'Post One',
            'excerpt' => 'Learn about Vue.js framework',
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->create([
            'title' => 'Post Two',
            'excerpt' => 'Guide to React development',
            'user_id' => $this->author1->id,
        ]);
        
        $query = BlogPost::query();
        
        // Act: Apply search filter
        $filteredQuery = $this->service->applyFilters($query, ['search' => 'Vue']);
        
        // Assert: Only Vue post found
        expect($filteredQuery->count())->toBe(1)
            ->and($filteredQuery->first()->title)->toBe('Post One');
    });

    test('filters posts by search term in content', function () {
        // Arrange: Create posts with search term in content
        BlogPost::factory()->published()->create([
            'title' => 'Article A',
            'content' => 'Deep dive into TypeScript features',
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->create([
            'title' => 'Article B',
            'content' => 'JavaScript fundamentals explained',
            'user_id' => $this->author1->id,
        ]);
        
        $query = BlogPost::query();
        
        // Act: Apply search filter
        $filteredQuery = $this->service->applyFilters($query, ['search' => 'TypeScript']);
        
        // Assert: Only TypeScript post found
        expect($filteredQuery->count())->toBe(1)
            ->and($filteredQuery->first()->title)->toBe('Article A');
    });

    test('filters posts by tag', function () {
        // Arrange: Create posts with different tags
        BlogPost::factory()->published()->create([
            'tags' => ['Laravel', 'PHP'],
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->create([
            'tags' => ['JavaScript', 'React'],
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->create([
            'tags' => ['Laravel', 'Testing'],
            'user_id' => $this->author2->id,
        ]);
        
        $query = BlogPost::query();
        
        // Act: Apply tag filter
        $filteredQuery = $this->service->applyFilters($query, ['tag' => 'Laravel']);
        
        // Assert: Only Laravel tagged posts found
        expect($filteredQuery->count())->toBe(2);
    });

    test('filters posts by author id', function () {
        // Arrange: Create posts by different authors
        BlogPost::factory()->count(3)->published()->create(['user_id' => $this->author1->id]);
        BlogPost::factory()->count(2)->published()->create(['user_id' => $this->author2->id]);
        
        $query = BlogPost::query();
        
        // Act: Apply author filter
        $filteredQuery = $this->service->applyFilters($query, ['author' => $this->author1->id]);
        
        // Assert: Only author1's posts found
        expect($filteredQuery->count())->toBe(3);
    });

    test('applies multiple filters simultaneously', function () {
        // Arrange: Create posts with various combinations
        BlogPost::factory()->published()->create([
            'title' => 'Laravel Testing Guide',
            'tags' => ['Laravel', 'Testing'],
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->create([
            'title' => 'Laravel Security',
            'tags' => ['Laravel', 'Security'],
            'user_id' => $this->author2->id,
        ]);
        BlogPost::factory()->published()->create([
            'title' => 'PHP Testing',
            'tags' => ['PHP', 'Testing'],
            'user_id' => $this->author1->id,
        ]);
        
        $query = BlogPost::query();
        
        // Act: Apply multiple filters (search + tag + author)
        $filteredQuery = $this->service->applyFilters($query, [
            'search' => 'Laravel',
            'tag' => 'Testing',
            'author' => $this->author1->id,
        ]);
        
        // Assert: Only matching post found
        expect($filteredQuery->count())->toBe(1)
            ->and($filteredQuery->first()->title)->toBe('Laravel Testing Guide');
    });

    test('handles empty filters gracefully', function () {
        // Arrange: Create some posts
        BlogPost::factory()->count(5)->published()->create(['user_id' => $this->author1->id]);
        
        $query = BlogPost::query();
        
        // Act: Apply empty filters
        $filteredQuery = $this->service->applyFilters($query, []);
        
        // Assert: All posts returned
        expect($filteredQuery->count())->toBe(5);
    });

    test('handles special characters in search', function () {
        // Arrange: Create post with special characters
        BlogPost::factory()->published()->create([
            'title' => 'C++ Programming Guide',
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->create([
            'title' => 'Java Programming',
            'user_id' => $this->author1->id,
        ]);
        
        $query = BlogPost::query();
        
        // Act: Apply search with special characters
        $filteredQuery = $this->service->applyFilters($query, ['search' => 'C++']);
        
        // Assert: Special characters handled correctly
        expect($filteredQuery->count())->toBe(1)
            ->and($filteredQuery->first()->title)->toBe('C++ Programming Guide');
    });
});

describe('getFeaturedPosts method', function () {
    test('returns correct number of posts', function () {
        // Arrange: Create more featured posts than limit
        BlogPost::factory()->count(5)->published()->featured()->create(['user_id' => $this->author1->id]);
        
        // Act: Get featured posts with limit of 2
        $posts = $this->service->getFeaturedPosts(2);
        
        // Assert: Returns exactly 2 posts
        expect($posts)->toHaveCount(2);
    });

    test('applies filters', function () {
        // Arrange: Create featured posts with different tags
        BlogPost::factory()->published()->featured()->create([
            'tags' => ['Laravel'],
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->featured()->create([
            'tags' => ['PHP'],
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->featured()->create([
            'tags' => ['Laravel'],
            'user_id' => $this->author2->id,
        ]);
        
        // Act: Get featured posts filtered by tag
        $posts = $this->service->getFeaturedPosts(10, ['tag' => 'Laravel']);
        
        // Assert: Only Laravel tagged posts returned
        expect($posts)->toHaveCount(2);
    });

    test('orders by published_at desc', function () {
        // Arrange: Create featured posts with specific dates
        $oldest = BlogPost::factory()->published()->featured()->create([
            'published_at' => now()->subDays(10),
            'user_id' => $this->author1->id,
        ]);
        $newest = BlogPost::factory()->published()->featured()->create([
            'published_at' => now()->subDays(1),
            'user_id' => $this->author1->id,
        ]);
        $middle = BlogPost::factory()->published()->featured()->create([
            'published_at' => now()->subDays(5),
            'user_id' => $this->author1->id,
        ]);
        
        // Act: Get featured posts
        $posts = $this->service->getFeaturedPosts(10);
        
        // Assert: Ordered newest to oldest
        expect($posts[0]->id)->toBe($newest->id)
            ->and($posts[1]->id)->toBe($middle->id)
            ->and($posts[2]->id)->toBe($oldest->id);
    });

    test('only returns published posts', function () {
        // Arrange: Create featured posts with different publication statuses
        BlogPost::factory()->published()->featured()->count(2)->create(['user_id' => $this->author1->id]);
        BlogPost::factory()->featured()->create([
            'is_published' => false,
            'published_at' => null,
            'user_id' => $this->author1->id,
        ]);
        
        // Act: Get featured posts
        $posts = $this->service->getFeaturedPosts(10);
        
        // Assert: Only published posts returned
        expect($posts)->toHaveCount(2);
    });

    test('only returns featured posts', function () {
        // Arrange: Create mix of featured and non-featured published posts
        BlogPost::factory()->published()->featured()->count(2)->create(['user_id' => $this->author1->id]);
        BlogPost::factory()->published()->count(3)->create([
            'is_featured' => false,
            'user_id' => $this->author1->id,
        ]);
        
        // Act: Get featured posts
        $posts = $this->service->getFeaturedPosts(10);
        
        // Assert: Only featured posts returned
        expect($posts)->toHaveCount(2)
            ->and($posts->every(fn($post) => $post->is_featured))->toBeTrue();
    });
});

describe('getRecentPosts method', function () {
    test('excludes featured posts', function () {
        // Arrange: Create mix of featured and non-featured posts
        BlogPost::factory()->published()->featured()->count(2)->create(['user_id' => $this->author1->id]);
        BlogPost::factory()->published()->count(3)->create([
            'is_featured' => false,
            'user_id' => $this->author1->id,
        ]);
        
        // Act: Get recent posts
        $posts = $this->service->getRecentPosts(10);
        
        // Assert: No featured posts in result
        expect($posts)->toHaveCount(3)
            ->and($posts->every(fn($post) => !$post->is_featured))->toBeTrue();
    });

    test('applies filters', function () {
        // Arrange: Create non-featured posts with different authors
        BlogPost::factory()->published()->count(2)->create([
            'is_featured' => false,
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->count(3)->create([
            'is_featured' => false,
            'user_id' => $this->author2->id,
        ]);
        
        // Act: Get recent posts filtered by author
        $posts = $this->service->getRecentPosts(10, ['author' => $this->author1->id]);
        
        // Assert: Only author1's posts returned
        expect($posts)->toHaveCount(2);
    });

    test('respects limit parameter', function () {
        // Arrange: Create many non-featured posts
        BlogPost::factory()->published()->count(10)->create([
            'is_featured' => false,
            'user_id' => $this->author1->id,
        ]);
        
        // Act: Get recent posts with limit of 3
        $posts = $this->service->getRecentPosts(3);
        
        // Assert: Returns exactly 3 posts
        expect($posts)->toHaveCount(3);
    });

    test('orders by published_at desc', function () {
        // Arrange: Create non-featured posts with specific dates
        $oldest = BlogPost::factory()->published()->create([
            'is_featured' => false,
            'published_at' => now()->subDays(10),
            'user_id' => $this->author1->id,
        ]);
        $newest = BlogPost::factory()->published()->create([
            'is_featured' => false,
            'published_at' => now()->subDays(1),
            'user_id' => $this->author1->id,
        ]);
        
        // Act: Get recent posts
        $posts = $this->service->getRecentPosts(10);
        
        // Assert: Ordered newest to oldest
        expect($posts[0]->id)->toBe($newest->id)
            ->and($posts[1]->id)->toBe($oldest->id);
    });
});

describe('getAvailableTags method', function () {
    test('returns unique tags from published posts', function () {
        // Arrange: Create posts with overlapping tags
        BlogPost::factory()->published()->create([
            'tags' => ['Laravel', 'PHP'],
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->create([
            'tags' => ['Laravel', 'Testing'],
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->create([
            'tags' => ['JavaScript'],
            'user_id' => $this->author2->id,
        ]);
        
        // Act: Get available tags
        $tags = $this->service->getAvailableTags();
        
        // Assert: Returns unique tags (Laravel appears only once)
        expect($tags)->toHaveCount(4)
            ->and($tags->contains('Laravel'))->toBeTrue()
            ->and($tags->contains('PHP'))->toBeTrue()
            ->and($tags->contains('Testing'))->toBeTrue()
            ->and($tags->contains('JavaScript'))->toBeTrue();
    });

    test('sorts tags alphabetically', function () {
        // Arrange: Create posts with tags in random order
        BlogPost::factory()->published()->create([
            'tags' => ['Zebra', 'Apple', 'Mango'],
            'user_id' => $this->author1->id,
        ]);
        
        // Act: Get available tags
        $tags = $this->service->getAvailableTags();
        
        // Assert: Tags are sorted alphabetically
        expect($tags->values()->toArray())->toBe(['Apple', 'Mango', 'Zebra']);
    });

    test('excludes tags from draft posts', function () {
        // Arrange: Create published and draft posts
        BlogPost::factory()->published()->create([
            'tags' => ['Published'],
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->create([
            'is_published' => false,
            'published_at' => null,
            'tags' => ['Draft'],
            'user_id' => $this->author1->id,
        ]);
        
        // Act: Get available tags
        $tags = $this->service->getAvailableTags();
        
        // Assert: Only published post tags returned
        expect($tags)->toHaveCount(1)
            ->and($tags->contains('Published'))->toBeTrue()
            ->and($tags->contains('Draft'))->toBeFalse();
    });

    test('handles posts with empty tags', function () {
        // Arrange: Create posts with and without tags
        BlogPost::factory()->published()->create([
            'tags' => ['Valid'],
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->create([
            'tags' => [],
            'user_id' => $this->author1->id,
        ]);
        
        // Act: Get available tags
        $tags = $this->service->getAvailableTags();
        
        // Assert: Returns only valid tags
        expect($tags)->toHaveCount(1)
            ->and($tags->first())->toBe('Valid');
    });
});

describe('getAvailableAuthors method', function () {
    test('returns only authors with published posts', function () {
        // Arrange: Create authors with and without published posts
        BlogPost::factory()->published()->create(['user_id' => $this->author1->id]);
        BlogPost::factory()->published()->create(['user_id' => $this->author2->id]);
        
        $authorWithoutPosts = User::factory()->create(['name' => 'Charlie No Posts']);
        
        // Act: Get available authors
        $authors = $this->service->getAvailableAuthors();
        
        // Assert: Only authors with published posts returned
        expect($authors)->toHaveCount(2)
            ->and($authors->pluck('id'))->toContain($this->author1->id, $this->author2->id)
            ->and($authors->pluck('id'))->not->toContain($authorWithoutPosts->id);
    });

    test('returns correct user attributes', function () {
        // Arrange: Create author with published post
        BlogPost::factory()->published()->create(['user_id' => $this->author1->id]);
        
        // Act: Get available authors
        $authors = $this->service->getAvailableAuthors();
        
        // Assert: Returns only id and name attributes
        $author = $authors->first();
        expect($author->id)->toBe($this->author1->id)
            ->and($author->name)->toBe('Alice Author')
            ->and(isset($author->email))->toBeFalse(); // Should not include email
    });

    test('excludes authors with only draft posts', function () {
        // Arrange: Create published post for author1, draft for author2
        BlogPost::factory()->published()->create(['user_id' => $this->author1->id]);
        BlogPost::factory()->create([
            'is_published' => false,
            'published_at' => null,
            'user_id' => $this->author2->id,
        ]);
        
        // Act: Get available authors
        $authors = $this->service->getAvailableAuthors();
        
        // Assert: Only author with published posts returned
        expect($authors)->toHaveCount(1)
            ->and($authors->first()->id)->toBe($this->author1->id);
    });

    test('returns each author once even with multiple posts', function () {
        // Arrange: Create multiple posts for same author
        BlogPost::factory()->published()->count(5)->create(['user_id' => $this->author1->id]);
        
        // Act: Get available authors
        $authors = $this->service->getAvailableAuthors();
        
        // Assert: Author appears only once
        expect($authors)->toHaveCount(1)
            ->and($authors->first()->id)->toBe($this->author1->id);
    });
});

describe('getLandingPageData method', function () {
    test('assembles data correctly', function () {
        // Arrange: Create various posts
        BlogPost::factory()->published()->featured()->count(3)->create(['user_id' => $this->author1->id]);
        BlogPost::factory()->published()->count(7)->create([
            'is_featured' => false,
            'user_id' => $this->author1->id,
        ]);
        
        // Act: Get landing page data
        $data = $this->service->getLandingPageData();
        
        // Assert: All required keys present with correct data
        expect($data)->toHaveKeys(['featured_posts', 'recent_posts', 'available_tags', 'available_authors'])
            ->and($data['featured_posts'])->toHaveCount(2) // Default limit
            ->and($data['recent_posts'])->toHaveCount(6) // Default limit
            ->and($data['available_tags'])->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($data['available_authors'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    test('passes filters to child methods', function () {
        // Arrange: Create posts with different tags
        BlogPost::factory()->published()->featured()->create([
            'tags' => ['Laravel'],
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->featured()->create([
            'tags' => ['PHP'],
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->count(2)->create([
            'is_featured' => false,
            'tags' => ['Laravel'],
            'user_id' => $this->author1->id,
        ]);
        
        // Act: Get landing page data with filters
        $data = $this->service->getLandingPageData(['tag' => 'Laravel']);
        
        // Assert: Filters applied to featured and recent posts
        expect($data['featured_posts'])->toHaveCount(1)
            ->and($data['recent_posts'])->toHaveCount(2);
    });
});

describe('getBlogListingData method', function () {
    test('assembles data correctly', function () {
        // Arrange: Create various posts
        BlogPost::factory()->published()->featured()->count(5)->create(['user_id' => $this->author1->id]);
        BlogPost::factory()->published()->count(8)->create([
            'is_featured' => false,
            'user_id' => $this->author1->id,
        ]);
        
        // Act: Get blog listing data
        $data = $this->service->getBlogListingData();
        
        // Assert: All required keys present with correct data
        expect($data)->toHaveKeys(['featured_posts', 'posts', 'available_tags', 'available_authors'])
            ->and($data['featured_posts'])->toHaveCount(4) // Limited to 4
            ->and($data['posts'])->toHaveCount(8) // All non-featured
            ->and($data['available_tags'])->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($data['available_authors'])->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    test('separates featured and non-featured posts correctly', function () {
        // Arrange: Create mix of posts
        BlogPost::factory()->published()->featured()->count(3)->create(['user_id' => $this->author1->id]);
        BlogPost::factory()->published()->count(5)->create([
            'is_featured' => false,
            'user_id' => $this->author1->id,
        ]);
        
        // Act: Get blog listing data
        $data = $this->service->getBlogListingData();
        
        // Assert: Featured and non-featured properly separated
        expect($data['featured_posts']->every(fn($post) => $post->is_featured))->toBeTrue()
            ->and($data['posts']->every(fn($post) => !$post->is_featured))->toBeTrue();
    });

    test('applies filters to all posts', function () {
        // Arrange: Create posts for different authors
        BlogPost::factory()->published()->featured()->count(2)->create(['user_id' => $this->author1->id]);
        BlogPost::factory()->published()->featured()->count(2)->create(['user_id' => $this->author2->id]);
        BlogPost::factory()->published()->count(3)->create([
            'is_featured' => false,
            'user_id' => $this->author1->id,
        ]);
        BlogPost::factory()->published()->count(3)->create([
            'is_featured' => false,
            'user_id' => $this->author2->id,
        ]);
        
        // Act: Get blog listing data filtered by author1
        $data = $this->service->getBlogListingData(['author' => $this->author1->id]);
        
        // Assert: Only author1's posts returned
        expect($data['featured_posts'])->toHaveCount(2)
            ->and($data['posts'])->toHaveCount(3)
            ->and($data['featured_posts']->every(fn($post) => $post->user_id === $this->author1->id))->toBeTrue()
            ->and($data['posts']->every(fn($post) => $post->user_id === $this->author1->id))->toBeTrue();
    });
});
