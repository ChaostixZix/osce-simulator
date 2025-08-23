<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class ForumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test user if none exists
        $user = User::first();
        if (! $user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'workos_id' => 'test_workos_id_123',
                'avatar' => 'https://ui-avatars.com/api/?name=Test+User&background=0d1117&color=fff',
            ]);
        }

        // Create sample posts
        $posts = [
            [
                'title' => 'Welcome to the Medical Training Forum',
                'content' => 'This is a simple forum where medical students and professionals can discuss various topics related to their studies and practice. Feel free to share your experiences, ask questions, and help others in their learning journey.',
            ],
            [
                'title' => 'Best Practices for OSCE Preparation',
                'content' => 'What are your top tips for preparing for OSCE examinations? I\'ve been struggling with the practical aspects and would love to hear from those who have successfully passed their OSCEs.',
            ],
            [
                'title' => 'Resources for Medical MCQ Practice',
                'content' => 'Can anyone recommend good resources for practicing multiple choice questions? I\'m looking for comprehensive question banks that cover various medical specialties.',
            ],
        ];

        foreach ($posts as $postData) {
            $post = Post::create([
                'title' => $postData['title'],
                'content' => $postData['content'],
                'user_id' => $user->id,
            ]);

            // Add some comments to posts
            if ($post->title === 'Best Practices for OSCE Preparation') {
                Comment::create([
                    'content' => 'I found that practicing with peers and timing yourself is crucial. Also, don\'t forget to review common procedures and their step-by-step processes.',
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ]);

                Comment::create([
                    'content' => 'Great question! I also recommend recording yourself performing procedures and reviewing them later to identify areas for improvement.',
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ]);
            }

            if ($post->title === 'Resources for Medical MCQ Practice') {
                Comment::create([
                    'content' => 'I highly recommend Question Bank XYZ - it has over 10,000 questions with detailed explanations. Very helpful for exam preparation.',
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ]);
            }
        }
    }
}
