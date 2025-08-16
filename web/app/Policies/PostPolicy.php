<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Determine whether the user can view any posts.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view the blog index
        return true;
    }

    /**
     * Determine whether the user can view the post.
     */
    public function view(?User $user, Post $post): bool
    {
        // Anyone can view published posts
        if ($post->status === 'published' && $post->published_at) {
            return true;
        }

        // Only authenticated users can view their own drafts/archived posts
        return $user && $user->id === $post->author_id;
    }

    /**
     * Determine whether the user can create posts.
     */
    public function create(User $user): bool
    {
        // Only authenticated users can create posts
        return true;
    }

    /**
     * Determine whether the user can update the post.
     */
    public function update(User $user, Post $post): bool
    {
        // Users can only update their own posts
        return $user->id === $post->author_id;
    }

    /**
     * Determine whether the user can delete the post.
     */
    public function delete(User $user, Post $post): bool
    {
        // Users can only delete their own posts
        return $user->id === $post->author_id;
    }

    /**
     * Determine whether the user can restore the post.
     */
    public function restore(User $user, Post $post): bool
    {
        // Users can only restore their own posts
        return $user->id === $post->author_id;
    }

    /**
     * Determine whether the user can permanently delete the post.
     */
    public function forceDelete(User $user, Post $post): bool
    {
        // Users can only force delete their own posts
        return $user->id === $post->author_id;
    }

    /**
     * Determine whether the user can publish/unpublish posts.
     */
    public function publish(User $user, Post $post): bool
    {
        // Users can only publish/unpublish their own posts
        return $user->id === $post->author_id;
    }

    /**
     * Determine whether the user can feature/unfeature posts.
     */
    public function feature(User $user, Post $post): bool
    {
        // For now, users can feature their own posts
        // In a real application, you might want to restrict this to admins
        return $user->id === $post->author_id;
    }

    /**
     * Determine whether the user can manage comments for the post.
     */
    public function manageComments(User $user, Post $post): bool
    {
        // Users can manage comments on their own posts
        return $user->id === $post->author_id;
    }
}