<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningStreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'total_sessions',
        'total_study_time',
        'streak_type'
    ];

    protected $casts = [
        'last_activity_date' => 'date',
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'total_sessions' => 'integer',
        'total_study_time' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if streak is active (activity within last 2 days)
     */
    public function isActive(): bool
    {
        return $this->last_activity_date &&
               $this->last_activity_date >= now()->subDays(2)->startOfDay();
    }

    /**
     * Get streak status
     */
    public function getStatus(): string
    {
        if (!$this->isActive()) {
            return 'broken';
        }

        if ($this->last_activity_date->isToday()) {
            return 'current';
        }

        return 'pending'; // Can be continued today
    }

    /**
     * Get next milestone
     */
    public function getNextMilestone(): ?int
    {
        $milestones = [7, 14, 30, 60, 100, 365];

        foreach ($milestones as $milestone) {
            if ($this->current_streak < $milestone) {
                return $milestone;
            }
        }

        return null;
    }

    /**
     * Get average session duration
     */
    public function getAverageSessionDuration(): float
    {
        return $this->total_sessions > 0 ? $this->total_study_time / $this->total_sessions : 0;
    }
}