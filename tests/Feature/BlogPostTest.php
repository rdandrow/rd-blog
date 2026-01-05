<?php

/**
 * Blog Post Management Test Suite
 *
 * Tests all CRUD operations for blog posts including creation, editing, publishing,
 * and deletion. Verifies admin and master admin access controls.
 *
 * Test Categories:
 * - List/Index Tests: Blog post listing and filtering
 * - Create Tests: Post creation with validation and file uploads
 * - Edit Tests: Post editing and update operations
 * - Delete Tests: Post deletion and authorization
 * - Publishing Tests: Draft/publish workflow
 * - Featured Image Tests: Image upload and validation
 * - Slug Generation Tests: Automatic slug creation
 * - Validation Tests: Input validation and error handling
 *
 * Features Tested:
 * - Role-based access (admin/master admin)
 * - Featured image upload with validation
 * - Automatic slug generation
 * - Reading time calculation
 * - Tag management
 * - Draft and published states
 * - Author ownership validation
 */

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

// List/Index Tests
test('admin users can view their published blog posts', function () {
    $admin = createTestAdmin();
    $otherAdmin = createTestAdmin();

    // Create posts for the admin
    BlogPost::factory()->published()->count(3)->create(['user_id' => $admin->id]);
    // Create posts for another admin (shouldn't appear)
    BlogPost::factory()->published()->count(2)->create(['user_id' => $otherAdmin->id]);

    $response = $this->actingAs($admin)->get(route('admin.blog-posts.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/BlogPosts/Index')
        ->has('posts.data', 3)
    );
});

test('member users cannot access blog post index', function () {
    $member = createTestMember();

    $response = $this->actingAs($member)->get(route('admin.blog-posts.index'));

    $response->assertStatus(403);
});

test('guests cannot access blog post index', function () {
    $response = $this->get(route('admin.blog-posts.index'));

    $response->assertRedirect(route('login'));
});

// Drafts Tests
test('admin users can view their draft posts', function () {
    $admin = createTestAdmin();

    BlogPost::factory()->draft()->count(2)->create(['user_id' => $admin->id]);
    BlogPost::factory()->published()->create(['user_id' => $admin->id]); // shouldn't appear

    $response = $this->actingAs($admin)->get(route('admin.blog-posts.drafts'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/BlogPosts/Drafts')
        ->has('posts.data', 2)
    );
});

test('draft posts are not shown in published index', function () {
    $admin = createTestAdmin();

    BlogPost::factory()->draft()->count(3)->create(['user_id' => $admin->id]);
    BlogPost::factory()->published()->count(2)->create(['user_id' => $admin->id]);

    $response = $this->actingAs($admin)->get(route('admin.blog-posts.index'));

    $response->assertInertia(fn ($page) => $page
        ->has('posts.data', 2)
    );
});

// Create Tests
test('admin users can view create blog post form', function () {
    $admin = createTestAdmin();

    $response = $this->actingAs($admin)->get(route('admin.blog-posts.create'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/BlogPosts/Create')
    );
});

test('admin users can create a published blog post', function () {
    $admin = createTestAdmin();

    $postData = [
        'title' => 'Test Blog Post',
        'excerpt' => 'This is a test excerpt',
        'content' => 'This is the test content for the blog post.',
        'tags' => ['Laravel', 'Testing'], // Array of strings
        'is_featured' => true, // Featured posts appear on landing page
        'is_published' => true, // Published immediately with published_at timestamp
    ];

    $response = $this->actingAs($admin)->post(route('admin.blog-posts.store'), $postData);

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
    expect($post->published_at)->not->toBeNull();
    expect($post->reading_time)->toBeGreaterThan(0);
});

test('admin users can create a draft blog post', function () {
    $admin = createTestAdmin();

    $postData = [
        'title' => 'Draft Blog Post',
        'excerpt' => 'This is a draft excerpt',
        'content' => 'This is the draft content.',
        'is_featured' => false,
        'is_published' => false, // Draft - no published_at timestamp
    ];

    $response = $this->actingAs($admin)->post(route('admin.blog-posts.store'), $postData);

    $response->assertRedirect();
    $this->assertDatabaseHas('blog_posts', [
        'title' => 'Draft Blog Post',
        'is_published' => false,
        'published_at' => null,
    ]);
});

test('blog post slug is automatically generated from title', function () {
    $admin = createTestAdmin();

    $postData = [
        'title' => 'My Awesome Blog Post!', // Special chars removed, converted to kebab-case
        'excerpt' => 'Test excerpt',
        'content' => 'Test content',
        'is_published' => false,
    ];

    $this->actingAs($admin)->post(route('admin.blog-posts.store'), $postData);

    $this->assertDatabaseHas('blog_posts', [
        'title' => 'My Awesome Blog Post!',
        'slug' => 'my-awesome-blog-post',
    ]);
});

test('blog post creation requires title', function () {
    $admin = createTestAdmin();

    $postData = [
        'excerpt' => 'Test excerpt',
        'content' => 'Test content',
        'is_published' => false,
    ];

    $response = $this->actingAs($admin)->post(route('admin.blog-posts.store'), $postData);

    $response->assertSessionHasErrors('title');
});

test('blog post creation requires excerpt', function () {
    $admin = createTestAdmin();

    $postData = [
        'title' => 'Test Post',
        'content' => 'Test content',
        'is_published' => false,
    ];

    $response = $this->actingAs($admin)->post(route('admin.blog-posts.store'), $postData);

    $response->assertSessionHasErrors('excerpt');
});

test('blog post creation requires content', function () {
    $admin = createTestAdmin();

    $postData = [
        'title' => 'Test Post',
        'excerpt' => 'Test excerpt',
        'is_published' => false,
    ];

    $response = $this->actingAs($admin)->post(route('admin.blog-posts.store'), $postData);

    $response->assertSessionHasErrors('content');
});

test('member users cannot create blog posts', function () {
    $member = createTestMember();

    $postData = [
        'title' => 'Test Post',
        'excerpt' => 'Test excerpt',
        'content' => 'Test content',
        'is_published' => false,
    ];

    $response = $this->actingAs($member)->post(route('admin.blog-posts.store'), $postData);

    $response->assertStatus(403); // Forbidden - only admin/master_admin can create posts
});

// Edit/Update Tests
test('admin users can view edit form for their own posts', function () {
    $admin = createTestAdmin();
    $post = BlogPost::factory()->create(['user_id' => $admin->id]);

    $response = $this->actingAs($admin)->get(route('admin.blog-posts.edit', $post));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/BlogPosts/Edit')
        ->has('post')
    );
});

test('admin users cannot edit other admins posts', function () {
    $admin1 = createTestAdmin();
    $admin2 = createTestAdmin();
    $post = BlogPost::factory()->create(['user_id' => $admin2->id]);

    $response = $this->actingAs($admin1)->get(route('admin.blog-posts.edit', $post));

    $response->assertStatus(403);
});

test('admin users can update their own blog post', function () {
    $admin = createTestAdmin();
    $post = BlogPost::factory()->create(['user_id' => $admin->id]);

    $updateData = [
        'title' => 'Updated Title',
        'excerpt' => 'Updated excerpt',
        'content' => 'Updated content',
        'is_featured' => true,
        'is_published' => true,
    ];

    $response = $this->actingAs($admin)->put(route('admin.blog-posts.update', $post), $updateData);

    $response->assertRedirect();
    $this->assertDatabaseHas('blog_posts', [
        'id' => $post->id,
        'title' => 'Updated Title',
        'is_featured' => true,
        'is_published' => true,
    ]);
});

test('admin users can publish a draft post', function () {
    $admin = createTestAdmin();
    $post = BlogPost::factory()->draft()->create(['user_id' => $admin->id]);

    expect($post->is_published)->toBeFalse();
    expect($post->published_at)->toBeNull();

    $updateData = [
        'title' => $post->title,
        'excerpt' => $post->excerpt,
        'content' => $post->content,
        'is_published' => true,
    ];

    $this->actingAs($admin)->put(route('admin.blog-posts.update', $post), $updateData);

    $post->refresh();
    expect($post->is_published)->toBeTrue();
    expect($post->published_at)->not->toBeNull();
});

test('admin users can unpublish a published post', function () {
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
    expect($post->is_published)->toBeFalse();
    expect($post->published_at)->toBeNull();
});

test('admin users cannot update other admins posts', function () {
    $admin1 = createTestAdmin();
    $admin2 = createTestAdmin();
    $post = BlogPost::factory()->create(['user_id' => $admin2->id]);

    $updateData = [
        'title' => 'Hacked Title',
        'excerpt' => $post->excerpt,
        'content' => $post->content,
        'is_published' => $post->is_published,
    ];

    $response = $this->actingAs($admin1)->put(route('admin.blog-posts.update', $post), $updateData);

    $response->assertStatus(403);
    $this->assertDatabaseMissing('blog_posts', [
        'id' => $post->id,
        'title' => 'Hacked Title',
    ]);
});

// Show Tests
test('admin users can view their own blog post details', function () {
    $admin = createTestAdmin();
    $post = BlogPost::factory()->create(['user_id' => $admin->id]);

    $response = $this->actingAs($admin)->get(route('admin.blog-posts.show', $post));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/BlogPosts/Show')
        ->has('post')
    );
});

// Delete Tests
test('admin users can delete their own blog post', function () {
    $admin = createTestAdmin();
    $post = BlogPost::factory()->create(['user_id' => $admin->id]);

    $response = $this->actingAs($admin)->delete(route('admin.blog-posts.destroy', $post));

    $response->assertRedirect(route('admin.blog-posts.index'));
    $this->assertDatabaseMissing('blog_posts', [
        'id' => $post->id,
    ]);
});

test('admin users cannot delete other admins posts', function () {
    $admin1 = createTestAdmin();
    $admin2 = createTestAdmin();
    $post = BlogPost::factory()->create(['user_id' => $admin2->id]);

    $response = $this->actingAs($admin1)->delete(route('admin.blog-posts.destroy', $post));

    $response->assertStatus(403);
    $this->assertDatabaseHas('blog_posts', [
        'id' => $post->id,
    ]);
});

test('member users cannot delete blog posts', function () {
    $member = createTestMember();
    $admin = createTestAdmin();
    $post = BlogPost::factory()->create(['user_id' => $admin->id]);

    $response = $this->actingAs($member)->delete(route('admin.blog-posts.destroy', $post));

    $response->assertStatus(403);
    $this->assertDatabaseHas('blog_posts', [
        'id' => $post->id,
    ]);
});

// Featured Posts Tests
test('can mark blog post as featured', function () {
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

    $post->refresh();
    expect($post->is_featured)->toBeTrue();
});

// Tags Tests
test('can add tags to blog post', function () {
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
});

test('can update tags on existing post', function () {
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

    $post->refresh();
    expect($post->tags)->toBe(['New', 'Updated', 'Tags']);
});

// Reading Time Tests
test('reading time is calculated automatically on creation', function () {
    $admin = createTestAdmin();

    $postData = [
        'title' => 'Test Post',
        'excerpt' => 'Test excerpt',
        'content' => str_repeat('word ', 500), // ~500 words
        'is_published' => true,
    ];

    $this->actingAs($admin)->post(route('admin.blog-posts.store'), $postData);

    $post = BlogPost::where('title', 'Test Post')->first();
    expect($post->reading_time)->toBeGreaterThan(0);
    expect($post->reading_time)->toBeLessThanOrEqual(5); // ~500 words should be 2-3 min
});

// Unique Slug Tests
test('slug is unique for duplicate titles', function () {
    $admin = createTestAdmin();

    $postData = [
        'title' => 'Duplicate Title',
        'excerpt' => 'First post',
        'content' => 'First content',
        'is_published' => true,
    ];

    $this->actingAs($admin)->post(route('admin.blog-posts.store'), $postData);

    $postData['excerpt'] = 'Second post';
    $postData['content'] = 'Second content';

    $this->actingAs($admin)->post(route('admin.blog-posts.store'), $postData);

    $posts = BlogPost::where('title', 'Duplicate Title')->get();
    expect($posts)->toHaveCount(2);
    expect($posts[0]->slug)->not->toBe($posts[1]->slug);
    expect($posts[1]->slug)->toContain('duplicate-title-');
});
