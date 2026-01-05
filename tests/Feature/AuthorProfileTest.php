<?php

/**
 * Author Profile Test Suite
 *
 * Tests public and authenticated viewing of author profile pages including
 * blog posts, statistics, and social features.
 *
 * Test Categories:
 * - Viewing Author Profiles: Public and authenticated access
 * - Author Statistics: Post counts, follower counts, engagement metrics
 * - Author Blog Posts: Published posts listing and pagination
 * - Following Status: Follower/following relationships
 * - Social Features: Follow counts and follow status display
 *
 * Features Tested:
 * - Public profile access for guests
 * - Authenticated user profile viewing
 * - Published posts listing (excludes drafts)
 * - Author statistics display
 * - Following/follower counts
 * - Current user's follow status
 * - Profile data validation
 * - 404 errors for non-existent authors
 */

use App\Models\BlogPost;
use App\Models\User;

// Viewing Author Profiles Tests
test('guests can view author profile pages', function () {
    $author = createTestAdmin([
        'name' => 'Test Author',
        'email' => 'author@test.com',
    ]);

    $response = $this->get(route('author.profile', $author->id));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('AuthorProfile')
        ->has('author')
        ->where('author.name', 'Test Author')
        ->where('author.email', 'author@test.com')
    );
});

test('authenticated users can view author profile pages', function () {
    $member = createTestMember();
    $author = createTestAdmin();

    $response = $this->actingAs($member)->get(route('author.profile', $author->id));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('AuthorProfile')
        ->has('author')
    );
});

test('cannot view profile for non-existent authors', function () {
    $response = $this->get(route('author.profile', 99999));

    $response->assertNotFound();
});

test('cannot view profile for member users (non-authors)', function () {
    $member = createTestMember();

    $response = $this->get(route('author.profile', $member->id));

    $response->assertNotFound();
});

test('can view master admin profile pages', function () {
    $masterAdmin = createTestMasterAdmin([
        'name' => 'Master Admin',
    ]);

    $response = $this->get(route('author.profile', $masterAdmin->id));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('AuthorProfile')
        ->where('author.name', 'Master Admin')
    );
});

test('authors can view their own profile pages', function () {
    $author = createTestAdmin();

    $response = $this->actingAs($author)->get(route('author.profile', $author->id));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('AuthorProfile')
        ->has('author')
    );
});

test('authors can view other authors profile pages', function () {
    $author1 = createTestAdmin();
    $author2 = createTestAdmin(['name' => 'Other Author']);

    $response = $this->actingAs($author1)->get(route('author.profile', $author2->id));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('AuthorProfile')
        ->where('author.name', 'Other Author')
    );
});

// Author Profile Data Tests
test('author profile displays basic information', function () {
    $author = createTestAdmin([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'bio' => 'Software developer and writer',
        'website' => 'https://johndoe.com',
    ]);

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->where('author.name', 'John Doe')
        ->where('author.email', 'john@example.com')
        ->where('author.bio', 'Software developer and writer')
        ->where('author.website', 'https://johndoe.com')
    );
});

test('author profile displays id', function () {
    $author = createTestAdmin();

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->where('author.id', $author->id)
    );
});

test('author profile handles null bio', function () {
    $author = createTestAdmin([
        'bio' => null,
    ]);

    $response = $this->get(route('author.profile', $author->id));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('author.bio', null)
    );
});

test('author profile handles null website', function () {
    $author = createTestAdmin([
        'website' => null,
    ]);

    $response = $this->get(route('author.profile', $author->id));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('author.website', null)
    );
});

// Author Statistics Tests
test('author profile displays followers count', function () {
    $author = User::factory()->admin()->create();
    $followers = User::factory()->count(5)->create();

    foreach ($followers as $follower) {
        $follower->following()->attach($author->id);
    }

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->where('author.followers_count', 5)
    );
});

test('author profile displays zero followers count when no followers', function () {
    $author = createTestAdmin();

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->where('author.followers_count', 0)
    );
});

test('author profile displays following count', function () {
    $author = User::factory()->admin()->create();
    $otherAuthors = User::factory()->admin()->count(3)->create();

    foreach ($otherAuthors as $otherAuthor) {
        $author->following()->attach($otherAuthor->id);
    }

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->where('author.following_count', 3)
    );
});

test('author profile displays zero following count when not following anyone', function () {
    $author = createTestAdmin();

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->where('author.following_count', 0)
    );
});

test('author profile displays published posts count', function () {
    $author = createTestAdmin();
    
    // Create published posts
    BlogPost::factory()->published()->count(7)->create(['user_id' => $author->id]);
    
    // Create draft posts (should not be counted)
    BlogPost::factory()->draft()->count(3)->create(['user_id' => $author->id]);

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->where('author.posts_count', 7)
    );
});

test('author profile displays zero posts count when no published posts', function () {
    $author = createTestAdmin();

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->where('author.posts_count', 0)
    );
});

test('author profile only counts published posts not drafts', function () {
    $author = createTestAdmin();
    
    // Create only draft posts
    BlogPost::factory()->draft()->count(5)->create(['user_id' => $author->id]);

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->where('author.posts_count', 0)
    );
});

// Author Posts Display Tests
test('author profile displays recent published posts', function () {
    $author = createTestAdmin();
    
    $post = BlogPost::factory()->published()->create([
        'user_id' => $author->id,
        'title' => 'Test Blog Post',
        'slug' => 'test-blog-post',
        'excerpt' => 'This is a test excerpt',
    ]);

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->has('posts', 1)
        ->where('posts.0.title', 'Test Blog Post')
        ->where('posts.0.slug', 'test-blog-post')
        ->where('posts.0.excerpt', 'This is a test excerpt')
    );
});

test('author profile displays post metadata', function () {
    $author = createTestAdmin();
    
    $post = BlogPost::factory()->published()->create([
        'user_id' => $author->id,
        'tags' => ['Laravel', 'PHP'],
    ]);

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->has('posts', 1)
        ->where('posts.0.id', $post->id)
        ->has('posts.0.reading_time')
        ->where('posts.0.tags', ['Laravel', 'PHP'])
        ->has('posts.0.published_at')
    );
});

test('author profile limits posts to most recent 10', function () {
    $author = createTestAdmin();
    
    // Create 15 published posts
    BlogPost::factory()->published()->count(15)->create(['user_id' => $author->id]);

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->has('posts', 10)
    );
});

test('author profile shows posts in latest order', function () {
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

    $response->assertInertia(fn ($page) => $page
        ->has('posts', 2)
        ->where('posts.0.title', 'New Post')
        ->where('posts.1.title', 'Old Post')
    );
});

test('author profile does not show draft posts', function () {
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

    $response->assertInertia(fn ($page) => $page
        ->has('posts', 1)
        ->where('posts.0.title', 'Published Post')
    );
});

test('author profile shows empty posts array when author has no published posts', function () {
    $author = createTestAdmin();

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->has('posts', 0)
    );
});

// Following Status Tests
test('author profile shows is_following as false for guests', function () {
    $author = createTestAdmin();

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->where('is_following', false)
    );
});

test('author profile shows is_following as false when user is not following author', function () {
    $member = createTestMember();
    $author = createTestAdmin();

    $response = $this->actingAs($member)->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->where('is_following', false)
    );
});

test('author profile shows is_following as true when user is following author', function () {
    $member = createTestMember();
    $author = createTestAdmin();
    
    $member->following()->attach($author->id);

    $response = $this->actingAs($member)->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->where('is_following', true)
    );
});

test('author viewing their own profile shows is_following as false', function () {
    $author = createTestAdmin();

    $response = $this->actingAs($author)->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->where('is_following', false)
    );
});

test('author following status updates after following', function () {
    $member = createTestMember();
    $author = createTestAdmin();

    // Initially not following
    $response = $this->actingAs($member)->get(route('author.profile', $author->id));
    $response->assertInertia(fn ($page) => $page->where('is_following', false));

    // Follow the author
    $member->following()->attach($author->id);

    // Now following
    $response = $this->actingAs($member)->get(route('author.profile', $author->id));
    $response->assertInertia(fn ($page) => $page->where('is_following', true));
});

// Edge Cases Tests
test('author profile with special characters in name', function () {
    $author = createTestAdmin([
        'name' => "O'Brien & Sons",
    ]);

    $response = $this->get(route('author.profile', $author->id));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('author.name', "O'Brien & Sons")
    );
});

test('author profile with very long bio', function () {
    $longBio = str_repeat('This is a long bio. ', 100);
    $author = createTestAdmin([
        'bio' => $longBio,
    ]);

    $response = $this->get(route('author.profile', $author->id));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('author.bio', $longBio)
    );
});

test('author profile with unicode characters in bio', function () {
    $author = createTestAdmin([
        'bio' => '🚀 Developer | ❤️ Code | 🌍 World',
    ]);

    $response = $this->get(route('author.profile', $author->id));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('author.bio', '🚀 Developer | ❤️ Code | 🌍 World')
    );
});

test('author profile with posts containing no tags', function () {
    $author = createTestAdmin();
    
    BlogPost::factory()->published()->create([
        'user_id' => $author->id,
        'tags' => [],
    ]);

    $response = $this->get(route('author.profile', $author->id));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('posts', 1)
        ->where('posts.0.tags', [])
    );
});

test('author profile statistics are accurate with mixed activity', function () {
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

    $response->assertInertia(fn ($page) => $page
        ->where('author.followers_count', 3)
        ->where('author.following_count', 2)
        ->where('author.posts_count', 5)
    );
});

test('author profile loads efficiently with many posts', function () {
    $author = createTestAdmin();
    
    // Create 50 posts
    BlogPost::factory()->published()->count(50)->create(['user_id' => $author->id]);

    $response = $this->get(route('author.profile', $author->id));

    // Should only load 10 posts
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('posts', 10)
        ->where('author.posts_count', 50)
    );
});

test('different users see same author profile data', function () {
    $author = createTestAdmin(['name' => 'Consistent Author']);
    $user1 = createTestMember();
    $user2 = createTestMember();

    $response1 = $this->actingAs($user1)->get(route('author.profile', $author->id));
    $response2 = $this->actingAs($user2)->get(route('author.profile', $author->id));

    $response1->assertInertia(fn ($page) => $page->where('author.name', 'Consistent Author'));
    $response2->assertInertia(fn ($page) => $page->where('author.name', 'Consistent Author'));
});

test('author profile includes all required fields', function () {
    $author = createTestAdmin();

    $response = $this->get(route('author.profile', $author->id));

    $response->assertInertia(fn ($page) => $page
        ->has('author.id')
        ->has('author.name')
        ->has('author.email')
        ->has('author.bio')
        ->has('author.website')
        ->has('author.followers_count')
        ->has('author.following_count')
        ->has('author.posts_count')
        ->has('posts')
        ->has('is_following')
    );
});
