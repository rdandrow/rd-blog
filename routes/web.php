<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('blog', function () {
    // Mock data for demonstration - in production, this would come from a database
    $posts = [
        [
            'id' => 1,
            'title' => 'Getting Started with Vue.js 3 Composition API',
            'excerpt' => 'Learn the fundamentals of Vue.js 3 Composition API and how it improves component organization.',
            'content' => '',
            'slug' => 'getting-started-vue3-composition-api',
            'featured_image' => null,
            'author' => [
                'name' => 'John Doe',
                'avatar' => null
            ],
            'published_at' => '2024-01-15T10:00:00Z',
            'reading_time' => 8,
            'tags' => ['Vue.js', 'JavaScript', 'Frontend'],
            'is_featured' => true
        ],
        [
            'id' => 2,
            'title' => 'Modern Laravel Development with Inertia.js',
            'excerpt' => 'Discover how Inertia.js bridges the gap between server-side and client-side frameworks.',
            'content' => '',
            'slug' => 'modern-laravel-development-inertia',
            'featured_image' => null,
            'author' => [
                'name' => 'Jane Smith',
                'avatar' => null
            ],
            'published_at' => '2024-01-10T14:30:00Z',
            'reading_time' => 12,
            'tags' => ['Laravel', 'Inertia.js', 'PHP'],
            'is_featured' => true
        ],
        [
            'id' => 3,
            'title' => 'Building Responsive Layouts with Tailwind CSS',
            'excerpt' => 'Master the art of creating beautiful, responsive layouts using Tailwind CSS utility classes.',
            'content' => '',
            'slug' => 'responsive-layouts-tailwind-css',
            'featured_image' => null,
            'author' => [
                'name' => 'Mike Johnson',
                'avatar' => null
            ],
            'published_at' => '2024-01-08T09:15:00Z',
            'reading_time' => 6,
            'tags' => ['CSS', 'Tailwind', 'Design'],
            'is_featured' => false
        ],
    ];

    $featured_posts = array_filter($posts, fn($post) => $post['is_featured']);

    return Inertia::render('BlogSimple', [
        'posts' => $posts,
        'featured_posts' => $featured_posts,
    ]);
})->name('blog');

require __DIR__.'/settings.php';
