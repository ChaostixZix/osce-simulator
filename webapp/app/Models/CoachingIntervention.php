<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachingIntervention extends Model
{
    use HasFactory;

    protected $fillable = [
        'osce_session_id',
        'intervention_type',
        'trigger_reason',
        'content',
        'priority',
        'displayed_at',
        'user_response',
        'effectiveness_rating'
    ];

    protected $casts = [
        'displayed_at' => 'datetime',
        'effectiveness_rating' => 'integer'
    ];

    public function osceSession(): BelongsTo
    {
        return $this->belongsTo(OsceSession::class);
    }

    /**
     * Check if intervention was displayed to user
     */
    public function wasDisplayed(): bool
    {
        return !is_null($this->displayed_at);
    }

    /**
     * Check if intervention is high priority
     */
    public function isHighPriority(): bool
    {
        return $this->priority === 'high';
    }

    /**
     * Get priority color for UI
     */
    public function getPriorityColor(): string
    {
        return match($this->priority) {
            'high' => 'red',
            'medium' => 'orange',
            'low' => 'blue',
            default => 'gray'
        };
    }

    /**
     * Get intervention type icon
     */
    public function getTypeIcon(): string
    {
        return match($this->intervention_type) {
            'decision_support' => '🤔',
            'resource_management' => '💰',
            'time_management' => '⏱️',
            'communication' => '💬',
            'knowledge_check' => '🧠',
            default => '💡'
        };
    }

    /**
     * Scope for recent interventions
     */
    public function scopeRecent($query, int $minutes = 30)
    {
        return $query->where('created_at', '>', now()->subMinutes($minutes));
    }

    /**
     * Scope for displayed interventions
     */
    public function scopeDisplayed($query)
    {
        return $query->whereNotNull('displayed_at');
    }

    /**
     * Scope for high priority interventions
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }
}