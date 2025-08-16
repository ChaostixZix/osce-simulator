<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(rand(4, 8), false);
        $content = $this->generateContent();
        
        return [
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title),
            'excerpt' => fake()->paragraph(2),
            'content' => $content,
            'featured_image' => fake()->optional(0.6)->imageUrl(1200, 630, 'blog'),
            'category_id' => Category::factory(),
            'author_id' => User::factory(),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'published_at' => fake()->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'meta_data' => [
                'meta_title' => fake()->sentence(6),
                'meta_description' => fake()->sentence(15),
                'keywords' => fake()->words(5),
            ],
            'views_count' => fake()->numberBetween(0, 10000),
            'is_featured' => fake()->boolean(15), // 15% chance of being featured
            'comments_enabled' => fake()->boolean(85), // 85% chance of comments enabled
        ];
    }

    /**
     * Generate realistic blog content
     */
    private function generateContent(): string
    {
        $paragraphs = [];
        $paragraphCount = fake()->numberBetween(3, 8);
        
        for ($i = 0; $i < $paragraphCount; $i++) {
            $paragraphs[] = fake()->paragraph(fake()->numberBetween(4, 8));
        }
        
        return implode("\n\n", $paragraphs);
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the post is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the post is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    /**
     * Indicate that the post is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    /**
     * Generate a post with high views
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'views_count' => fake()->numberBetween(5000, 50000),
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-1 year', '-3 months'),
        ]);
    }
}