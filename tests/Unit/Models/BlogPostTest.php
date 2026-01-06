<?php

use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

describe('generateUniqueSlug method', function () {
    it('generates slug from title', function () {
        // Arrange: Title with no existing slug in database
        
        // Act: Generate slug
        $slug = BlogPost::generateUniqueSlug('Test Title');
        
        // Assert: Returns slugified title
        expect($slug)->toBe('test-title');
    });

    it('converts title to lowercase', function () {
        // Arrange: Title in uppercase
        
        // Act: Generate slug from uppercase title
        $slug = BlogPost::generateUniqueSlug('UPPERCASE TITLE');
        
        // Assert: Returns lowercase slug
        expect($slug)->toBe('uppercase-title');
    });

    it('replaces spaces with hyphens', function () {
        // Arrange: Title with multiple words
        
        // Act: Generate slug from multi-word title
        $slug = BlogPost::generateUniqueSlug('Multiple Word Title');
        
        // Assert: Returns hyphenated slug
        expect($slug)->toBe('multiple-word-title');
    });

    it('removes special characters', function () {
        // Arrange: Title with special characters
        
        // Act: Generate slug from title with special characters
        $slug = BlogPost::generateUniqueSlug('Special!@#$%^&*()Characters');
        
        // Assert: Returns clean slug (@ converts to -at- by Laravel's Str::slug)
        expect($slug)->toBe('special-at-characters');
    });

    it('handles unicode characters', function () {
        // Arrange: Title with unicode characters
        
        // Act: Generate slug from title with unicode characters
        $slug = BlogPost::generateUniqueSlug('Hëllö Wörld');
        
        // Assert: Returns ASCII slug
        expect($slug)->toBe('hello-world');
    });

    it('appends counter for duplicate slugs', function () {
        // Arrange: Create a blog post with existing slug
        BlogPost::factory()->create(['slug' => 'duplicate-title']);
        
        // Act: Generate slug for duplicate title
        $slug = BlogPost::generateUniqueSlug('Duplicate Title');
        
        // Assert: Returns slug with counter
        expect($slug)->toBe('duplicate-title-1');
    });

    it('excludes specific id when checking uniqueness', function () {
        // Arrange: Create a blog post with specific slug
        $post = BlogPost::factory()->create(['slug' => 'existing-slug']);
        
        // Act: Generate slug with exclude ID (should allow same slug since we're excluding this ID)
        $slug = BlogPost::generateUniqueSlug('Existing Slug', $post->id);
        
        // Assert: Returns original slug (not duplicate when excluding the ID)
        expect($slug)->toBe('existing-slug');
    });

    it('handles very long titles gracefully', function () {
        // Arrange: Create a very long title (over 200 characters)
        $longTitle = str_repeat('Long Title Word ', 20); // ~240 characters
        
        // Act: Generate slug from long title
        $slug = BlogPost::generateUniqueSlug($longTitle);
        
        // Assert: Returns a slug (Laravel's Str::slug handles this)
        expect($slug)->toBeString();
        expect($slug)->toContain('long-title-word');
    });
});

describe('calculateReadingTime method', function () {
    it('returns 1 minute for 200 words', function () {
        // Arrange: Create content with exactly 200 words
        $content = str_repeat('word ', 200);
        
        // Act: Calculate reading time
        $minutes = BlogPost::calculateReadingTime($content);
        
        // Assert: Should be 1 minute (200 words / 200 wpm)
        expect($minutes)->toBe(1);
    });

    it('returns 2 minutes for 400 words', function () {
        // Arrange: Create content with exactly 400 words
        $content = str_repeat('word ', 400);
        
        // Act: Calculate reading time
        $minutes = BlogPost::calculateReadingTime($content);
        
        // Assert: Should be 2 minutes (400 words / 200 wpm)
        expect($minutes)->toBe(2);
    });

    it('returns 3 minutes for 600 words', function () {
        // Arrange: Create content with exactly 600 words
        $content = str_repeat('word ', 600);
        
        // Act: Calculate reading time
        $minutes = BlogPost::calculateReadingTime($content);
        
        // Assert: Should be 3 minutes (600 words / 200 wpm)
        expect($minutes)->toBe(3);
    });

    it('returns minimum of 1 minute for short content', function () {
        // Arrange: Create content with only 50 words (less than 200)
        $content = str_repeat('word ', 50);
        
        // Act: Calculate reading time
        $minutes = BlogPost::calculateReadingTime($content);
        
        // Assert: Should return minimum of 1 minute
        expect($minutes)->toBe(1);
    });

    it('strips html tags before counting words', function () {
        // Arrange: Create content with HTML tags
        $content = '<p>' . str_repeat('word ', 200) . '</p><div>More content</div>';
        
        // Act: Calculate reading time
        $minutes = BlogPost::calculateReadingTime($content);
        
        // Assert: Should count only text, not HTML tags (202 words / 200 = 1.01 → rounds up to 2)
        expect($minutes)->toBe(2);
    });

    it('handles empty content', function () {
        // Arrange: Empty content string
        $content = '';
        
        // Act: Calculate reading time
        $minutes = BlogPost::calculateReadingTime($content);
        
        // Assert: Should return minimum of 1 minute
        expect($minutes)->toBe(1);
    });

    it('rounds up partial minutes', function () {
        // Arrange: Create content with 250 words (1.25 minutes)
        $content = str_repeat('word ', 250);
        
        // Act: Calculate reading time
        $minutes = BlogPost::calculateReadingTime($content);
        
        // Assert: Should round up to 2 minutes
        expect($minutes)->toBe(2);
    });
});

// Additional test using data provider pattern
describe('calculateReadingTime with data provider', function () {
    $testCases = [
        'short content (50 words)' => [str_repeat('word ', 50), 1],
        'exactly 200 words' => [str_repeat('word ', 200), 1],
        'exactly 400 words' => [str_repeat('word ', 400), 2],
        'exactly 600 words' => [str_repeat('word ', 600), 3],
        '250 words (rounds up)' => [str_repeat('word ', 250), 2],
        '350 words (rounds up)' => [str_repeat('word ', 350), 2],
        'empty content' => ['', 1],
    ];

    foreach ($testCases as $description => $case) {
        it("calculates correct reading time for $description", function () use ($case) {
            [$content, $expectedMinutes] = $case;
            
            $minutes = BlogPost::calculateReadingTime($content);
            
            expect($minutes)->toBe($expectedMinutes);
        });
    }
});
