<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Store a newly created comment.
     */
    public function store(Request $request, string $slug): RedirectResponse
    {
        $blogPost = BlogPost::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ]);

        // If parent_id is provided, verify it belongs to the same blog post
        if (isset($validated['parent_id'])) {
            $parentComment = Comment::findOrFail($validated['parent_id']);
            if ($parentComment->blog_post_id !== $blogPost->id) {
                return back()->withErrors(['parent_id' => 'This reply does not belong to the current post.']);
            }
        }

        Comment::create([
            'blog_post_id' => $blogPost->id,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => $validated['content'],
        ]);

        return back()->with('success', isset($validated['parent_id']) ? 'Reply added successfully!' : 'Comment added successfully!');
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(Comment $comment): RedirectResponse
    {
        // Only allow user to delete their own comments or admins can delete any
        if ($comment->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', 'Comment deleted successfully!');
    }
}
