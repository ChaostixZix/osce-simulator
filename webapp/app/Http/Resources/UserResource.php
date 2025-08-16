<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'location' => $this->location,
            'website' => $this->website,
            'birth_date' => $this->birth_date,
            'is_verified' => $this->is_verified,
            'is_private' => $this->is_private,
            'social_links' => $this->social_links,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Counts
            'posts_count' => $this->posts_count,
            'followers_count' => $this->followers_count,
            'following_count' => $this->following_count,
            
            // Current user's relationship with this user
            'is_following' => $currentUser ? $this->isFollowing($currentUser) : false,
            'is_followed_by' => $currentUser ? $this->isFollowedBy($currentUser) : false,
            
            // Formatted timestamps
            'formatted_created_at' => $this->created_at->diffForHumans(),
            'formatted_updated_at' => $this->updated_at->diffForHumans(),
        ];
    }
}