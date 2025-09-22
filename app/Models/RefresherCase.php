<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefresherCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'osce_case_id',
        'content_type',
        'content',
        'difficulty',
        'generated_at',
        'completed_at',
        'performance_score',
        'next_reminder_date'
    ];

    protected $casts = [
        'content' => 'array',
        'generated_at' => 'datetime',
        'completed_at' => 'datetime',
        'next_reminder_date' => 'datetime',
        'performance_score' => 'float'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function osceCase(): BelongsTo
    {
        return $this->belongsTo(OsceCase::class);
    }

    /**
     * Check if refresher is due
     */
    public function isDue(): bool
    {
        return $this->next_reminder_date <= now();
    }

    /**
     * Check if refresher is completed
     */
    public function isCompleted(): bool
    {
        return !is_null($this->completed_at);
    }

    /**
     * Get content based on type
     */
    public function getFormattedContent(): array
    {
        return match($this->content_type) {
            'quick_quiz' => [
                'type' => 'quiz',
                'questions' => $this->content['questions'] ?? [],
                'estimated_time' => '5 minutes'
            ],
            'case_review' => [
                'type' => 'review',
                'summary' => $this->content['summary'] ?? '',
                'key_points' => $this->content['key_points'] ?? [],
                'estimated_time' => '10 minutes'
            ],
            'skill_drill' => [
                'type' => 'drill',
                'scenario' => $this->content['scenario'] ?? '',
                'steps' => $this->content['steps'] ?? [],
                'estimated_time' => '15 minutes'
            ],
            default => $this->content
        };
    }

    /**
     * Get difficulty color
     */
    public function getDifficultyColor(): string
    {
        return match($this->difficulty) {
            'easy' => 'green',
            'medium' => 'yellow',
            'hard' => 'red',
            default => 'gray'
        };
    }

    /**
     * Scope for due refreshers
     */
    public function scopeDue($query)
    {
        return $query->where('next_reminder_date', '<=', now())
                    ->whereNull('completed_at');
    }

    /**
     * Scope for pending refreshers
     */
    public function scopePending($query)
    {
        return $query->whereNull('completed_at');
    }

    /**
     * Scope for specific content type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('content_type', $type);
    }
}