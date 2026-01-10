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
        $currentUser = $request->user();
        
        $author = User::withCount(['followers', 'following'])
            ->with([
                'blogPosts' => function ($query) {
                    $query->published()->latest()->take(10);
                },
            ])
            ->when($currentUser, function ($query) use ($currentUser, $id) {
                $query->withExists([
                    'followers as is_following' => function ($query) use ($currentUser) {
                        $query->where('follower_id', $currentUser->id);
                    }
                ]);
            })
            ->findOrFail($id);

        // Only show profile for admin users (authors)
        if (!$author->isAdmin()) {
            abort(404);
        }

        return Inertia::render('AuthorProfile', [
            'author' => [
                'id' => $author->id,
                'name' => $author->name,
                'bio' => $author->bio,
                'website' => $author->website,
                'followers_count' => $author->followers_count,
                'following_count' => $author->following_count,
                'posts_count' => $author->blogPosts->count(),
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
            'is_following' => $currentUser ? ($author->is_following ?? false) : false,
        ]);
    }
}
