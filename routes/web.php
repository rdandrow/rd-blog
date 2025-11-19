<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function (Illuminate\Http\Request $request) {
    $query = App\Models\BlogPost::with('author')->published();

    // Apply search filter
    if ($search = $request->input('search')) {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('excerpt', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
        });
    }

    // Apply tag filter
    if ($tag = $request->input('tag')) {
        $query->whereJsonContains('tags', $tag);
    }

    // Apply author filter
    if ($authorId = $request->input('author')) {
        $query->where('user_id', $authorId);
    }

    $posts = $query->orderBy('published_at', 'desc')
        ->take(6) // Show latest 6 posts on landing page
        ->get()
        ->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'slug' => $post->slug,
                'featured_image' => $post->featured_image,
                'author' => [
                    'id' => $post->author->id,
                    'name' => $post->author->name,
                ],
                'published_at' => $post->published_at->toISOString(),
                'reading_time' => $post->reading_time,
                'tags' => $post->tags,
                'is_featured' => $post->is_featured
            ];
        });

    $featured_posts = $posts->where('is_featured', true)->take(2);
    $recent_posts = $posts->take(4);

    // Get all available tags and authors for filters
    $allTags = App\Models\BlogPost::published()
        ->get()
        ->pluck('tags')
        ->flatten()
        ->unique()
        ->sort()
        ->values();

    $allAuthors = App\Models\User::whereHas('blogPosts', function ($query) {
        $query->published();
    })->get()->map(function ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    });

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'posts' => $recent_posts,
        'featured_posts' => $featured_posts,
        'filters' => [
            'search' => $request->input('search'),
            'tag' => $request->input('tag'),
            'author' => $request->input('author'),
        ],
        'availableTags' => $allTags,
        'availableAuthors' => $allAuthors,
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'ensure.2fa'])->name('dashboard');

Route::get('blog', function (Illuminate\Http\Request $request) {
    $query = App\Models\BlogPost::with('author')->published();

    // Apply search filter
    if ($search = $request->input('search')) {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('excerpt', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
        });
    }

    // Apply tag filter
    if ($tag = $request->input('tag')) {
        $query->whereJsonContains('tags', $tag);
    }

    // Apply author filter
    if ($authorId = $request->input('author')) {
        $query->where('user_id', $authorId);
    }

    $posts = $query->orderBy('published_at', 'desc')
        ->get()
        ->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'slug' => $post->slug,
                'featured_image' => $post->featured_image,
                'author' => [
                    'id' => $post->author->id,
                    'name' => $post->author->name,
                    'avatar' => null // You can add avatar support later
                ],
                'published_at' => $post->published_at->toISOString(),
                'reading_time' => $post->reading_time,
                'tags' => $post->tags,
                'is_featured' => $post->is_featured
            ];
        });

    $featured_posts = $posts->where('is_featured', true)->values();
    $regular_posts = $posts->where('is_featured', false)->values();

    // Get all available tags and authors for filters
    $allTags = App\Models\BlogPost::published()
        ->get()
        ->pluck('tags')
        ->flatten()
        ->unique()
        ->sort()
        ->values();

    $allAuthors = App\Models\User::whereHas('blogPosts', function ($query) {
        $query->published();
    })->get()->map(function ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    });

    return Inertia::render('BlogSimple', [
        'posts' => $regular_posts,
        'featured_posts' => $featured_posts,
        'filters' => [
            'search' => $request->input('search'),
            'tag' => $request->input('tag'),
            'author' => $request->input('author'),
        ],
        'availableTags' => $allTags,
        'availableAuthors' => $allAuthors,
    ]);
})->name('blog');

// Blog Post Management Routes (Admin)
Route::middleware(['auth', 'verified', 'ensure.2fa'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('blog-posts', App\Http\Controllers\BlogPostController::class);
});

// Public blog post route (individual post viewing by slug)
Route::get('blog/{slug}', [App\Http\Controllers\BlogPostController::class, 'showBySlug'])->name('blog.show');

// Two-factor authentication setup routes (for new users during registration)
Route::middleware(['auth'])->group(function () {
    Route::get('/register/setup-two-factor', function () {
        $registerController = new App\Http\Controllers\Auth\RegisterController();
        return $registerController->setupTwoFactorAuthentication(Auth::user());
    })->name('register.setup-two-factor');
    
    Route::post('/two-factor-challenge/confirm', [App\Http\Controllers\Auth\RegisterController::class, 'confirmTwoFactorAuthentication']);
});

require __DIR__.'/settings.php';
