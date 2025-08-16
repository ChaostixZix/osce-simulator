<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();
        
        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->optional(0.7)->sentence(rand(8, 15)),
            'color' => fake()->randomElement([
                '#10b981', // Emerald
                '#f59e0b', // Amber
                '#8b5cf6', // Violet
                '#06b6d4', // Cyan
                '#ef4444', // Red
                '#3b82f6', // Blue
                '#ec4899', // Pink
                '#84cc16', // Lime
                '#6366f1', // Indigo
                '#f97316', // Orange
            ]),
        ];
    }

    /**
     * Common programming tags
     */
    public function programming(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement([
                'PHP', 'JavaScript', 'Laravel', 'Vue.js', 'React', 'Node.js', 
                'TypeScript', 'Python', 'CSS', 'HTML', 'MySQL', 'Docker'
            ]),
            'color' => '#10b981',
        ]);
    }

    /**
     * Common blog category tags
     */
    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement([
                'Tutorial', 'Guide', 'Tips', 'Review', 'News', 'Opinion',
                'Analysis', 'Beginner', 'Advanced', 'Best Practices'
            ]),
            'color' => '#3b82f6',
        ]);
    }
}