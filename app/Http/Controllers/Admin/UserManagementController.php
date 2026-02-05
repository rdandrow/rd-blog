<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    /**
     * Display a listing of admin users.
     */
    public function indexAdmins(): Response
    {
        $admins = User::whereIn('role', ['admin', 'master_admin'])
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at->format('M d, Y'),
                'invitation_sent_at' => $user->invitation_sent_at?->toISOString(),
                'invitation_accepted_at' => $user->invitation_accepted_at?->toISOString(),
                'invitation_expired' => $user->hasInvitationExpired(),
            ]);

        return Inertia::render('Admin/Users/AdminUsers', [
            'admins' => $admins,
        ]);
    }

    /**
     * Display a listing of member users.
     */
    public function indexMembers(): Response
    {
        $members = User::where('role', 'member')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at->format('M d, Y'),
                'invitation_sent_at' => $user->invitation_sent_at?->toISOString(),
                'invitation_accepted_at' => $user->invitation_accepted_at?->toISOString(),
                'invitation_expired' => $user->hasInvitationExpired(),
            ]);

        return Inertia::render('Admin/Users/MemberUsers', [
            'members' => $members,
        ]);
    }

    /**
     * Update the specified user's role.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => ['required', Rule::in(['master_admin', 'admin', 'member'])],
        ]);

        return DB::transaction(function () use ($request, $user) {
            // Prevent demoting the last master admin
            if ($user->isMasterAdmin() && $request->role !== 'master_admin') {
                $masterAdminCount = User::where('role', 'master_admin')->count();
                if ($masterAdminCount <= 1) {
                    return back()->withErrors([
                        'role' => 'Cannot demote the last master admin.',
                    ]);
                }
            }

            $user->update([
                'role' => $request->role,
            ]);

            return back()->with('success', 'User role updated successfully.');
        });
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Prevent deleting the last master admin
        if ($user->isMasterAdmin()) {
            $masterAdminCount = User::where('role', 'master_admin')->count();
            if ($masterAdminCount <= 1) {
                return back()->withErrors([
                    'error' => 'Cannot delete the last master admin.',
                ]);
            }
        }

        // Prevent users from deleting themselves
        if ($user->id === auth()->id()) {
            return back()->withErrors([
                'error' => 'You cannot delete your own account.',
            ]);
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    /**
     * Create a new user and send invitation email.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'role' => ['required', Rule::in(['master_admin', 'admin', 'member'])],
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // Generate invitation token
                $invitationToken = Str::random(64);
                
                // Create user with temporary password and invitation token
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make(Str::random(32)), // Temporary random password
                    'role' => $request->role,
                    'invitation_token' => $invitationToken,
                    'invitation_sent_at' => now(),
                ]);

                // Generate invitation URL
                $invitationUrl = url("/invitation/accept/{$invitationToken}");
                
                // Send invitation email (queued with retry logic)
                $user->notify(new UserInvitation(
                    invitationUrl: $invitationUrl,
                    inviterName: auth()->user()->name
                ));

                // Log security event
                Log::info('User invitation sent', [
                    'invited_user_id' => $user->id,
                    'invited_user_email' => $user->email,
                    'invited_user_role' => $user->role,
                    'inviter_id' => auth()->id(),
                    'inviter_email' => auth()->user()->email,
                    'ip_address' => request()->ip(),
                ]);

                return back()->with('success', 'User invited successfully. An invitation email has been sent.');
            });
        } catch (\Exception $e) {
            // Log the error
            Log::error('Failed to create user and send invitation', [
                'email' => $request->email,
                'role' => $request->role,
                'error' => $e->getMessage(),
                'inviter_id' => auth()->id(),
            ]);

            return back()->withErrors([
                'error' => 'Failed to create user and send invitation. Please try again or contact support if the issue persists.',
            ]);
        }
    }

    /**
     * Resend invitation to a user.
     */
    public function resendInvitation(User $user): RedirectResponse
    {
        // Check if user has already accepted the invitation
        if ($user->invitation_accepted_at) {
            return back()->withErrors([
                'error' => 'This user has already accepted their invitation.',
            ]);
        }

        try {
            return DB::transaction(function () use ($user) {
                // Generate new invitation token
                $invitationToken = Str::random(64);
                
                // Update user with new invitation token
                $user->update([
                    'invitation_token' => $invitationToken,
                    'invitation_sent_at' => now(),
                ]);

                // Generate invitation URL
                $invitationUrl = url("/invitation/accept/{$invitationToken}");
                
                // Send invitation email (queued with retry logic)
                $user->notify(new UserInvitation(
                    invitationUrl: $invitationUrl,
                    inviterName: auth()->user()->name
                ));

                // Log security event
                Log::info('User invitation resent', [
                    'invited_user_id' => $user->id,
                    'invited_user_email' => $user->email,
                    'invited_user_role' => $user->role,
                    'inviter_id' => auth()->id(),
                    'inviter_email' => auth()->user()->email,
                    'ip_address' => request()->ip(),
                ]);

                return back()->with('success', 'Invitation email has been resent successfully.');
            });
        } catch (\Exception $e) {
            // Log the error
            Log::error('Failed to resend invitation', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage(),
                'inviter_id' => auth()->id(),
            ]);

            return back()->withErrors([
                'error' => 'Failed to resend invitation. Please try again or contact support if the issue persists.',
            ]);
        }
    }
}
