<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use App\Services\BlogPostService;
use App\Services\BlogPostViewTrackingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class PublicBlogController extends Controller
{
    public function __construct(
        private readonly BlogPostService $blogPostService,
        private readonly BlogPostViewTrackingService $blogPostViewTrackingService,
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
        $user = $request->user();
        
        $post = BlogPost::withCount('likes')
            ->with([
                'author',
                'comments' => function ($query) {
                    $query->whereNull('parent_id')->with(['user', 'replies.user']);
                },
            ])
            ->when($user, function ($query) use ($user) {
                $query->withExists([
                    'likes as user_has_liked' => function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    }
                ]);
            })
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $isFollowingAuthor = false;
        
        if ($user && $post->author) {
            $isFollowingAuthor = $user->following()->where('following_id', $post->author->id)->exists();
        }

        $this->blogPostViewTrackingService->trackView($post, $request);

        return Inertia::render('BlogPost', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'featured_image' => $post->featured_image,
                'author' => $post->author ? [
                    'id' => $post->author->id,
                    'name' => $post->author->name,
                    'avatar' => $post->author->avatar ?? null,
                ] : null,
                'published_at' => $post->published_at?->toISOString(),
                'reading_time' => $post->reading_time,
                'tags' => $post->tags ?? [],
                'is_featured' => $post->is_featured,
                'likes_count' => $post->likes_count,
                'user_has_liked' => $user ? ($post->user_has_liked ?? false) : false,
                'is_following_author' => $isFollowingAuthor,
                'comments' => $post->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'content' => $comment->content,
                        'created_at' => $comment->created_at->toISOString(),
                        'user' => [
                            'id' => $comment->user->id,
                            'name' => $comment->user->name,
                        ],
                        'replies' => $comment->replies->map(function ($reply) {
                            return [
                                'id' => $reply->id,
                                'content' => $reply->content,
                                'created_at' => $reply->created_at->toISOString(),
                                'user' => [
                                    'id' => $reply->user->id,
                                    'name' => $reply->user->name,
                                ],
                            ];
                        }),
                    ];
                }),
            ],
        ]);
    }
}
