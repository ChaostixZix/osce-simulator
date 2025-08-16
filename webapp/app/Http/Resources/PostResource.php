<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        
        return [
            'id' => $this->id,
            'content' => $this->content,
            'images' => $this->images ?? [],
            'location' => $this->location,
            'scheduled_at' => $this->scheduled_at,
            'is_public' => $this->is_public,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // User information
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'avatar' => $this->user->avatar,
                'is_verified' => $this->user->is_verified,
            ],
            
            // Interaction counts
            'likes_count' => $this->likes_count,
            'retweets_count' => $this->retweets_count,
            'bookmarks_count' => $this->bookmarks_count,
            'comments_count' => $this->comments_count,
            
            // Current user's interactions
            'is_liked' => $user ? $this->isLikedBy($user) : false,
            'is_retweeted' => $user ? $this->isRetweetedBy($user) : false,
            'is_bookmarked' => $user ? $this->isBookmarkedBy($user) : false,
            
            // Formatted timestamps
            'formatted_created_at' => $this->created_at?->diffForHumans(),
            'formatted_updated_at' => $this->updated_at?->diffForHumans(),
        ];
    }
}
