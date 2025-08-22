<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'from_user_id',
        'type',
        'data',
        'read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user that receives the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user that triggered the notification.
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Scope to get unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    /**
     * Scope to get read notifications.
     */
    public function scopeRead($query)
    {
        return $query->where('read', true);
    }

    /**
     * Scope to get notifications by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        $this->update([
            'read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread(): void
    {
        $this->update([
            'read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Get the notification message based on type and data.
     */
    public function getMessageAttribute(): string
    {
        $fromUserName = $this->fromUser?->name ?? 'Someone';
        
        return match ($this->type) {
            'like' => "{$fromUserName} liked your post",
            'retweet' => "{$fromUserName} retweeted your post",
            'comment' => "{$fromUserName} commented on your post",
            'follow' => "{$fromUserName} started following you",
            'mention' => "{$fromUserName} mentioned you in a post",
            'reply' => "{$fromUserName} replied to your comment",
            default => "You have a new notification",
        };
    }

    /**
     * Get the notification icon based on type.
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'like' => 'heart',
            'retweet' => 'repeat',
            'comment', 'reply' => 'message-circle',
            'follow' => 'user-plus',
            'mention' => 'at-sign',
            default => 'bell',
        };
    }
}
