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
        // Let's see what the parent method returns
        $parent = parent::toArray($request);

        return [
            'DEBUGGING' => 'Our custom PostResource is being called',
            'PARENT_RESULT' => $parent,
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
            ] : null,
            'comments_count' => $this->comments_count ?? $this->comments()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'formatted_created_at' => $this->created_at?->diffForHumans(),
        ];
    }
}
