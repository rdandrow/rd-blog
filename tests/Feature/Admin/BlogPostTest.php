<?php

/**
 * Blog Post Management Test Suite
 *
 * Tests all CRUD operations for blog posts including creation, editing, publishing,
 * and deletion. Verifies admin and master admin access controls.
 */

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

afterEach(function () {
    Storage::fake(); // Clean up storage between tests
});

describe('Blog Post Listing', function () {
    it('allows admin users to view their published blog posts', function () {
        $admin = createTestAdmin();
        $otherAdmin = createTestAdmin(['email' => 'other@test.com']);

        BlogPost::factory()->published()->count(3)->create(['user_id' => $admin->id]);
        BlogPost::factory()->published()->count(2)->create(['user_id' => $otherAdmin->id]);

        $response = authenticatedGet($admin, route('admin.blog-posts.index'));

        expect($response)->toBeSuccessfulInertiaResponse('Admin/BlogPosts/Index')
            ->and($response)->assertInertia(fn ($page) => $page->has('posts.data', 3));
    })->group('blog-posts', 'listing', 'authorized');

    it('denies member users from accessing blog post index', function () {
        $member = createTestMember();

        $response = authenticatedGet($member, route('admin.blog-posts.index'));

        expect($response)->toBeForbidden();
    })->group('blog-posts', 'listing', 'unauthorized');

    it('redirects unauthenticated users from blog post index', function () {
        $response = $this->get(route('admin.blog-posts.index'));

        expect($response)->toRedirectToLogin();
    })->group('blog-posts', 'listing', 'guest');
});

describe('Draft Posts Management', function () {
    it('allows admin users to view their draft posts', function () {
        $admin = createTestAdmin();

        BlogPost::factory()->draft()->count(2)->create(['user_id' => $admin->id]);
        BlogPost::factory()->published()->create(['user_id' => $admin->id]);

        $response = authenticatedGet($admin, route('admin.blog-posts.drafts'));

        expect($response)->toBeSuccessfulInertiaResponse('Admin/BlogPosts/Drafts')
            ->and($response)->assertInertia(fn ($page) => $page->has('posts.data', 2));
    })->group('blog-posts', 'drafts', 'authorized');

    it('excludes draft posts from published index', function () {
        $admin = createTestAdmin();

        BlogPost::factory()->draft()->count(3)->create(['user_id' => $admin->id]);
        BlogPost::factory()->published()->count(2)->create(['user_id' => $admin->id]);

        $response = authenticatedGet($admin, route('admin.blog-posts.index'));

        $response->assertInertia(fn ($page) => $page->has('posts.data', 2));
    })->group('blog-posts', 'drafts', 'filtering');
});

describe('Blog Post Creation', function () {
    it('allows admin users to view create blog post form', function () {
        $admin = createTestAdmin();

        $response = authenticatedGet($admin, route('admin.blog-posts.create'));

        expect($response)->toBeSuccessfulInertiaResponse('Admin/BlogPosts/Create');
    })->group('blog-posts', 'creation', 'authorized');

    it('allows admin users to create a published blog post', function () {
        $admin = createTestAdmin();

        $postData = [
            'title' => 'Test Blog Post',
            'excerpt' => 'This is a test excerpt',
            'content' => 'This is the test content for the blog post.',
            'tags' => ['Laravel', 'Testing'],
            'is_featured' => true,
            'is_published' => true,
        ];

        $response = authenticatedPost($admin, route('admin.blog-posts.store'), $postData);

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_posts', [
            'title' => 'Test Blog Post',
            'slug' => 'test-blog-post',
            'excerpt' => 'This is a test excerpt',
            'user_id' => $admin->id,
            'is_published' => true,
            'is_featured' => true,
        ]);

        $post = BlogPost::where('title', 'Test Blog Post')->first();
        expect($post->published_at)->not->toBeNull()
            ->and($post->reading_time)->toBeGreaterThan(0);
    })->group('blog-posts', 'creation', 'authorized');

    it('allows admin users to create a draft blog post', function () {
        $admin = createTestAdmin();

        $postData = [
            'title' => 'Draft Blog Post',
            'excerpt' => 'This is a draft excerpt',
            'content' => 'This is the draft content.',
            'is_featured' => false,
            'is_published' => false,
        ];

        $response = authenticatedPost($admin, route('admin.blog-posts.store'), $postData);

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_posts', [
            'title' => 'Draft Blog Post',
            'is_published' => false,
            'published_at' => null,
        ]);
    })->group('blog-posts', 'creation', 'authorized', 'drafts');

    it('generates slug automatically from title', function () {
        $admin = createTestAdmin();

        $postData = [
            'title' => 'My Awesome Blog Post!',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'is_published' => false,
        ];

        authenticatedPost($admin, route('admin.blog-posts.store'), $postData);

        $this->assertDatabaseHas('blog_posts', [
            'title' => 'My Awesome Blog Post!',
            'slug' => 'my-awesome-blog-post',
        ]);
    })->group('blog-posts', 'creation', 'slug-generation');

    it('generates unique slugs for duplicate titles', function () {
        $admin = createTestAdmin();

        $postData = [
            'title' => 'Duplicate Title',
            'excerpt' => 'First post',
            'content' => 'First content',
            'is_published' => true,
        ];

        authenticatedPost($admin, route('admin.blog-posts.store'), $postData);

        $postData['excerpt'] = 'Second post';
        $postData['content'] = 'Second content';

        authenticatedPost($admin, route('admin.blog-posts.store'), $postData);

        $posts = BlogPost::where('title', 'Duplicate Title')->get();
        expect($posts)->toHaveCount(2)
            ->and($posts[0]->slug)->not->toBe($posts[1]->slug)
            ->and($posts[1]->slug)->toContain('duplicate-title-');
    })->group('blog-posts', 'creation', 'slug-generation', 'edge-cases');

    it('denies member users from creating blog posts', function () {
        $member = createTestMember();

        $postData = [
            'title' => 'Test Post',
            'excerpt' => 'Test excerpt',
            'content' => 'Test content',
            'is_published' => false,
        ];

        $response = authenticatedPost($member, route('admin.blog-posts.store'), $postData);

        expect($response)->toBeForbidden();
    })->group('blog-posts', 'creation', 'unauthorized');
});

describe('Blog Post Creation Validation', function () {
    beforeEach(function () {
        $this->admin = createTestAdmin();
    });

    it('requires required fields', function (string $missingField, array $validData) {
        unset($validData[$missingField]);

        $response = authenticatedPost($this->admin, route('admin.blog-posts.store'), $validData);

        expect($response)->toHaveValidationError($missingField);
    })->with([
        'title is required' => [
            'missingField' => 'title',
            'validData' => [
                'title' => 'Test Post',
                'excerpt' => 'Test excerpt',
                'content' => 'Test content',
                'is_published' => false,
            ],
        ],
        'excerpt is required' => [
            'missingField' => 'excerpt',
            'validData' => [
                'title' => 'Test Post',
                'excerpt' => 'Test excerpt',
                'content' => 'Test content',
                'is_published' => false,
            ],
        ],
        'content is required' => [
            'missingField' => 'content',
            'validData' => [
                'title' => 'Test Post',
                'excerpt' => 'Test excerpt',
                'content' => 'Test content',
                'is_published' => false,
            ],
        ],
    ])->group('blog-posts', 'creation', 'validation');
});

describe('Blog Post Editing', function () {
    it('allows admin users to view edit form for their own posts', function () {
        $admin = createTestAdmin();
        $post = BlogPost::factory()->create(['user_id' => $admin->id]);

        $response = authenticatedGet($admin, route('admin.blog-posts.edit', $post));

        expect($response)->toBeSuccessfulInertiaResponse('Admin/BlogPosts/Edit')
            ->and($response)->assertInertia(fn ($page) => $page->has('post'));
    })->group('blog-posts', 'editing', 'authorized');

    it('denies admin users from editing other admins posts', function () {
        $admin1 = createTestAdmin();
        $admin2 = createTestAdmin(['email' => 'admin2@test.com']);
        $post = BlogPost::factory()->create(['user_id' => $admin2->id]);

        $response = authenticatedGet($admin1, route('admin.blog-posts.edit', $post));

        expect($response)->toBeForbidden();
    })->group('blog-posts', 'editing', 'unauthorized');

    it('allows admin users to update their own blog post', function () {
        $admin = createTestAdmin();
        $post = BlogPost::factory()->create(['user_id' => $admin->id]);

        $updateData = [
            'title' => 'Updated Title',
            'excerpt' => 'Updated excerpt',
            'content' => 'Updated content',
            'is_featured' => true,
            'is_published' => true,
        ];

        $response = authenticatedPut($admin, route('admin.blog-posts.update', $post), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
            'is_featured' => true,
            'is_published' => true,
        ]);
    })->group('blog-posts', 'editing', 'authorized');

    it('allows admin users to publish a draft post', function () {
        $admin = createTestAdmin();
        $post = BlogPost::factory()->draft()->create(['user_id' => $admin->id]);

        expect($post->is_published)->toBeFalse()
            ->and($post->published_at)->toBeNull();

        $updateData = [
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'is_published' => true,
        ];

        authenticatedPut($admin, route('admin.blog-posts.update', $post), $updateData);

        $post->refresh();
        expect($post->is_published)->toBeTrue()
            ->and($post->published_at)->not->toBeNull();
    })->group('blog-posts', 'editing', 'publishing');

    it('allows admin users to unpublish a published post', function () {
        $admin = createTestAdmin();
        $post = BlogPost::factory()->published()->create(['user_id' => $admin->id]);

        expect($post->is_published)->toBeTrue();

        $updateData = [
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'is_published' => false,
        ];

        $this->actingAs($admin)->put(route('admin.blog-posts.update', $post), $updateData);

        $post->refresh();
        expect($post->is_published)->toBeFalse()
            ->and($post->published_at)->toBeNull();
    })->group('blog-posts', 'editing', 'publishing');

    it('denies admin users from updating other admins posts', function () {
        $admin1 = createTestAdmin();
        $admin2 = createTestAdmin(['email' => 'admin2@test.com']);
        $post = BlogPost::factory()->create(['user_id' => $admin2->id]);

        $updateData = [
            'title' => 'Hacked Title',
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'is_published' => $post->is_published,
        ];

        $response = $this->actingAs($admin1)->put(route('admin.blog-posts.update', $post), $updateData);

        expect($response)->toBeForbidden();
        $this->assertDatabaseMissing('blog_posts', [
            'id' => $post->id,
            'title' => 'Hacked Title',
        ]);
    })->group('blog-posts', 'editing', 'unauthorized');
});

describe('Blog Post Viewing', function () {
    it('allows admin users to view their own blog post details', function () {
        $admin = createTestAdmin();
        $post = BlogPost::factory()->create(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->get(route('admin.blog-posts.show', $post));

        expect($response)->toBeSuccessfulInertiaResponse('Admin/BlogPosts/Show')
            ->and($response)->assertInertia(fn ($page) => $page->has('post'));
    })->group('blog-posts', 'viewing', 'authorized');
});

describe('Blog Post Deletion', function () {
    it('allows admin users to delete their own blog post', function () {
        $admin = createTestAdmin();
        $post = BlogPost::factory()->create(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->delete(route('admin.blog-posts.destroy', $post));

        $response->assertRedirect(route('admin.blog-posts.index'));
        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
    })->group('blog-posts', 'deletion', 'authorized');

    it('denies admin users from deleting other admins posts', function () {
        $admin1 = createTestAdmin();
        $admin2 = createTestAdmin(['email' => 'admin2@test.com']);
        $post = BlogPost::factory()->create(['user_id' => $admin2->id]);

        $response = $this->actingAs($admin1)->delete(route('admin.blog-posts.destroy', $post));

        expect($response)->toBeForbidden();
        $this->assertDatabaseHas('blog_posts', ['id' => $post->id]);
    })->group('blog-posts', 'deletion', 'unauthorized');

    it('denies member users from deleting blog posts', function () {
        $member = createTestMember();
        $admin = createTestAdmin();
        $post = BlogPost::factory()->create(['user_id' => $admin->id]);

        $response = $this->actingAs($member)->delete(route('admin.blog-posts.destroy', $post));

        expect($response)->toBeForbidden();
        $this->assertDatabaseHas('blog_posts', ['id' => $post->id]);
    })->group('blog-posts', 'deletion', 'unauthorized');
});

describe('Featured Posts', function () {
    it('allows marking blog post as featured', function () {
        $admin = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $admin->id,
            'is_featured' => false,
        ]);

        $updateData = [
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'is_featured' => true,
            'is_published' => $post->is_published,
        ];

        $this->actingAs($admin)->put(route('admin.blog-posts.update', $post), $updateData);

        expect($post->fresh()->is_featured)->toBeTrue();
    })->group('blog-posts', 'featured');
});

describe('Blog Post Tags', function () {
    it('allows adding tags to blog post', function () {
        $admin = createTestAdmin();

        $postData = [
            'title' => 'Tagged Post',
            'excerpt' => 'Post with tags',
            'content' => 'Content with tags',
            'tags' => ['PHP', 'Laravel', 'Testing'],
            'is_published' => true,
        ];

        $this->actingAs($admin)->post(route('admin.blog-posts.store'), $postData);

        $post = BlogPost::where('title', 'Tagged Post')->first();
        expect($post->tags)->toBe(['PHP', 'Laravel', 'Testing']);
    })->group('blog-posts', 'tags');

    it('allows updating tags on existing post', function () {
        $admin = createTestAdmin();
        $post = BlogPost::factory()->create([
            'user_id' => $admin->id,
            'tags' => ['Old', 'Tags'],
        ]);

        $updateData = [
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'tags' => ['New', 'Updated', 'Tags'],
            'is_published' => $post->is_published,
        ];

        $this->actingAs($admin)->put(route('admin.blog-posts.update', $post), $updateData);

        expect($post->fresh()->tags)->toBe(['New', 'Updated', 'Tags']);
    })->group('blog-posts', 'tags');
});

describe('Reading Time Calculation', function () {
    it('calculates reading time automatically on creation', function () {
        $admin = createTestAdmin();

        $postData = [
            'title' => 'Test Post',
            'excerpt' => 'Test excerpt',
            'content' => str_repeat('word ', 500), // ~500 words
            'is_published' => true,
        ];

        $this->actingAs($admin)->post(route('admin.blog-posts.store'), $postData);

        $post = BlogPost::where('title', 'Test Post')->first();
        expect($post->reading_time)->toBeGreaterThan(0)
            ->and($post->reading_time)->toBeLessThanOrEqual(5); // ~500 words should be 2-3 min
    })->group('blog-posts', 'reading-time');
});
