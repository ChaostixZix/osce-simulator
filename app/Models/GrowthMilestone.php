<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrowthMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'milestone_type',
        'milestone_title',
        'milestone_description',
        'threshold_value',
        'current_value',
        'achieved_at',
        'badge_icon',
        'badge_color'
    ];

    protected $casts = [
        'achieved_at' => 'datetime',
        'threshold_value' => 'integer',
        'current_value' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get badge icon based on milestone type
     */
    public function getBadgeIcon(): string
    {
        if ($this->badge_icon) {
            return $this->badge_icon;
        }

        return match($this->milestone_type) {
            'sessions_completed' => '🎯',
            'learning_streak' => '🔥',
            'study_time' => '⏰',
            'assessment_score' => '⭐',
            default => '🏆'
        };
    }

    /**
     * Get badge color based on milestone type
     */
    public function getBadgeColor(): string
    {
        if ($this->badge_color) {
            return $this->badge_color;
        }

        return match($this->milestone_type) {
            'sessions_completed' => 'blue',
            'learning_streak' => 'orange',
            'study_time' => 'green',
            'assessment_score' => 'yellow',
            default => 'purple'
        };
    }

    /**
     * Check if milestone was achieved recently
     */
    public function isRecent(): bool
    {
        return $this->achieved_at && $this->achieved_at >= now()->subDays(7);
    }

    /**
     * Get milestone rarity
     */
    public function getRarity(): string
    {
        $achieversCount = static::where('milestone_type', $this->milestone_type)
            ->where('threshold_value', $this->threshold_value)
            ->count();

        if ($achieversCount < 10) return 'legendary';
        if ($achieversCount < 50) return 'epic';
        if ($achieversCount < 200) return 'rare';
        return 'common';
    }

    /**
     * Scope for recent achievements
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('achieved_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for specific milestone type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('milestone_type', $type);
    }
}