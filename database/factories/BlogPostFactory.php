<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlogPost>
 */
class BlogPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4, true);
        $publishedAt = fake()->boolean(80) ? fake()->dateTimeBetween('-1 year', 'now') : null;
        
        return [
            'title' => rtrim($title, '.'),
            'slug' => \Illuminate\Support\Str::slug($title),
            'excerpt' => fake()->paragraph(2),
            'content' => fake()->paragraphs(8, true),
            'featured_image' => fake()->boolean(30) ? 'https://picsum.photos/800/400?random=' . fake()->numberBetween(1, 1000) : null,
            'tags' => fake()->randomElements([
                'Vue.js', 'Laravel', 'JavaScript', 'PHP', 'Frontend', 'Backend', 
                'Tutorial', 'Tips', 'Web Development', 'CSS', 'Tailwind', 
                'Inertia.js', 'Database', 'API', 'Performance'
            ], fake()->numberBetween(1, 4)),
            'is_featured' => fake()->boolean(20),
            'is_published' => $publishedAt !== null,
            'published_at' => $publishedAt,
            'user_id' => 1, // Assuming the first user exists
        ];
    }

    public function featured()
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function published()
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function draft()
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
