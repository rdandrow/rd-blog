<?php

/**
 * Public Blog Post Viewing Test Suite
 *
 * Tests all public-facing blog functionality including landing page, blog index,
 * individual post viewing, search, filtering, and pagination.
 */

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\User;

describe('Landing Page (Home)', function () {
    it('allows guests to view landing page', function () {
        $response = $this->get(route('home'));
        
        expect($response)->toBeSuccessfulInertiaResponse('Welcome');
    })->group('public', 'guest', 'home');

    it('allows authenticated users to view landing page', function () {
        $user = createTestMember();
        
        $response = $this->actingAs($user)->get(route('home'));
        
        expect($response)->toBeSuccessfulInertiaResponse('Welcome');
    })->group('public', 'authenticated', 'home');

    it('displays featured posts', function () {
        $author = createTestAdmin();
        $featuredPost = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_featured' => true,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('home'));
        
        expect($response)->toBeSuccessfulInertiaResponse('Welcome')
            ->and($response)->assertInertia(fn ($page) => $page
                ->has('featured_posts', 1)
                ->where('featured_posts.0.id', $featuredPost->id)
                ->where('featured_posts.0.title', $featuredPost->title)
            );
    })->group('public', 'home', 'featured');

    it('displays recent posts', function () {
        $author = createTestAdmin();
        $recentPost = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_featured' => false,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('home'));
        
        expect($response)->toBeSuccessfulInertiaResponse('Welcome')
            ->and($response)->assertInertia(fn ($page) => $page
                ->has('posts', 1)
                ->where('posts.0.id', $recentPost->id)
            );
    })->group('public', 'home');

    it('limits featured posts to 2', function () {
        $author = createTestAdmin();
        BlogPost::factory()->count(5)->create([
            'user_id' => $author->id,
            'is_featured' => true,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('home'));
        
        expect($response)->assertInertia(fn ($page) => $page->has('featured_posts', 2));
    })->group('public', 'home', 'featured');

    it('limits recent posts to 6', function () {
        $author = createTestAdmin();
        BlogPost::factory()->count(10)->create([
            'user_id' => $author->id,
            'is_featured' => false,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('home'));
        
        expect($response)->assertInertia(fn ($page) => $page->has('posts', 6));
    })->group('public', 'home');

    it('does not show draft posts', function () {
        $author = createTestAdmin();
        $draftPost = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => false,
        ]);
        
        $response = $this->get(route('home'));
        
        expect($response)->assertInertia(fn ($page) => $page
            ->has('posts', 0)
            ->has('featured_posts', 0)
        );
    })->group('public', 'home', 'visibility');

    it('does not show future scheduled posts', function () {
        $author = createTestAdmin();
        $futurePost = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);
        
        $response = $this->get(route('home'));
        
        expect($response)->assertInertia(fn ($page) => $page
            ->has('posts', 0)
            ->has('featured_posts', 0)
        );
    })->group('public', 'home', 'visibility');

    it('shows posts in latest first order', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page
            ->where('posts.0.id', $newPost->id)
            ->where('posts.1.id', $oldPost->id)
        );
    })->group('public', 'home');
});

describe('Blog Index (Listing)', function () {
    it('allows guests to view blog list page', function () {
        $response = $this->get(route('blog'));
        
        expect($response)->toBeSuccessfulInertiaResponse('BlogSimple');
    })->group('public', 'guest', 'blog-list');

    it('allows authenticated users to view blog list page', function () {
        $user = createTestMember();
        
        $response = $this->actingAs($user)->get(route('blog'));
        
        expect($response)->toBeSuccessfulInertiaResponse('BlogSimple');
    })->group('public', 'authenticated', 'blog-list');

    it('displays all published posts', function () {
        $author = createTestAdmin();
        $posts = BlogPost::factory()->count(3)->create([
            'user_id' => $author->id,
            'is_featured' => false,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog'));
        
        expect($response)->assertInertia(fn ($page) => $page->has('posts', 3));
    })->group('public', 'blog-list');

    it('separates featured and regular posts', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page
            ->has('featured_posts', 1)
            ->where('featured_posts.0.id', $featuredPost->id)
            ->has('posts', 1)
            ->where('posts.0.id', $regularPost->id)
        );
    })->group('public', 'blog-list', 'featured');

    it('limits featured posts to 4', function () {
        $author = createTestAdmin();
        BlogPost::factory()->count(10)->create([
            'user_id' => $author->id,
            'is_featured' => true,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog'));
        
        expect($response)->assertInertia(fn ($page) => $page->has('featured_posts', 4));
    })->group('public', 'blog-list', 'featured');

    it('does not limit regular posts', function () {
        $author = createTestAdmin();
        BlogPost::factory()->count(20)->create([
            'user_id' => $author->id,
            'is_featured' => false,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog'));
        
        expect($response)->assertInertia(fn ($page) => $page->has('posts', 20));
    })->group('public', 'blog-list');

    it('does not show draft posts', function () {
        $author = createTestAdmin();
        BlogPost::factory()->count(3)->create([
            'user_id' => $author->id,
            'is_published' => false,
        ]);
        
        $response = $this->get(route('blog'));
        
        expect($response)->assertInertia(fn ($page) => $page
            ->has('posts', 0)
            ->has('featured_posts', 0)
        );
    })->group('public', 'blog-list', 'visibility');
});

describe('Search and Filtering', function () {
    it('can search posts by title on landing page', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page
            ->where('filters.search', 'Laravel')
            ->has('posts', 1)
            ->where('posts.0.id', $matchingPost->id)
        );
    })->group('public', 'search', 'home');

    it('can search posts by excerpt on landing page', function () {
        $author = createTestAdmin();
        $matchingPost = BlogPost::factory()->create([
            'user_id' => $author->id,
            'excerpt' => 'A comprehensive guide to PHP testing',
            'is_featured' => false,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('home', ['search' => 'PHP testing']));
        
        expect($response)->assertInertia(fn ($page) => $page->has('posts', 1));
    })->group('public', 'search', 'home');

    it('can search posts by content on landing page', function () {
        $author = createTestAdmin();
        $matchingPost = BlogPost::factory()->create([
            'user_id' => $author->id,
            'content' => 'This post contains information about TypeScript',
            'is_featured' => false,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('home', ['search' => 'TypeScript']));
        
        expect($response)->assertInertia(fn ($page) => $page->has('posts', 1));
    })->group('public', 'search', 'home');

    it('can filter posts by tag', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page
            ->where('filters.tag', 'laravel')
            ->has('posts', 1)
            ->where('posts.0.id', $laravelPost->id)
        );
    })->group('public', 'filtering', 'blog-list');

    it('can filter posts by author', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page
            ->where('filters.author', (string) $author1->id)
            ->has('posts', 1)
            ->where('posts.0.id', $post1->id)
        );
    })->group('public', 'filtering', 'blog-list');

    it('can combine search and tag filters', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page
            ->has('posts', 1)
            ->where('posts.0.id', $matchingPost->id)
        );
    })->group('public', 'search', 'filtering', 'blog-list');
});

describe('Available Filters', function () {
    it('provides available tags list on landing page', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page
            ->has('availableTags', 4)
            ->where('availableTags.0', 'javascript')
            ->where('availableTags.1', 'laravel')
            ->where('availableTags.2', 'php')
            ->where('availableTags.3', 'react')
        );
    })->group('public', 'filtering', 'home');

    it('provides available authors list on landing page', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page->has('availableAuthors', 2));
    })->group('public', 'filtering', 'home');

    it('includes only tags from published posts', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page
            ->has('availableTags', 1)
            ->where('availableTags.0', 'published-tag')
        );
    })->group('public', 'filtering', 'visibility');

    it('includes only authors with published posts', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page->has('availableAuthors', 1));
    })->group('public', 'filtering', 'visibility');
});

describe('Individual Post Viewing', function () {
    it('allows guests to view published blog post by slug', function () {
        $author = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'slug' => 'test-post',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog.show', $post->slug));
        
        expect($response)->toBeSuccessfulInertiaResponse('BlogPost')
            ->and($response)->assertInertia(fn ($page) => $page
                ->where('post.id', $post->id)
                ->where('post.title', $post->title)
                ->where('post.slug', $post->slug)
            );
    })->group('public', 'guest', 'blog-post');

    it('allows authenticated users to view published blog post', function () {
        $user = createTestMember();
        $author = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->actingAs($user)->get(route('blog.show', $post->slug));
        
        expect($response)->toBeSuccessfulInertiaResponse('BlogPost')
            ->and($response)->assertInertia(fn ($page) => $page->where('post.id', $post->id));
    })->group('public', 'authenticated', 'blog-post');

    it('cannot view draft posts via public route', function () {
        $author = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => false,
        ]);
        
        $response = $this->get(route('blog.show', $post->slug));
        
        expect($response)->toBeNotFound();
    })->group('public', 'blog-post', 'visibility');

    it('cannot view future scheduled posts via public route', function () {
        $author = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);
        
        $response = $this->get(route('blog.show', $post->slug));
        
        expect($response)->toBeNotFound();
    })->group('public', 'blog-post', 'visibility');

    it('returns 404 for non-existent slug', function () {
        $response = $this->get(route('blog.show', 'non-existent-slug'));
        
        expect($response)->toBeNotFound();
    })->group('public', 'blog-post');
});

describe('Post Metadata Display', function () {
    it('displays all post metadata', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page
            ->where('post.title', 'Test Post Title')
            ->where('post.excerpt', 'Test excerpt')
            ->where('post.content', 'Test content')
            ->has('post.tags', 2)
            ->where('post.is_featured', true)
            ->has('post.reading_time')
            ->has('post.published_at')
        );
    })->group('public', 'blog-post', 'metadata');

    it('displays author information', function () {
        $author = createTestAdmin(['name' => 'John Doe']);
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog.show', $post->slug));
        
        expect($response)->assertInertia(fn ($page) => $page
            ->where('post.author.id', $author->id)
            ->where('post.author.name', 'John Doe')
            ->has('post.author.avatar')
        );
    })->group('public', 'blog-post', 'metadata');

    it('displays comments count', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page->has('post.comments', 3));
    })->group('public', 'blog-post', 'metadata');

    it('displays only top-level comments', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page
            ->has('post.comments', 1)
            ->has('post.comments.0.replies', 1)
        );
    })->group('public', 'blog-post', 'metadata');

    it('displays likes count', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page->where('post.likes_count', 5));
    })->group('public', 'blog-post', 'metadata');

    it('shows user_has_liked as false for guests', function () {
        $author = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog.show', $post->slug));
        
        expect($response)->assertInertia(fn ($page) => $page->where('post.user_has_liked', false));
    })->group('public', 'guest', 'blog-post', 'metadata');

    it('shows user_has_liked as true when user liked post', function () {
        $user = createTestMember();
        $author = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $post->likes()->create(['user_id' => $user->id]);
        
        $response = $this->actingAs($user)->get(route('blog.show', $post->slug));
        
        expect($response)->assertInertia(fn ($page) => $page->where('post.user_has_liked', true));
    })->group('public', 'authenticated', 'blog-post', 'metadata');

    it('shows user_has_liked as false when user has not liked post', function () {
        $user = createTestMember();
        $author = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->actingAs($user)->get(route('blog.show', $post->slug));
        
        expect($response)->assertInertia(fn ($page) => $page->where('post.user_has_liked', false));
    })->group('public', 'authenticated', 'blog-post', 'metadata');

    it('shows is_following_author as false for guests', function () {
        $author = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog.show', $post->slug));
        
        expect($response)->assertInertia(fn ($page) => $page->where('post.is_following_author', false));
    })->group('public', 'guest', 'blog-post', 'metadata');

    it('shows is_following_author as true when user follows author', function () {
        $user = createTestMember();
        $author = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $user->following()->attach($author->id);
        
        $response = $this->actingAs($user)->get(route('blog.show', $post->slug));
        
        expect($response)->assertInertia(fn ($page) => $page->where('post.is_following_author', true));
    })->group('public', 'authenticated', 'blog-post', 'metadata');

    it('shows is_following_author as false when user does not follow author', function () {
        $user = createTestMember();
        $author = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->actingAs($user)->get(route('blog.show', $post->slug));
        
        expect($response)->assertInertia(fn ($page) => $page->where('post.is_following_author', false));
    })->group('public', 'authenticated', 'blog-post', 'metadata');
});

describe('Edge Cases', function () {
    it('handles posts with no tags', function () {
        $author = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'tags' => [],
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog.show', $post->slug));
        
        expect($response)->assertInertia(fn ($page) => $page->where('post.tags', []));
    })->group('public', 'blog-post', 'edge-cases');

    it('handles posts with no comments', function () {
        $author = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog.show', $post->slug));
        
        expect($response)->assertInertia(fn ($page) => $page->has('post.comments', 0));
    })->group('public', 'blog-post', 'edge-cases');

    it('handles posts with no likes', function () {
        $author = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog.show', $post->slug));
        
        expect($response)->assertInertia(fn ($page) => $page->where('post.likes_count', 0));
    })->group('public', 'blog-post', 'edge-cases');

    it('handles empty blog with no posts on landing page', function () {
        $response = $this->get(route('home'));
        
        expect($response)->toBeSuccessfulInertiaResponse('Welcome')
            ->and($response)->assertInertia(fn ($page) => $page
                ->has('posts', 0)
                ->has('featured_posts', 0)
                ->has('availableTags', 0)
                ->has('availableAuthors', 0)
            );
    })->group('public', 'home', 'edge-cases');

    it('handles empty blog with no posts on blog list', function () {
        $response = $this->get(route('blog'));
        
        expect($response)->toBeSuccessfulInertiaResponse('BlogSimple')
            ->and($response)->assertInertia(fn ($page) => $page
                ->has('posts', 0)
                ->has('featured_posts', 0)
            );
    })->group('public', 'blog-list', 'edge-cases');

    it('returns empty list when search has no results', function () {
        $author = createTestAdmin();
        BlogPost::factory()->create([
            'user_id' => $author->id,
            'title' => 'Laravel Guide',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog', ['search' => 'NonExistentTerm']));
        
        expect($response)->assertInertia(fn ($page) => $page
            ->has('posts', 0)
            ->has('featured_posts', 0)
        );
    })->group('public', 'search', 'edge-cases');

    it('handles long content correctly', function () {
        $author = createTestAdmin();
        $longContent = str_repeat('Lorem ipsum dolor sit amet. ', 500);
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'content' => $longContent,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog.show', $post->slug));
        
        expect($response)->toBeSuccessfulInertiaResponse('BlogPost')
            ->and($response)->assertInertia(fn ($page) => $page->where('post.content', $longContent));
    })->group('public', 'blog-post', 'edge-cases');

    it('handles special characters in content', function () {
        $author = createTestAdmin();
        $specialContent = '<script>alert("XSS")</script> & special chars: é à ü ñ';
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'content' => $specialContent,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog.show', $post->slug));
        
        expect($response)->toBeSuccessfulInertiaResponse('BlogPost')
            ->and($response)->assertInertia(fn ($page) => $page->where('post.content', $specialContent));
    })->group('public', 'blog-post', 'edge-cases');

    it('displays mixed featured and regular posts correctly on landing page', function () {
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
        
        expect($response)->assertInertia(fn ($page) => $page
            ->has('featured_posts', 2) // Limited to 2
            ->has('posts', 6) // Limited to 6
        );
    })->group('public', 'home', 'featured', 'edge-cases');

    it('performs case insensitive search correctly', function () {
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
        
        expect($response1)->assertInertia(fn ($page) => $page->has('posts', 1))
            ->and($response2)->assertInertia(fn ($page) => $page->has('posts', 1))
            ->and($response3)->assertInertia(fn ($page) => $page->has('posts', 1));
    })->group('public', 'search', 'edge-cases');

    it('supports partial search matches correctly', function () {
        $author = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $author->id,
            'title' => 'Understanding Laravel Framework',
            'is_featured' => false,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog', ['search' => 'Laravel']));
        
        expect($response)->assertInertia(fn ($page) => $page->has('posts', 1));
    })->group('public', 'search', 'edge-cases');

    it('shows admin author posts on public pages', function () {
        $admin = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $admin->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog.show', $post->slug));
        
        expect($response)->toBeSuccessfulInertiaResponse('BlogPost')
            ->and($response)->assertInertia(fn ($page) => $page
                ->where('post.id', $post->id)
                ->where('post.author.id', $admin->id)
            );
    })->group('public', 'blog-post', 'visibility');

    it('shows master admin author posts on public pages', function () {
        $masterAdmin = createTestMasterAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $masterAdmin->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        
        $response = $this->get(route('blog.show', $post->slug));
        
        expect($response)->toBeSuccessfulInertiaResponse('BlogPost')
            ->and($response)->assertInertia(fn ($page) => $page->where('post.author.id', $masterAdmin->id));
    })->group('public', 'blog-post', 'visibility');
});
