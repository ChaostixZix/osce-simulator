<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create categories
        $categories = [
            ['name' => 'Web Development', 'description' => 'Frontend and backend web development tutorials', 'color' => '#3b82f6'],
            ['name' => 'Laravel', 'description' => 'Laravel framework tutorials and tips', 'color' => '#ef4444'],
            ['name' => 'Vue.js', 'description' => 'Vue.js framework and ecosystem', 'color' => '#10b981'],
            ['name' => 'DevOps', 'description' => 'Development operations and deployment', 'color' => '#f59e0b'],
            ['name' => 'Tutorials', 'description' => 'Step-by-step programming tutorials', 'color' => '#8b5cf6'],
            ['name' => 'News', 'description' => 'Latest tech news and updates', 'color' => '#06b6d4'],
        ];

        foreach ($categories as $categoryData) {
            Category::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($categoryData['name'])],
                $categoryData
            );
        }

        // Create tags
        $tags = [
            // Programming languages
            ['name' => 'PHP', 'color' => '#8993be'],
            ['name' => 'JavaScript', 'color' => '#f7df1e'],
            ['name' => 'TypeScript', 'color' => '#3178c6'],
            ['name' => 'CSS', 'color' => '#1572b6'],
            ['name' => 'HTML', 'color' => '#e34f26'],
            
            // Frameworks and tools
            ['name' => 'Laravel', 'color' => '#ff2d20'],
            ['name' => 'Vue.js', 'color' => '#4fc08d'],
            ['name' => 'Inertia.js', 'color' => '#9553e9'],
            ['name' => 'Tailwind CSS', 'color' => '#06b6d4'],
            ['name' => 'Docker', 'color' => '#2496ed'],
            
            // General tags
            ['name' => 'Tutorial', 'color' => '#10b981'],
            ['name' => 'Guide', 'color' => '#f59e0b'],
            ['name' => 'Tips', 'color' => '#ec4899'],
            ['name' => 'Best Practices', 'color' => '#6366f1'],
            ['name' => 'Beginner', 'color' => '#84cc16'],
        ];

        foreach ($tags as $tagData) {
            Tag::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($tagData['name'])],
                $tagData
            );
        }

        // Get or create a default user for posts
        $user = User::first() ?? User::factory()->create([
            'name' => 'Blog Author',
            'email' => 'author@example.com',
        ]);

        // Create additional users for variety
        $users = User::factory(3)->create();
        $allUsers = collect([$user])->merge($users);

        $categories = Category::all();
        $tags = Tag::all();

        // Create featured posts
        $featuredPosts = Post::factory()
            ->count(3)
            ->featured()
            ->create([
                'author_id' => $allUsers->random()->id,
                'category_id' => $categories->random()->id,
            ]);

        // Attach random tags to featured posts
        foreach ($featuredPosts as $post) {
            $post->tags()->attach($tags->random(rand(2, 4))->pluck('id'));
        }

        // Create published posts
        $publishedPosts = Post::factory()
            ->count(15)
            ->published()
            ->create([
                'author_id' => $allUsers->random()->id,
                'category_id' => $categories->random()->id,
            ]);

        // Attach random tags to published posts
        foreach ($publishedPosts as $post) {
            $post->tags()->attach($tags->random(rand(1, 3))->pluck('id'));
        }

        // Create draft posts
        $draftPosts = Post::factory()
            ->count(5)
            ->draft()
            ->create([
                'author_id' => $allUsers->random()->id,
                'category_id' => $categories->random()->id,
            ]);

        // Attach random tags to draft posts
        foreach ($draftPosts as $post) {
            $post->tags()->attach($tags->random(rand(1, 2))->pluck('id'));
        }

        // Create some popular posts
        $popularPosts = Post::factory()
            ->count(3)
            ->popular()
            ->create([
                'author_id' => $allUsers->random()->id,
                'category_id' => $categories->random()->id,
            ]);

        // Attach random tags to popular posts
        foreach ($popularPosts as $post) {
            $post->tags()->attach($tags->random(rand(2, 5))->pluck('id'));
        }

        $this->command->info('Blog seeder completed successfully!');
        $this->command->info('Created: ' . Category::count() . ' categories');
        $this->command->info('Created: ' . Tag::count() . ' tags');
        $this->command->info('Created: ' . Post::count() . ' posts');
        $this->command->info('Created: ' . User::count() . ' users');
    }
}