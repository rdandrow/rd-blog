<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    /**
     * Display the invitation acceptance form.
     */
    public function show(string $token): Response|RedirectResponse
    {
        $user = User::where('invitation_token', $token)
            ->whereNotNull('invitation_sent_at')
            ->whereNull('invitation_accepted_at')
            ->first();

        // Check if invitation exists
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired invitation link.');
        }

        // Check if invitation has expired (48 hours)
        if ($user->hasInvitationExpired()) {
            return redirect()->route('login')
                ->with('error', 'This invitation has expired. Please contact an administrator.');
        }

        return Inertia::render('auth/AcceptInvitation', [
            'token' => $token,
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }

    /**
     * Process the invitation acceptance and set password.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::min(14)],
        ]);

        $user = User::where('invitation_token', $token)
            ->whereNotNull('invitation_sent_at')
            ->whereNull('invitation_accepted_at')
            ->first();

        // Check if invitation exists
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired invitation link.');
        }

        // Check if invitation has expired (48 hours)
        if ($user->hasInvitationExpired()) {
            return redirect()->route('login')
                ->with('error', 'This invitation has expired. Please contact an administrator.');
        }

        // Update user password and mark invitation as accepted
        $user->update([
            'password' => Hash::make($request->password),
            'invitation_accepted_at' => now(),
            'invitation_token' => null, // Clear the token
            'email_verified_at' => now(), // Mark email as verified
        ]);

        // Log security event
        Log::info('User invitation accepted', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'invitation_sent_at' => $user->invitation_sent_at?->toISOString(),
            'ip_address' => $request->ip(),
        ]);

        // Redirect to login with pre-filled email
        return redirect()->route('login')
            ->with('success', 'Your password has been set successfully. Please log in.')
            ->with('email', $user->email);
    }
}
