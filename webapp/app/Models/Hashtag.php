<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hashtag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'post_count',
        'trending_score',
    ];

    protected $casts = [
        'post_count' => 'integer',
        'trending_score' => 'integer',
    ];

    /**
     * Get the posts that use this hashtag.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_hashtags');
    }

    /**
     * Scope to get trending hashtags.
     */
    public function scopeTrending($query, int $limit = 10)
    {
        return $query->orderBy('trending_score', 'desc')
                    ->orderBy('post_count', 'desc')
                    ->limit($limit);
    }

    /**
     * Scope to get hashtags by popularity.
     */
    public function scopePopular($query, int $limit = 20)
    {
        return $query->orderBy('post_count', 'desc')
                    ->limit($limit);
    }

    /**
     * Update the trending score based on recent activity.
     */
    public function updateTrendingScore(): void
    {
        $recentPosts = $this->posts()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $recentInteractions = PostInteraction::whereHas('post', function ($query) {
            $query->whereHas('hashtags', function ($q) {
                $q->where('hashtags.id', $this->id);
            });
        })
        ->where('created_at', '>=', now()->subDays(7))
        ->count();

        $this->trending_score = ($recentPosts * 2) + $recentInteractions;
        $this->save();
    }

    /**
     * Increment post count when a post uses this hashtag.
     */
    public function incrementPostCount(): void
    {
        $this->increment('post_count');
    }

    /**
     * Decrement post count when a post no longer uses this hashtag.
     */
    public function decrementPostCount(): void
    {
        $this->decrement('post_count');
    }

    /**
     * Get the formatted name with hash symbol.
     */
    public function getFormattedNameAttribute(): string
    {
        return '#' . $this->name;
    }

    /**
     * Boot the model and add event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Update trending score periodically
        static::saved(function ($hashtag) {
            // In a real app, you might want to use a job queue for this
            // $hashtag->updateTrendingScore();
        });
    }
}