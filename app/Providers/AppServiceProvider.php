<?php

namespace App\Providers;

use App\Services\BlogImageService;
use App\Services\BlogPostService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services as singletons for better performance
        $this->app->singleton(BlogImageService::class);
        $this->app->singleton(BlogPostService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
