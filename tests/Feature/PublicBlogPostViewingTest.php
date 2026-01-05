<?php

/**
 * Public Blog Post Viewing Test Suite
 *
 * Tests all public-facing blog functionality including landing page, blog index,
 * individual post viewing, search, filtering, and pagination.
 *
 * Test Categories:
 * - Landing Page (Home): Homepage with featured posts
 * - Blog Index: Main blog listing with pagination
 * - Individual Post Viewing: Single post pages with comments
 * - Search Functionality: Blog post search
 * - Filtering: Tag filtering and sorting
 * - Pagination: Large dataset handling
 * - Published vs Draft: Visibility controls
 * - Comments Display: Comment loading and threading
 *
 * Features Tested:
 * - Guest and authenticated access
 * - Featured posts display
 * - Published post visibility
 * - Draft post hiding from public
 * - Search by title and content
 * - Tag-based filtering
 * - Chronological ordering
 * - Like counts display
 * - Comment counts and display
 * - Author information display
 * - Reading time calculation
 */

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\User;

// Landing Page (Home) Tests
test('guests can view landing page', function () {
    $response = $this->get(route('home'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Welcome'));
});

test('authenticated users can view landing page', function () {
    $user = createTestMember();
    
    $response = $this->actingAs($user)->get(route('home'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Welcome'));
});

test('landing page displays featured posts', function () {
    $author = createTestAdmin();
    $featuredPost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_featured' => true,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('home'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('featured_posts', 1)
        ->where('featured_posts.0.id', $featuredPost->id)
        ->where('featured_posts.0.title', $featuredPost->title)
    );
});

test('landing page displays recent posts', function () {
    $author = createTestAdmin();
    $recentPost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('home'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('posts', 1)
        ->where('posts.0.id', $recentPost->id)
    );
});

test('landing page limits featured posts to 2', function () {
    $author = createTestAdmin();
    BlogPost::factory()->count(5)->create([
        'user_id' => $author->id,
        'is_featured' => true,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn ($page) => $page->has('featured_posts', 2));
});

test('landing page limits recent posts to 6', function () {
    $author = createTestAdmin();
    BlogPost::factory()->count(10)->create([
        'user_id' => $author->id,
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn ($page) => $page->has('posts', 6));
});

test('landing page does not show draft posts', function () {
    $author = createTestAdmin();
    $draftPost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => false,
    ]);
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn ($page) => $page
        ->has('posts', 0)
        ->has('featured_posts', 0)
    );
});

test('landing page does not show future scheduled posts', function () {
    $author = createTestAdmin();
    $futurePost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->addDay(),
    ]);
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn ($page) => $page
        ->has('posts', 0)
        ->has('featured_posts', 0)
    );
});

test('landing page shows posts in latest first order', function () {
    $author = createTestAdmin();
    $oldPost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDays(5),
    ]);
    $newPost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn ($page) => $page
        ->where('posts.0.id', $newPost->id)
        ->where('posts.1.id', $oldPost->id)
    );
});

// Blog List Page Tests
test('guests can view blog list page', function () {
    $response = $this->get(route('blog'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('BlogSimple'));
});

test('authenticated users can view blog list page', function () {
    $user = createTestMember();
    
    $response = $this->actingAs($user)->get(route('blog'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('BlogSimple'));
});

test('blog list displays all published posts', function () {
    $author = createTestAdmin();
    $posts = BlogPost::factory()->count(3)->create([
        'user_id' => $author->id,
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog'));
    
    $response->assertInertia(fn ($page) => $page->has('posts', 3));
});

test('blog list separates featured and regular posts', function () {
    $author = createTestAdmin();
    $featuredPost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_featured' => true,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    $regularPost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog'));
    
    $response->assertInertia(fn ($page) => $page
        ->has('featured_posts', 1)
        ->where('featured_posts.0.id', $featuredPost->id)
        ->has('posts', 1)
        ->where('posts.0.id', $regularPost->id)
    );
});

test('blog list limits featured posts to 4', function () {
    $author = createTestAdmin();
    BlogPost::factory()->count(10)->create([
        'user_id' => $author->id,
        'is_featured' => true,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog'));
    
    $response->assertInertia(fn ($page) => $page->has('featured_posts', 4));
});

test('blog list does not limit regular posts', function () {
    $author = createTestAdmin();
    BlogPost::factory()->count(20)->create([
        'user_id' => $author->id,
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog'));
    
    $response->assertInertia(fn ($page) => $page->has('posts', 20));
});

test('blog list does not show draft posts', function () {
    $author = createTestAdmin();
    BlogPost::factory()->count(3)->create([
        'user_id' => $author->id,
        'is_published' => false,
    ]);
    
    $response = $this->get(route('blog'));
    
    $response->assertInertia(fn ($page) => $page
        ->has('posts', 0)
        ->has('featured_posts', 0)
    );
});

// Search and Filter Tests
test('landing page can search posts by title', function () {
    $author = createTestAdmin();
    $matchingPost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'title' => 'Laravel Testing Guide',
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    $otherPost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'title' => 'React Development',
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('home', ['search' => 'Laravel']));
    
    $response->assertInertia(fn ($page) => $page
        ->where('filters.search', 'Laravel')
        ->has('posts', 1)
        ->where('posts.0.id', $matchingPost->id)
    );
});

test('landing page can search posts by excerpt', function () {
    $author = createTestAdmin();
    $matchingPost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'excerpt' => 'A comprehensive guide to PHP testing',
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('home', ['search' => 'PHP testing']));
    
    $response->assertInertia(fn ($page) => $page->has('posts', 1));
});

test('landing page can search posts by content', function () {
    $author = createTestAdmin();
    $matchingPost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'content' => 'This post contains information about TypeScript',
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('home', ['search' => 'TypeScript']));
    
    $response->assertInertia(fn ($page) => $page->has('posts', 1));
});

test('blog list can filter posts by tag', function () {
    $author = createTestAdmin();
    $laravelPost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'tags' => ['laravel', 'php'],
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    $reactPost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'tags' => ['react', 'javascript'],
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog', ['tag' => 'laravel']));
    
    $response->assertInertia(fn ($page) => $page
        ->where('filters.tag', 'laravel')
        ->has('posts', 1)
        ->where('posts.0.id', $laravelPost->id)
    );
});

test('blog list can filter posts by author', function () {
    $author1 = createTestAdmin();
    $author2 = createTestAdmin();
    
    $post1 = BlogPost::factory()->create([
        'user_id' => $author1->id,
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    $post2 = BlogPost::factory()->create([
        'user_id' => $author2->id,
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog', ['author' => $author1->id]));
    
    $response->assertInertia(fn ($page) => $page
        ->where('filters.author', (string) $author1->id)
        ->has('posts', 1)
        ->where('posts.0.id', $post1->id)
    );
});

test('blog list can combine search and tag filters', function () {
    $author = createTestAdmin();
    $matchingPost = BlogPost::factory()->create([
        'user_id' => $author->id,
        'title' => 'Laravel Testing Guide',
        'tags' => ['laravel', 'testing'],
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    $nonMatchingPost1 = BlogPost::factory()->create([
        'user_id' => $author->id,
        'title' => 'Laravel Routing',
        'tags' => ['laravel', 'routing'],
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    $nonMatchingPost2 = BlogPost::factory()->create([
        'user_id' => $author->id,
        'title' => 'PHP Testing Guide',
        'tags' => ['php', 'testing'],
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog', ['search' => 'Laravel', 'tag' => 'testing']));
    
    $response->assertInertia(fn ($page) => $page
        ->has('posts', 1)
        ->where('posts.0.id', $matchingPost->id)
    );
});

test('landing page provides available tags list', function () {
    $author = createTestAdmin();
    BlogPost::factory()->create([
        'user_id' => $author->id,
        'tags' => ['laravel', 'php'],
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    BlogPost::factory()->create([
        'user_id' => $author->id,
        'tags' => ['react', 'javascript'],
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn ($page) => $page
        ->has('availableTags', 4)
        ->where('availableTags.0', 'javascript')
        ->where('availableTags.1', 'laravel')
        ->where('availableTags.2', 'php')
        ->where('availableTags.3', 'react')
    );
});

test('landing page provides available authors list', function () {
    $author1 = createTestAdmin(['name' => 'Author One']);
    $author2 = createTestAdmin(['name' => 'Author Two']);
    $memberWithoutPosts = createTestMember(['name' => 'Member User']);
    
    BlogPost::factory()->create([
        'user_id' => $author1->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    BlogPost::factory()->create([
        'user_id' => $author2->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn ($page) => $page
        ->has('availableAuthors', 2)
    );
});

test('available tags only includes tags from published posts', function () {
    $author = createTestAdmin();
    BlogPost::factory()->create([
        'user_id' => $author->id,
        'tags' => ['published-tag'],
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    BlogPost::factory()->create([
        'user_id' => $author->id,
        'tags' => ['draft-tag'],
        'is_published' => false,
    ]);
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn ($page) => $page
        ->has('availableTags', 1)
        ->where('availableTags.0', 'published-tag')
    );
});

test('available authors only includes authors with published posts', function () {
    $authorWithPublished = createTestAdmin();
    $authorWithDraftsOnly = createTestAdmin();
    
    BlogPost::factory()->create([
        'user_id' => $authorWithPublished->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    BlogPost::factory()->create([
        'user_id' => $authorWithDraftsOnly->id,
        'is_published' => false,
    ]);
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn ($page) => $page->has('availableAuthors', 1));
});

// Individual Blog Post View Tests
test('guests can view published blog post by slug', function () {
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'slug' => 'test-post',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('BlogPost')
        ->where('post.id', $post->id)
        ->where('post.title', $post->title)
        ->where('post.slug', $post->slug)
    );
});

test('authenticated users can view published blog post', function () {
    $user = createTestMember();
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->actingAs($user)->get(route('blog.show', $post->slug));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('post.id', $post->id)
    );
});

test('cannot view draft posts via public route', function () {
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => false,
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertStatus(404);
});

test('cannot view future scheduled posts via public route', function () {
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->addDay(),
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertStatus(404);
});

test('blog post view returns 404 for non-existent slug', function () {
    $response = $this->get(route('blog.show', 'non-existent-slug'));
    
    $response->assertStatus(404);
});

test('blog post view displays all post metadata', function () {
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'title' => 'Test Post Title',
        'excerpt' => 'Test excerpt',
        'content' => 'Test content',
        'tags' => ['laravel', 'php'],
        'is_featured' => true,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertInertia(fn ($page) => $page
        ->where('post.title', 'Test Post Title')
        ->where('post.excerpt', 'Test excerpt')
        ->where('post.content', 'Test content')
        ->has('post.tags', 2)
        ->where('post.is_featured', true)
        ->has('post.reading_time')
        ->has('post.published_at')
    );
});

test('blog post view displays author information', function () {
    $author = createTestAdmin(['name' => 'John Doe']);
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertInertia(fn ($page) => $page
        ->where('post.author.id', $author->id)
        ->where('post.author.name', 'John Doe')
        ->has('post.author.avatar')
    );
});

test('blog post view displays comments count', function () {
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $user = createTestMember();
    Comment::factory()->count(3)->create([
        'blog_post_id' => $post->id,
        'user_id' => $user->id,
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertInertia(fn ($page) => $page->has('post.comments', 3));
});

test('blog post view displays only top-level comments', function () {
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $user = createTestMember();
    $parentComment = Comment::factory()->create([
        'blog_post_id' => $post->id,
        'user_id' => $user->id,
        'parent_id' => null,
    ]);
    $replyComment = Comment::factory()->create([
        'blog_post_id' => $post->id,
        'user_id' => $user->id,
        'parent_id' => $parentComment->id,
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertInertia(fn ($page) => $page
        ->has('post.comments', 1)
        ->has('post.comments.0.replies', 1)
    );
});

test('blog post view displays likes count', function () {
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $users = User::factory()->count(5)->create();
    foreach ($users as $user) {
        $post->likes()->create(['user_id' => $user->id]);
    }
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertInertia(fn ($page) => $page->where('post.likes_count', 5));
});

test('blog post view shows user_has_liked as false for guests', function () {
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertInertia(fn ($page) => $page->where('post.user_has_liked', false));
});

test('blog post view shows user_has_liked as true when user liked post', function () {
    $user = createTestMember();
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $post->likes()->create(['user_id' => $user->id]);
    
    $response = $this->actingAs($user)->get(route('blog.show', $post->slug));
    
    $response->assertInertia(fn ($page) => $page->where('post.user_has_liked', true));
});

test('blog post view shows user_has_liked as false when user has not liked post', function () {
    $user = createTestMember();
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->actingAs($user)->get(route('blog.show', $post->slug));
    
    $response->assertInertia(fn ($page) => $page->where('post.user_has_liked', false));
});

test('blog post view shows is_following_author as false for guests', function () {
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertInertia(fn ($page) => $page->where('post.is_following_author', false));
});

test('blog post view shows is_following_author as true when user follows author', function () {
    $user = createTestMember();
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $user->following()->attach($author->id);
    
    $response = $this->actingAs($user)->get(route('blog.show', $post->slug));
    
    $response->assertInertia(fn ($page) => $page->where('post.is_following_author', true));
});

test('blog post view shows is_following_author as false when user does not follow author', function () {
    $user = createTestMember();
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->actingAs($user)->get(route('blog.show', $post->slug));
    
    $response->assertInertia(fn ($page) => $page->where('post.is_following_author', false));
});

// Edge Cases and Special Scenarios
test('blog post view handles posts with no tags', function () {
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'tags' => [],
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertInertia(fn ($page) => $page
        ->where('post.tags', [])
    );
});

test('blog post view handles posts with no comments', function () {
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertInertia(fn ($page) => $page->has('post.comments', 0));
});

test('blog post view handles posts with no likes', function () {
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertInertia(fn ($page) => $page->where('post.likes_count', 0));
});

test('landing page handles empty blog with no posts', function () {
    $response = $this->get(route('home'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('posts', 0)
        ->has('featured_posts', 0)
        ->has('availableTags', 0)
        ->has('availableAuthors', 0)
    );
});

test('blog list handles empty blog with no posts', function () {
    $response = $this->get(route('blog'));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('posts', 0)
        ->has('featured_posts', 0)
    );
});

test('search with no results returns empty list', function () {
    $author = createTestAdmin();
    BlogPost::factory()->create([
        'user_id' => $author->id,
        'title' => 'Laravel Guide',
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog', ['search' => 'NonExistentTerm']));
    
    $response->assertInertia(fn ($page) => $page
        ->has('posts', 0)
        ->has('featured_posts', 0)
    );
});

test('blog post view handles long content correctly', function () {
    $author = createTestAdmin();
    $longContent = str_repeat('Lorem ipsum dolor sit amet. ', 500);
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'content' => $longContent,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('post.content', $longContent)
    );
});

test('blog post view handles special characters in content', function () {
    $author = createTestAdmin();
    $specialContent = '<script>alert("XSS")</script> & special chars: é à ü ñ';
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'content' => $specialContent,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('post.content', $specialContent)
    );
});

test('landing page with mixed featured and regular posts displays correctly', function () {
    $author = createTestAdmin();
    BlogPost::factory()->count(3)->create([
        'user_id' => $author->id,
        'is_featured' => true,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    BlogPost::factory()->count(7)->create([
        'user_id' => $author->id,
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('home'));
    
    $response->assertInertia(fn ($page) => $page
        ->has('featured_posts', 2) // Limited to 2
        ->has('posts', 6) // Limited to 6
    );
});

test('case insensitive search works correctly', function () {
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'title' => 'Laravel Framework Guide',
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response1 = $this->get(route('blog', ['search' => 'laravel']));
    $response2 = $this->get(route('blog', ['search' => 'LARAVEL']));
    $response3 = $this->get(route('blog', ['search' => 'LaRaVeL']));
    
    $response1->assertInertia(fn ($page) => $page->has('posts', 1));
    $response2->assertInertia(fn ($page) => $page->has('posts', 1));
    $response3->assertInertia(fn ($page) => $page->has('posts', 1));
});

test('partial search matches work correctly', function () {
    $author = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $author->id,
        'title' => 'Understanding Laravel Framework',
        'is_featured' => false,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog', ['search' => 'Laravel']));
    
    $response->assertInertia(fn ($page) => $page->has('posts', 1));
});

test('admin authors own posts are visible on public pages', function () {
    $admin = createTestAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $admin->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('post.id', $post->id)
        ->where('post.author.id', $admin->id)
    );
});

test('master admin authors posts are visible on public pages', function () {
    $masterAdmin = createTestMasterAdmin();
    $post = BlogPost::factory()->create([
        'user_id' => $masterAdmin->id,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);
    
    $response = $this->get(route('blog.show', $post->slug));
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('post.author.id', $masterAdmin->id)
    );
});
