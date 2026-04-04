<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\BlogPostLike;
use App\Services\DashboardMetricsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BlogPostLikeController extends Controller
{
    public function __construct(
        private DashboardMetricsService $dashboardMetricsService,
    ) {
    }

    /**
     * Toggle like on a blog post.
     */
    public function toggle(Request $request, string $slug): RedirectResponse
    {
        $blogPost = BlogPost::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        $existingLike = BlogPostLike::where('blog_post_id', $blogPost->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $message = 'Like removed';
        } else {
            BlogPostLike::create([
                'blog_post_id' => $blogPost->id,
                'user_id' => $user->id,
            ]);
            $message = 'Post liked';
        }

        $this->dashboardMetricsService->invalidateDashboardCache();

        return back()->with('success', $message);
    }
}
