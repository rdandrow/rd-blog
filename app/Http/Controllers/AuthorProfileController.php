<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthorProfileController extends Controller
{
    /**
     * Display the author's profile page.
     */
    public function show(string $id, Request $request): Response
    {
        $author = User::with(['blogPosts' => function ($query) {
            $query->published()->latest()->take(10);
        }])
        ->findOrFail($id);

        // Only show profile for admin users (authors)
        if (!$author->isAdmin()) {
            abort(404);
        }

        $currentUser = $request->user();
        $isFollowing = false;
        
        if ($currentUser) {
            $isFollowing = $currentUser->following()->where('following_id', $author->id)->exists();
        }

        return Inertia::render('AuthorProfile', [
            'author' => [
                'id' => $author->id,
                'name' => $author->name,
                'email' => $author->email,
                'bio' => $author->bio,
                'website' => $author->website,
                'followers_count' => $author->followers()->count(),
                'following_count' => $author->following()->count(),
                'posts_count' => $author->blogPosts()->published()->count(),
            ],
            'posts' => $author->blogPosts->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'published_at' => $post->published_at?->toISOString(),
                    'reading_time' => $post->reading_time,
                    'tags' => $post->tags ?? [],
                ];
            }),
            'is_following' => $isFollowing,
        ]);
    }
}
