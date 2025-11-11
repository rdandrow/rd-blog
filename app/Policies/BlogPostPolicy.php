<?php

namespace App\Policies;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BlogPostPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view blog posts
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BlogPost $blogPost): bool
    {
        return true; // All authenticated users can view individual posts
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create blog posts
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BlogPost $blogPost): bool
    {
        return $user->id === $blogPost->user_id; // Only the author can update
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BlogPost $blogPost): bool
    {
        return $user->id === $blogPost->user_id; // Only the author can delete
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BlogPost $blogPost): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BlogPost $blogPost): bool
    {
        return false;
    }
}
