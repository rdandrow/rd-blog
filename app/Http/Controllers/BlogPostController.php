<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BlogPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
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
    public function create()
    {
        return Inertia::render('Admin/BlogPosts/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'required|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'nullable|string',
            'featured_image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        // Handle file upload - file takes priority over URL
        if ($request->hasFile('featured_image_file')) {
            $file = $request->file('featured_image_file');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $extension;
            $path = $file->storeAs('blog-images', $filename, 'public');
            $validated['featured_image'] = '/storage/' . $path;
        } elseif (empty($validated['featured_image'])) {
            // If no file and no URL, set to null
            $validated['featured_image'] = null;
        }

        // Generate unique slug
        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;
        
        while (BlogPost::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $post = BlogPost::create([
            ...$validated,
            'slug' => $slug,
            'user_id' => Auth::id(),
            'published_at' => $validated['is_published'] ? ($validated['published_at'] ?? now()) : null,
        ]);

        return redirect()
            ->route('admin.blog-posts.show', $post)
            ->with('success', 'Blog post created successfully!');
    }

    /**
     * Display the specified resource (Admin).
     */
    public function show(BlogPost $blogPost)
    {
        $blogPost->load('author');

        return Inertia::render('Admin/BlogPosts/Show', [
            'post' => $blogPost
        ]);
    }

    /**
     * Display a blog post by slug (Public).
     */
    public function showBySlug(string $slug)
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
    public function edit(BlogPost $blogPost)
    {
        $this->authorize('update', $blogPost);
        
        return Inertia::render('Admin/BlogPosts/Edit', [
            'post' => $blogPost
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BlogPost $blogPost)
    {
        $this->authorize('update', $blogPost);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'required|string|max:500',
            'content' => 'required|string',
            'featured_image' => 'nullable|string',
            'featured_image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
        ]);

        // Handle file upload - file takes priority over URL
        if ($request->hasFile('featured_image_file')) {
            // Delete old image if it exists and is stored locally
            if ($blogPost->featured_image && str_starts_with($blogPost->featured_image, '/storage/')) {
                $oldImagePath = str_replace('/storage/', '', $blogPost->featured_image);
                \Storage::disk('public')->delete($oldImagePath);
            }
            
            $file = $request->file('featured_image_file');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $extension;
            $path = $file->storeAs('blog-images', $filename, 'public');
            $validated['featured_image'] = '/storage/' . $path;
        } elseif (empty($validated['featured_image'])) {
            // If no file and no URL, set to null
            $validated['featured_image'] = null;
        }

        // Handle slug update if title changed
        if ($validated['title'] !== $blogPost->title) {
            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $counter = 1;
            
            while (BlogPost::where('slug', $slug)->where('id', '!=', $blogPost->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $validated['slug'] = $slug;
        }

        $blogPost->update([
            ...$validated,
            'published_at' => $validated['is_published'] ? ($validated['published_at'] ?? $blogPost->published_at ?? now()) : null,
        ]);

        return redirect()
            ->route('admin.blog-posts.show', $blogPost)
            ->with('success', 'Blog post updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BlogPost $blogPost)
    {
        $this->authorize('delete', $blogPost);
        
        $blogPost->delete();

        return redirect()
            ->route('admin.blog-posts.index')
            ->with('success', 'Blog post deleted successfully!');
    }
}
