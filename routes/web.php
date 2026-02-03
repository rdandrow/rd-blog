<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\PublicBlogController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\BlogPostLikeController;
use App\Http\Controllers\UserFollowController;
use App\Http\Controllers\AuthorProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Admin\UserManagementController;

Route::get('/', [PublicBlogController::class, 'index'])->name('home');

// Admin Dashboard
Route::get('admin/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'ensure.2fa', 'admin'])->name('dashboard');

Route::get('blog', [PublicBlogController::class, 'list'])->name('blog');

// Invitation acceptance routes (public, guest-only)
Route::get('/invitation/accept/{token}', [InvitationController::class, 'show'])
    ->middleware(['guest', 'throttle:10,1'])
    ->name('invitation.show');
Route::post('/invitation/accept/{token}', [InvitationController::class, 'accept'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('invitation.accept');

// Blog Post Management Routes (Admin)
Route::middleware(['auth', 'verified', 'ensure.2fa', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('blog-posts/drafts', [BlogPostController::class, 'drafts'])->name('blog-posts.drafts');
    
    // Image upload for markdown content
    Route::post('blog-posts/upload-image', [BlogPostController::class, 'uploadImage'])
        ->middleware('throttle:20,1')
        ->name('blog-posts.upload-image');
    
    // Blog post resource routes (store and update have custom rate limiting below)
    Route::resource('blog-posts', BlogPostController::class)
        ->except(['store', 'update']);
    
    // Store and update routes with stricter rate limiting (10 requests per minute)
    Route::post('blog-posts', [BlogPostController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('blog-posts.store');
    
    Route::match(['put', 'patch'], 'blog-posts/{blog_post}', [BlogPostController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('blog-posts.update');
});

// User Management Routes (Master Admin Only)
Route::middleware(['auth', 'verified', 'ensure.2fa', 'master.admin'])->prefix('admin/users')->name('admin.users.')->group(function () {
    Route::get('admins', [UserManagementController::class, 'indexAdmins'])->name('admins');
    Route::get('members', [UserManagementController::class, 'indexMembers'])->name('members');
    Route::post('/', [UserManagementController::class, 'store'])->name('store');
    Route::patch('{user}/role', [UserManagementController::class, 'updateRole'])->name('updateRole');
    Route::delete('{user}', [UserManagementController::class, 'destroy'])->name('destroy');
    Route::post('{user}/resend-invitation', [UserManagementController::class, 'resendInvitation'])->name('resendInvitation');
});

// Public blog post route (individual post viewing by slug)
Route::get('blog/{slug}', [PublicBlogController::class, 'show'])->name('blog.show');

// Author profile route
Route::get('author/{id}', [AuthorProfileController::class, 'show'])->name('author.profile');

// Comment routes (requires authentication with rate limiting)
Route::middleware(['auth', 'throttle:10,1'])->group(function () {
    Route::post('blog/{slug}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::post('blog/{slug}/like', [BlogPostLikeController::class, 'toggle'])->name('blog.like.toggle');
    Route::post('user/{userId}/follow', [UserFollowController::class, 'toggle'])->name('user.follow.toggle');
});

// Delete comment route (separate rate limit)
Route::middleware(['auth'])->group(function () {
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

// Two-factor authentication setup routes (for new users during registration)
Route::middleware(['auth'])->group(function () {
    Route::get('/register/setup-two-factor', function () {
        $registerController = new RegisterController();
        return $registerController->setupTwoFactorAuthentication(Auth::user());
    })->name('register.setup-two-factor');
    
    Route::post('/two-factor-challenge/confirm', [RegisterController::class, 'confirmTwoFactorAuthentication']);
});

require __DIR__.'/settings.php';
