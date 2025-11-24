<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Http\Requests\StoreBlogPostRequest;
use App\Http\Requests\UpdateBlogPostRequest;
use App\Services\BlogImageService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class BlogPostController extends Controller
{
    public function __construct(
        private readonly BlogImageService $imageService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $posts = BlogPost::with('author')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Admin/BlogPosts/Index', [
            'posts' => $posts
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/BlogPosts/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Handle featured image upload or URL using service
        $validated['featured_image'] = $this->imageService->handleCreate(
            $request->file('featured_image_file'),
            $validated['featured_image'] ?? null
        );

        $post = BlogPost::create([
            ...$validated,
            'user_id' => Auth::id(),
            'published_at' => $validated['is_published'] 
                ? ($validated['published_at'] ?? now()) 
                : null,
        ]);

        return redirect()
            ->route('admin.blog-posts.show', $post)
            ->with('success', 'Blog post created successfully!');
    }

    /**
     * Display the specified resource (Admin).
     */
    public function show(BlogPost $blogPost): Response
    {
        $blogPost->load('author');

        return Inertia::render('Admin/BlogPosts/Show', [
            'post' => $blogPost
        ]);
    }

    /**
     * Display a blog post by slug (Public).
     */
    public function showBySlug(string $slug): Response
    {
        $post = BlogPost::with('author')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
            
        return Inertia::render('BlogPost', [
            'post' => $post
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BlogPost $blogPost): Response
    {
        $this->authorize('update', $blogPost);
        
        return Inertia::render('Admin/BlogPosts/Edit', [
            'post' => $blogPost
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBlogPostRequest $request, BlogPost $blogPost): RedirectResponse
    {
        $validated = $request->validated();

        // Handle image updates
        $validated['featured_image'] = $this->imageService->handleUpdate(
            newFile: $request->file('featured_image_file'),
            newUrl: $validated['featured_image'] ?? null,
            removeCurrentImage: $validated['remove_current_image'] ?? false,
            currentImagePath: $blogPost->featured_image
        );
        
        // Remove the flag from data to be saved
        unset($validated['remove_current_image']);

        $blogPost->update([
            ...$validated,
            'published_at' => $validated['is_published'] 
                ? ($validated['published_at'] ?? $blogPost->published_at ?? now()) 
                : null,
        ]);

        return redirect()
            ->route('admin.blog-posts.show', $blogPost)
            ->with('success', 'Blog post updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        $this->authorize('delete', $blogPost);
        
        // Delete associated image if it exists
        $this->imageService->delete($blogPost->featured_image);
        
        $blogPost->delete();

        return redirect()
            ->route('admin.blog-posts.index')
            ->with('success', 'Blog post deleted successfully!');
    }
}
