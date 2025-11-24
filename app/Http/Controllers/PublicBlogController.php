<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use App\Services\BlogPostService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class PublicBlogController extends Controller
{
    public function __construct(
        private readonly BlogPostService $blogPostService
    ) {}

    /**
     * Display the landing page with featured and recent posts.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'tag', 'author']);
        
        $data = $this->blogPostService->getLandingPageData($filters);

        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'posts' => BlogPostResource::collection($data['recent_posts'])->collection->map->toArray($request)->all(),
            'featured_posts' => BlogPostResource::collection($data['featured_posts'])->collection->map->toArray($request)->all(),
            'filters' => $filters,
            'availableTags' => $data['available_tags'],
            'availableAuthors' => $data['available_authors'],
        ]);
    }

    /**
     * Display the blog listing page.
     */
    public function list(Request $request): Response
    {
        $filters = $request->only(['search', 'tag', 'author']);
        
        $data = $this->blogPostService->getBlogListingData($filters);

        return Inertia::render('BlogSimple', [
            'canRegister' => Features::enabled(Features::registration()),
            'posts' => BlogPostResource::collection($data['posts'])->collection->map->toArray($request)->all(),
            'featured_posts' => BlogPostResource::collection($data['featured_posts'])->collection->map->toArray($request)->all(),
            'filters' => $filters,
            'availableTags' => $data['available_tags'],
            'availableAuthors' => $data['available_authors'],
        ]);
    }

    /**
     * Display a single blog post.
     */
    public function show(string $slug, Request $request): Response
    {
        $post = BlogPost::with('author')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('BlogPost', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'featured_image' => $post->featured_image,
                'author' => [
                    'id' => $post->author->id,
                    'name' => $post->author->name,
                    'avatar' => $post->author->avatar ?? null,
                ],
                'published_at' => $post->published_at?->toISOString(),
                'reading_time' => $post->reading_time,
                'tags' => $post->tags ?? [],
                'is_featured' => $post->is_featured,
            ],
        ]);
    }
}
