<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionReplay extends Model
{
    use HasFactory;

    protected $fillable = [
        'osce_session_id',
        'replay_data',
        'generation_version',
        'user_feedback',
        'viewed_at'
    ];

    protected $casts = [
        'replay_data' => 'array',
        'viewed_at' => 'datetime'
    ];

    public function osceSession(): BelongsTo
    {
        return $this->belongsTo(OsceSession::class);
    }

    /**
     * Get timeline data
     */
    public function getTimeline(): array
    {
        return $this->replay_data['timeline'] ?? [];
    }

    /**
     * Get alternative scenarios
     */
    public function getAlternativeScenarios(): array
    {
        return $this->replay_data['alternative_scenarios'] ?? [];
    }

    /**
     * Get performance insights
     */
    public function getPerformanceInsights(): array
    {
        return $this->replay_data['performance_insights'] ?? [];
    }

    /**
     * Get voiceover scripts
     */
    public function getVoiceoverScripts(): array
    {
        return $this->replay_data['voiceover_scripts'] ?? [];
    }

    /**
     * Get session summary
     */
    public function getSessionSummary(): array
    {
        return $this->replay_data['session_summary'] ?? [];
    }

    /**
     * Check if this is a fallback replay
     */
    public function isFallback(): bool
    {
        return $this->replay_data['fallback'] ?? false;
    }

    /**
     * Mark as viewed
     */
    public function markAsViewed(): void
    {
        $this->update(['viewed_at' => now()]);
    }

    /**
     * Get pivotal moments from timeline
     */
    public function getPivotalMoments(): array
    {
        $timeline = $this->getTimeline();
        return $timeline['pivotal_moments'] ?? [];
    }

    /**
     * Get session duration
     */
    public function getSessionDuration(): int
    {
        $timeline = $this->getTimeline();
        return $timeline['duration_minutes'] ?? 0;
    }

    /**
     * Get phase breakdown
     */
    public function getPhaseBreakdown(): array
    {
        $timeline = $this->getTimeline();
        return $timeline['phase_breakdown'] ?? [];
    }

    /**
     * Count total events
     */
    public function getTotalEvents(): int
    {
        $timeline = $this->getTimeline();
        return count($timeline['events'] ?? []);
    }
}