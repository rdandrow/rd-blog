<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserFollowController extends Controller
{
    public function __construct(
        private DashboardMetricsService $dashboardMetricsService,
    ) {
    }

    /**
     * Toggle follow on a user.
     */
    public function toggle(Request $request, string $userId): RedirectResponse
    {
        $userToFollow = User::findOrFail($userId);
        $currentUser = $request->user();

        // Prevent users from following themselves
        if ($currentUser->id === $userToFollow->id) {
            return back()->with('error', 'You cannot follow yourself');
        }

        // Only allow following admin users (authors)
        if (!$userToFollow->isAdmin()) {
            return back()->with('error', 'You can only follow authors');
        }

        $isFollowing = $currentUser->following()->where('following_id', $userToFollow->id)->exists();

        if ($isFollowing) {
            $currentUser->following()->detach($userToFollow->id);
            $message = 'Unfollowed successfully';
        } else {
            $currentUser->following()->attach($userToFollow->id);
            $message = 'Following successfully';
        }

        $this->dashboardMetricsService->invalidateDashboardCache();

        return back()->with('success', $message);
    }
}
