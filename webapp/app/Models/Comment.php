<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'post_id',
        'parent_id',
        'content',
        'images',
        'is_public',
    ];

    protected $casts = [
        'images' => 'array',
        'is_public' => 'boolean',
    ];

    protected $with = ['user'];

    /**
     * Get the user that wrote the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post that the comment belongs to.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the parent comment (for threaded comments).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get the child comments (for threaded comments).
     */
    public function children(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * Scope to get only public comments.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get only top-level comments.
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get comments by user.
     */
    public function scopeByUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }
}
