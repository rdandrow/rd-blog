<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\PublicBlogController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\Auth\RegisterController;

Route::get('/', [PublicBlogController::class, 'index'])->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'ensure.2fa'])->name('dashboard');

Route::get('blog', [PublicBlogController::class, 'list'])->name('blog');

// Blog Post Management Routes (Admin)
Route::middleware(['auth', 'verified', 'ensure.2fa'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('blog-posts', BlogPostController::class);
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
