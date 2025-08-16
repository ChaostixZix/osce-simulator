<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Builder;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'images',
        'location',
        'scheduled_at',
        'is_public',
    ];

    protected $casts = [
        'images' => 'array',
        'scheduled_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    protected $with = ['user', 'hashtags'];

    /**
     * Get the user that owns the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the comments for the post.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the hashtags for the post.
     */
    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(Hashtag::class, 'post_hashtags');
    }

    /**
     * Get the interactions for the post.
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(PostInteraction::class);
    }

    /**
     * Get likes count for the post.
     */
    public function getLikesCountAttribute(): int
    {
        return $this->interactions()->where('type', 'like')->count();
    }

    /**
     * Get retweets count for the post.
     */
    public function getRetweetsCountAttribute(): int
    {
        return $this->interactions()->where('type', 'retweet')->count();
    }

    /**
     * Get bookmarks count for the post.
     */
    public function getBookmarksCountAttribute(): int
    {
        return $this->interactions()->where('type', 'bookmark')->count();
    }

    /**
     * Get comments count for the post.
     */
    public function getCommentsCountAttribute(): int
    {
        return $this->comments()->count();
    }

    /**
     * Check if a user has liked the post.
     */
    public function isLikedBy(User $user): bool
    {
        return $this->interactions()
            ->where('user_id', $user->id)
            ->where('type', 'like')
            ->exists();
    }

    /**
     * Check if a user has retweeted the post.
     */
    public function isRetweetedBy(User $user): bool
    {
        return $this->interactions()
            ->where('user_id', $user->id)
            ->where('type', 'retweet')
            ->exists();
    }

    /**
     * Check if a user has bookmarked the post.
     */
    public function isBookmarkedBy(User $user): bool
    {
        return $this->interactions()
            ->where('user_id', $user->id)
            ->where('type', 'bookmark')
            ->exists();
    }

    /**
     * Scope to get only public posts.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get posts from followed users.
     */
    public function scopeFromFollowedUsers(Builder $query, User $user): Builder
    {
        $followingIds = $user->following()->pluck('users.id');
        return $query->whereIn('user_id', $followingIds);
    }

    /**
     * Scope to get trending posts.
     */
    public function scopeTrending(Builder $query): Builder
    {
        return $query->withCount(['interactions as total_interactions' => function ($query) {
            $query->whereIn('type', ['like', 'retweet', 'comment']);
        }])
        ->orderBy('total_interactions', 'desc')
        ->orderBy('created_at', 'desc');
    }

    /**
     * Extract hashtags from content and sync them.
     */
    public function syncHashtags(): void
    {
        preg_match_all('/#(\w+)/', $this->content, $matches);
        $hashtagNames = $matches[1] ?? [];

        $hashtagIds = collect($hashtagNames)->map(function ($name) {
            return Hashtag::firstOrCreate(['name' => strtolower($name)])->id;
        });

        $this->hashtags()->sync($hashtagIds);
    }

    /**
     * Boot the model and add event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($post) {
            $post->syncHashtags();
        });
    }
}