<?php

namespace Database\Factories;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'blog_post_id' => BlogPost::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'content' => fake()->paragraph(),
        ];
    }

    /**
     * Create a reply to another comment.
     */
    public function reply($parentCommentId = null)
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentCommentId,
        ]);
    }
}
