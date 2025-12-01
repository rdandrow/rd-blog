<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at->format('M d, Y'),
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
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at->format('M d, Y'),
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
     * Create a new user.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::in(['master_admin', 'admin', 'member'])],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return back()->with('success', 'User created successfully.');
    }
}
