<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\PublicBlogController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Admin\UserManagementController;

Route::get('/', [PublicBlogController::class, 'index'])->name('home');

// Admin Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'login']);
    Route::get('/admin/register', [AdminAuthController::class, 'showRegisterForm'])->name('admin.register');
    Route::post('/admin/register', [AdminAuthController::class, 'register']);
});

Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->middleware('auth')->name('admin.logout');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'ensure.2fa', 'admin'])->name('dashboard');

Route::get('blog', [PublicBlogController::class, 'list'])->name('blog');

// Blog Post Management Routes (Admin)
Route::middleware(['auth', 'verified', 'ensure.2fa', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('blog-posts/drafts', [BlogPostController::class, 'drafts'])->name('blog-posts.drafts');
    Route::resource('blog-posts', BlogPostController::class);
});

// User Management Routes (Master Admin Only)
Route::middleware(['auth', 'verified', 'ensure.2fa', 'master.admin'])->prefix('admin/users')->name('admin.users.')->group(function () {
    Route::get('admins', [UserManagementController::class, 'indexAdmins'])->name('admins');
    Route::get('members', [UserManagementController::class, 'indexMembers'])->name('members');
    Route::post('/', [UserManagementController::class, 'store'])->name('store');
    Route::patch('{user}/role', [UserManagementController::class, 'updateRole'])->name('updateRole');
    Route::delete('{user}', [UserManagementController::class, 'destroy'])->name('destroy');
});

// Public blog post route (individual post viewing by slug)
Route::get('blog/{slug}', [PublicBlogController::class, 'show'])->name('blog.show');

// Two-factor authentication setup routes (for new users during registration)
Route::middleware(['auth'])->group(function () {
    Route::get('/register/setup-two-factor', function () {
        $registerController = new RegisterController();
        return $registerController->setupTwoFactorAuthentication(Auth::user());
    })->name('register.setup-two-factor');
    
    Route::post('/two-factor-challenge/confirm', [RegisterController::class, 'confirmTwoFactorAuthentication']);
});

require __DIR__.'/settings.php';
