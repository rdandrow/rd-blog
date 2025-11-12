<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    $posts = App\Models\BlogPost::with('author')
        ->published()
        ->orderBy('published_at', 'desc')
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

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'posts' => $recent_posts,
        'featured_posts' => $featured_posts,
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('blog', function () {
    $posts = App\Models\BlogPost::with('author')
        ->published()
        ->orderBy('published_at', 'desc')
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

    return Inertia::render('BlogSimple', [
        'posts' => $regular_posts,
        'featured_posts' => $featured_posts,
    ]);
})->name('blog');

// Blog Post Management Routes (Admin)
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('blog-posts', App\Http\Controllers\BlogPostController::class);
});

// Public blog post route (if needed for individual post viewing)
Route::get('blog/{slug}', [App\Http\Controllers\BlogPostController::class, 'show'])->name('blog.show');

require __DIR__.'/settings.php';
