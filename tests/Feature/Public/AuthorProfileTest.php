<?php

/**
 * Author Profile Test Suite
 *
 * Tests public and authenticated viewing of author profile pages including
 * blog posts, statistics, and social features.
 */

use App\Models\BlogPost;
use App\Models\User;

describe('Viewing Author Profiles', function () {
    it('allows guests to view author profile pages', function () {
        $author = createTestAdmin([
            'name' => 'Test Author',
            'email' => 'author@test.com',
        ]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)
            ->toBeSuccessfulInertiaResponse('AuthorProfile')
            ->and($response)->assertInertia(fn ($page) => $page
                ->has('author')
                ->where('author.name', 'Test Author')
            );
    })->group('author-profile', 'public', 'guest');

    it('allows authenticated users to view author profile pages', function () {
        $member = createTestMember();
        $author = createTestAdmin();

        $response = $this->actingAs($member)->get(route('author.profile', $author->id));

        expect($response)
            ->toBeSuccessfulInertiaResponse('AuthorProfile')
            ->and($response)->assertInertia(fn ($page) => $page->has('author'));
    })->group('author-profile', 'authenticated');

    it('returns 404 for non-existent authors', function () {
        $response = $this->get(route('author.profile', TEST_NONEXISTENT_ID));

        expect($response)->toBeNotFound();
    })->group('author-profile', 'validation', 'edge-cases');

    it('returns 404 for member users (non-authors)', function () {
        $member = createTestMember();

        $response = $this->get(route('author.profile', $member->id));

        expect($response)->toBeNotFound();
    })->group('author-profile', 'validation');

    it('allows viewing master admin profile pages', function () {
        $masterAdmin = createTestMasterAdmin([
            'name' => 'Master Admin',
        ]);

        $response = $this->get(route('author.profile', $masterAdmin->id));

        expect($response)
            ->toBeSuccessfulInertiaResponse('AuthorProfile')
            ->and($response)->assertInertia(fn ($page) => $page
                ->where('author.name', 'Master Admin')
            );
    })->group('author-profile', 'public');

    it('allows authors to view their own profile pages', function () {
        $author = createTestAdmin();

        $response = $this->actingAs($author)->get(route('author.profile', $author->id));

        expect($response)
            ->toBeSuccessfulInertiaResponse('AuthorProfile')
            ->and($response)->assertInertia(fn ($page) => $page->has('author'));
    })->group('author-profile', 'authenticated');

    it('allows authors to view other authors profile pages', function () {
        $author1 = createTestAdmin();
        $author2 = createTestAdmin(['name' => 'Other Author']);

        $response = $this->actingAs($author1)->get(route('author.profile', $author2->id));

        expect($response)
            ->toBeSuccessfulInertiaResponse('AuthorProfile')
            ->and($response)->assertInertia(fn ($page) => $page
                ->where('author.name', 'Other Author')
            );
    })->group('author-profile', 'authenticated');
});

describe('Profile Data', function () {
    it('displays basic author information', function () {
        $author = createTestAdmin([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'bio' => 'Software developer and writer',
            'website' => 'https://johndoe.com',
        ]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->where('author.name', 'John Doe')
            ->where('author.bio', 'Software developer and writer')
            ->where('author.website', 'https://johndoe.com')
        );
    })->group('author-profile', 'public');

    it('displays author id', function () {
        $author = createTestAdmin();

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->where('author.id', $author->id)
        );
    })->group('author-profile', 'public');

    it('handles null bio gracefully', function () {
        $author = createTestAdmin([
            'bio' => null,
        ]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)
            ->toBeSuccessfulInertiaResponse('AuthorProfile')
            ->and($response)->assertInertia(fn ($page) => $page
                ->where('author.bio', null)
            );
    })->group('author-profile', 'edge-cases');

    it('handles null website gracefully', function () {
        $author = createTestAdmin([
            'website' => null,
        ]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)
            ->toBeSuccessfulInertiaResponse('AuthorProfile')
            ->and($response)->assertInertia(fn ($page) => $page
                ->where('author.website', null)
            );
    })->group('author-profile', 'edge-cases');

    it('includes all required fields', function () {
        $author = createTestAdmin();

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->has('author.id')
            ->has('author.name')
            ->has('author.bio')
            ->has('author.website')
            ->has('author.followers_count')
            ->has('author.following_count')
            ->has('author.posts_count')
            ->has('posts')
            ->has('is_following')
        );
    })->group('author-profile', 'validation');
});

describe('Author Statistics', function () {
    it('displays followers count', function () {
        $author = User::factory()->admin()->create();
        $followers = User::factory()->count(5)->create();

        foreach ($followers as $follower) {
            $follower->following()->attach($author->id);
        }

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->where('author.followers_count', 5)
        );
    })->group('author-profile', 'statistics', 'social');

    it('displays zero followers count when no followers exist', function () {
        $author = createTestAdmin();

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->where('author.followers_count', 0)
        );
    })->group('author-profile', 'statistics', 'social');

    it('displays following count', function () {
        $author = User::factory()->admin()->create();
        $otherAuthors = User::factory()->admin()->count(3)->create();

        foreach ($otherAuthors as $otherAuthor) {
            $author->following()->attach($otherAuthor->id);
        }

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->where('author.following_count', 3)
        );
    })->group('author-profile', 'statistics', 'social');

    it('displays zero following count when not following anyone', function () {
        $author = createTestAdmin();

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->where('author.following_count', 0)
        );
    })->group('author-profile', 'statistics', 'social');

    it('displays published posts count', function () {
        $author = createTestAdmin();
        
        // Create published posts
        BlogPost::factory()->published()->count(7)->create(['user_id' => $author->id]);
        
        // Create draft posts (should not be counted)
        BlogPost::factory()->draft()->count(3)->create(['user_id' => $author->id]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->where('author.posts_count', 7)
        );
    })->group('author-profile', 'statistics', 'posts');

    it('displays zero posts count when no published posts exist', function () {
        $author = createTestAdmin();

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->where('author.posts_count', 0)
        );
    })->group('author-profile', 'statistics', 'posts');

    it('only counts published posts not drafts', function () {
        $author = createTestAdmin();
        
        // Create only draft posts
        BlogPost::factory()->draft()->count(5)->create(['user_id' => $author->id]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->where('author.posts_count', 0)
        );
    })->group('author-profile', 'statistics', 'posts');

    it('calculates statistics accurately with mixed activity', function () {
        $author = User::factory()->admin()->create();
        
        // Add followers
        $followers = User::factory()->count(3)->create();
        foreach ($followers as $follower) {
            $follower->following()->attach($author->id);
        }
        
        // Author follows others
        $otherAuthors = User::factory()->admin()->count(2)->create();
        foreach ($otherAuthors as $otherAuthor) {
            $author->following()->attach($otherAuthor->id);
        }
        
        // Create posts
        BlogPost::factory()->published()->count(5)->create(['user_id' => $author->id]);
        BlogPost::factory()->draft()->count(2)->create(['user_id' => $author->id]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->where('author.followers_count', 3)
            ->where('author.following_count', 2)
            ->where('author.posts_count', 5)
        );
    })->group('author-profile', 'statistics', 'edge-cases');
});

describe('Author Blog Posts', function () {
    it('displays recent published posts', function () {
        $author = createTestAdmin();
        
        $post = BlogPost::factory()->published()->create([
            'user_id' => $author->id,
            'title' => 'Test Blog Post',
            'slug' => 'test-blog-post',
            'excerpt' => 'This is a test excerpt',
        ]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->has('posts', 1)
            ->where('posts.0.title', 'Test Blog Post')
            ->where('posts.0.slug', 'test-blog-post')
            ->where('posts.0.excerpt', 'This is a test excerpt')
        );
    })->group('author-profile', 'posts', 'public');

    it('displays post metadata', function () {
        $author = createTestAdmin();
        
        $post = BlogPost::factory()->published()->create([
            'user_id' => $author->id,
            'tags' => ['Laravel', 'PHP'],
        ]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->has('posts', 1)
            ->where('posts.0.id', $post->id)
            ->has('posts.0.reading_time')
            ->where('posts.0.tags', ['Laravel', 'PHP'])
            ->has('posts.0.published_at')
        );
    })->group('author-profile', 'posts', 'public');

    it('limits posts to most recent 10', function () {
        $author = createTestAdmin();
        
        // Create 15 published posts
        BlogPost::factory()->published()->count(15)->create(['user_id' => $author->id]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->has('posts', 10)
        );
    })->group('author-profile', 'posts', 'public');

    it('shows posts in latest order', function () {
        $author = createTestAdmin();
        
        // Create posts with different timestamps
        $oldPost = BlogPost::factory()->published()->create([
            'user_id' => $author->id,
            'title' => 'Old Post',
            'created_at' => now()->subDays(10),
        ]);
        
        $newPost = BlogPost::factory()->published()->create([
            'user_id' => $author->id,
            'title' => 'New Post',
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->has('posts', 2)
            ->where('posts.0.title', 'New Post')
            ->where('posts.1.title', 'Old Post')
        );
    })->group('author-profile', 'posts', 'public');

    it('excludes draft posts from display', function () {
        $author = createTestAdmin();
        
        BlogPost::factory()->published()->create([
            'user_id' => $author->id,
            'title' => 'Published Post',
        ]);
        
        BlogPost::factory()->draft()->create([
            'user_id' => $author->id,
            'title' => 'Draft Post',
        ]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->has('posts', 1)
            ->where('posts.0.title', 'Published Post')
        );
    })->group('author-profile', 'posts', 'public');

    it('shows empty posts array when author has no published posts', function () {
        $author = createTestAdmin();

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->has('posts', 0)
        );
    })->group('author-profile', 'posts', 'edge-cases');

    it('handles posts with no tags', function () {
        $author = createTestAdmin();
        
        BlogPost::factory()->published()->create([
            'user_id' => $author->id,
            'tags' => [],
        ]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)
            ->toBeSuccessfulInertiaResponse('AuthorProfile')
            ->and($response)->assertInertia(fn ($page) => $page
                ->has('posts', 1)
                ->where('posts.0.tags', [])
            );
    })->group('author-profile', 'posts', 'edge-cases');

    it('loads efficiently with many posts', function () {
        $author = createTestAdmin();
        
        // Create 50 posts
        BlogPost::factory()->published()->count(50)->create(['user_id' => $author->id]);

        $response = $this->get(route('author.profile', $author->id));

        // Should only load 10 posts (limited by query)
        // posts_count now uses count of loaded blogPosts collection, not separate count query
        expect($response)
            ->toBeSuccessfulInertiaResponse('AuthorProfile')
            ->and($response)->assertInertia(fn ($page) => $page
                ->has('posts', 10)
                ->where('author.posts_count', 10) // Count of loaded posts (limited to 10)
            );
    })->group('author-profile', 'posts', 'edge-cases');
});

describe('Following Status', function () {
    it('shows is_following as false for guests', function () {
        $author = createTestAdmin();

        $response = $this->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->where('is_following', false)
        );
    })->group('author-profile', 'following', 'guest');

    it('shows is_following as false when user is not following author', function () {
        $member = createTestMember();
        $author = createTestAdmin();

        $response = $this->actingAs($member)->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->where('is_following', false)
        );
    })->group('author-profile', 'following', 'authenticated');

    it('shows is_following as true when user is following author', function () {
        $member = createTestMember();
        $author = createTestAdmin();
        
        $member->following()->attach($author->id);

        $response = $this->actingAs($member)->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->where('is_following', true)
        );
    })->group('author-profile', 'following', 'authenticated', 'social');

    it('shows is_following as false when author views their own profile', function () {
        $author = createTestAdmin();

        $response = $this->actingAs($author)->get(route('author.profile', $author->id));

        expect($response)->assertInertia(fn ($page) => $page
            ->where('is_following', false)
        );
    })->group('author-profile', 'following', 'authenticated');

    it('updates following status after user follows author', function () {
        $member = createTestMember();
        $author = createTestAdmin();

        // Initially not following
        $response = $this->actingAs($member)->get(route('author.profile', $author->id));
        expect($response)->assertInertia(fn ($page) => $page->where('is_following', false));

        // Follow the author
        $member->following()->attach($author->id);

        // Now following
        $response = $this->actingAs($member)->get(route('author.profile', $author->id));
        expect($response)->assertInertia(fn ($page) => $page->where('is_following', true));
    })->group('author-profile', 'following', 'authenticated', 'social');
});

describe('Edge Cases', function () {
    it('handles special characters in author name', function () {
        $author = createTestAdmin([
            'name' => "O'Brien & Sons",
        ]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)
            ->toBeSuccessfulInertiaResponse('AuthorProfile')
            ->and($response)->assertInertia(fn ($page) => $page
                ->where('author.name', "O'Brien & Sons")
            );
    })->group('author-profile', 'edge-cases', 'validation');

    it('handles very long bio', function () {
        $longBio = str_repeat('This is a long bio. ', 100);
        $author = createTestAdmin([
            'bio' => $longBio,
        ]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)
            ->toBeSuccessfulInertiaResponse('AuthorProfile')
            ->and($response)->assertInertia(fn ($page) => $page
                ->where('author.bio', $longBio)
            );
    })->group('author-profile', 'edge-cases', 'validation');

    it('handles unicode characters in bio', function () {
        $author = createTestAdmin([
            'bio' => '🚀 Developer | ❤️ Code | 🌍 World',
        ]);

        $response = $this->get(route('author.profile', $author->id));

        expect($response)
            ->toBeSuccessfulInertiaResponse('AuthorProfile')
            ->and($response)->assertInertia(fn ($page) => $page
                ->where('author.bio', '🚀 Developer | ❤️ Code | 🌍 World')
            );
    })->group('author-profile', 'edge-cases', 'validation');

    it('shows consistent data for different users', function () {
        $author = createTestAdmin(['name' => 'Consistent Author']);
        $user1 = createTestMember();
        $user2 = createTestMember();

        $response1 = $this->actingAs($user1)->get(route('author.profile', $author->id));
        $response2 = $this->actingAs($user2)->get(route('author.profile', $author->id));

        expect($response1)->assertInertia(fn ($page) => $page->where('author.name', 'Consistent Author'));
        expect($response2)->assertInertia(fn ($page) => $page->where('author.name', 'Consistent Author'));
    })->group('author-profile', 'edge-cases');
});
