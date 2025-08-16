<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;
use App\Models\PostInteraction;
use App\Models\Comment;
use App\Models\Notification;
use Illuminate\Support\Facades\Hash;

class ForumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample users
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'username' => 'johndoe',
                'bio' => 'Full-stack developer passionate about Vue.js and Laravel',
                'location' => 'San Francisco, CA',
                'website' => 'https://johndoe.dev',
                'is_verified' => true,
                'avatar' => 'https://github.com/shadcn.png',
            ],
            [
                'name' => 'Sarah Wilson',
                'email' => 'sarah@example.com',
                'username' => 'sarahdev',
                'bio' => 'UI/UX designer and frontend developer',
                'location' => 'New York, NY',
                'website' => 'https://sarahwilson.design',
                'is_verified' => false,
                'avatar' => 'https://github.com/shadcn.png',
            ],
            [
                'name' => 'Alex Chen',
                'email' => 'alex@example.com',
                'username' => 'alexchen',
                'bio' => 'Backend engineer specializing in scalable systems',
                'location' => 'Seattle, WA',
                'website' => 'https://alexchen.tech',
                'is_verified' => true,
                'avatar' => 'https://github.com/shadcn.png',
            ],
            [
                'name' => 'Maria Garcia',
                'email' => 'maria@example.com',
                'username' => 'mariagarcia',
                'bio' => 'DevOps engineer and cloud specialist',
                'location' => 'Austin, TX',
                'website' => 'https://mariagarcia.cloud',
                'is_verified' => false,
                'avatar' => 'https://github.com/shadcn.png',
            ],
            [
                'name' => 'David Kim',
                'email' => 'david@example.com',
                'username' => 'davidkim',
                'bio' => 'Mobile app developer and React Native enthusiast',
                'location' => 'Los Angeles, CA',
                'website' => 'https://davidkim.mobile',
                'is_verified' => true,
                'avatar' => 'https://github.com/shadcn.png',
            ],
        ];

        foreach ($users as $userData) {
            User::create(array_merge($userData, [
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]));
        }

        // Create sample posts
        $posts = [
            [
                'user_id' => 1,
                'content' => 'Just deployed my new Vue.js app! 🚀 The development experience has been amazing. What\'s your favorite frontend framework?',
                'location' => 'San Francisco, CA',
            ],
            [
                'user_id' => 2,
                'content' => 'Working on some really exciting features for our platform. Can\'t wait to share what we\'ve been building! 💻✨',
                'location' => 'New York, NY',
            ],
            [
                'user_id' => 3,
                'content' => 'Breaking: New AI models are revolutionizing how we approach software development. The future is here! 🤖',
                'location' => 'Seattle, WA',
            ],
            [
                'user_id' => 4,
                'content' => 'Just finished setting up our new CI/CD pipeline. The automation is incredible! 🚀 #DevOps #Automation',
                'location' => 'Austin, TX',
            ],
            [
                'user_id' => 5,
                'content' => 'React Native performance optimization tips: Always use FlatList for long lists, implement proper memoization, and avoid unnecessary re-renders! 📱',
                'location' => 'Los Angeles, CA',
            ],
            [
                'user_id' => 1,
                'content' => 'Laravel 11 is absolutely incredible! The new features and performance improvements are game-changing. Anyone else excited about the new release? 🎉',
                'location' => 'San Francisco, CA',
            ],
            [
                'user_id' => 2,
                'content' => 'Design tip: Always consider accessibility first. Your beautiful UI means nothing if it\'s not usable by everyone. ♿️ #Accessibility #UX',
                'location' => 'New York, NY',
            ],
            [
                'user_id' => 3,
                'content' => 'Microservices architecture: When to use it, when to avoid it, and how to implement it properly. Thoughts? 🤔 #Architecture #Microservices',
                'location' => 'Seattle, WA',
            ],
        ];

        foreach ($posts as $postData) {
            Post::create($postData);
        }

        // Create sample interactions (likes, retweets, bookmarks)
        $interactions = [
            // Post 1 interactions
            ['user_id' => 2, 'post_id' => 1, 'type' => 'like'],
            ['user_id' => 3, 'post_id' => 1, 'type' => 'like'],
            ['user_id' => 4, 'post_id' => 1, 'type' => 'retweet'],
            ['user_id' => 5, 'post_id' => 1, 'type' => 'bookmark'],
            
            // Post 2 interactions
            ['user_id' => 1, 'post_id' => 2, 'type' => 'like'],
            ['user_id' => 3, 'post_id' => 2, 'type' => 'like'],
            ['user_id' => 4, 'post_id' => 2, 'type' => 'like'],
            ['user_id' => 5, 'post_id' => 2, 'type' => 'retweet'],
            
            // Post 3 interactions
            ['user_id' => 1, 'post_id' => 3, 'type' => 'like'],
            ['user_id' => 2, 'post_id' => 3, 'type' => 'like'],
            ['user_id' => 4, 'post_id' => 3, 'type' => 'retweet'],
            ['user_id' => 5, 'post_id' => 3, 'type' => 'bookmark'],
            
            // Post 4 interactions
            ['user_id' => 1, 'post_id' => 4, 'type' => 'like'],
            ['user_id' => 2, 'post_id' => 4, 'type' => 'like'],
            ['user_id' => 3, 'post_id' => 4, 'type' => 'like'],
            ['user_id' => 5, 'post_id' => 4, 'type' => 'retweet'],
            
            // Post 5 interactions
            ['user_id' => 1, 'post_id' => 5, 'type' => 'like'],
            ['user_id' => 2, 'post_id' => 5, 'type' => 'bookmark'],
            ['user_id' => 3, 'post_id' => 5, 'type' => 'like'],
            ['user_id' => 4, 'post_id' => 5, 'type' => 'like'],
        ];

        foreach ($interactions as $interactionData) {
            PostInteraction::create($interactionData);
        }

        // Create sample comments
        $comments = [
            [
                'user_id' => 2,
                'post_id' => 1,
                'content' => 'Vue.js is definitely my favorite! The composition API is a game-changer.',
            ],
            [
                'user_id' => 3,
                'post_id' => 1,
                'content' => 'I\'m still team React, but Vue 3 is making me reconsider!',
            ],
            [
                'user_id' => 1,
                'post_id' => 2,
                'content' => 'Can\'t wait to see what you\'ve built! Always love your work.',
            ],
            [
                'user_id' => 4,
                'post_id' => 3,
                'content' => 'AI is definitely the future. Exciting times ahead!',
            ],
            [
                'user_id' => 5,
                'post_id' => 4,
                'content' => 'CI/CD automation is a lifesaver! What tools are you using?',
            ],
        ];

        foreach ($comments as $commentData) {
            Comment::create($commentData);
        }

        // Create sample notifications
        $notifications = [
            [
                'user_id' => 1,
                'from_user_id' => 2,
                'type' => 'like',
                'data' => ['post_id' => 1],
            ],
            [
                'user_id' => 1,
                'from_user_id' => 4,
                'type' => 'retweet',
                'data' => ['post_id' => 1],
            ],
            [
                'user_id' => 2,
                'from_user_id' => 1,
                'type' => 'like',
                'data' => ['post_id' => 2],
            ],
            [
                'user_id' => 3,
                'from_user_id' => 1,
                'type' => 'like',
                'data' => ['post_id' => 3],
            ],
        ];

        foreach ($notifications as $notificationData) {
            Notification::create($notificationData);
        }

        // Create some follow relationships
        $user1 = User::find(1);
        $user2 = User::find(2);
        $user3 = User::find(3);
        $user4 = User::find(4);
        $user5 = User::find(5);

        // User 1 follows users 2, 3, 4
        $user1->following()->attach([2, 3, 4]);
        
        // User 2 follows users 1, 3, 5
        $user2->following()->attach([1, 3, 5]);
        
        // User 3 follows users 1, 2, 4
        $user3->following()->attach([1, 2, 4]);
        
        // User 4 follows users 1, 3, 5
        $user4->following()->attach([1, 3, 5]);
        
        // User 5 follows users 1, 2, 4
        $user5->following()->attach([1, 2, 4]);

        $this->command->info('Forum sample data seeded successfully!');
    }
}