<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OsceSession extends Model
{
    protected $fillable = [
        'user_id',
        'osce_case_id',
        'status',
        'started_at',
        'completed_at',
        'score',
        'max_score',
        'time_extended',
        'clinical_reasoning_score',
        'total_test_cost',
        'evaluation_feedback',
        'responses',
        'feedback',
        'paused_at',
        'resumed_at',
        'total_paused_seconds',
        'current_remaining_seconds'
    ];

    protected $appends = [
        'elapsed_seconds',
        'remaining_seconds',
        'duration_minutes',
        'is_expired',
        'time_status'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'time_extended' => 'integer',
        'total_paused_seconds' => 'integer',
        'current_remaining_seconds' => 'integer',
        'responses' => 'array',
        'feedback' => 'array',
        'evaluation_feedback' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function osceCase(): BelongsTo
    {
        return $this->belongsTo(OsceCase::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(OsceChatMessage::class)->orderBy('sent_at', 'asc');
    }

    public function getLatestChatMessage(): ?OsceChatMessage
    {
        return $this->chatMessages()->latest('sent_at')->first();
    }

    public function orderedTests(): HasMany
    {
        return $this->hasMany(SessionOrderedTest::class, 'osce_session_id')->orderBy('ordered_at', 'desc');
    }

    public function examinations(): HasMany
    {
        return $this->hasMany(SessionExamination::class, 'osce_session_id')->orderBy('performed_at', 'desc');
    }

    public function getLabResults(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->orderedTests()->where('test_type', 'lab')->get();
    }

    public function getProcedureResults(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->orderedTests()->where('test_type', 'procedure')->get();
    }

    public function getPhysicalExamFindings(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->examinations()->get();
    }

    public function getDurationMinutesAttribute(): int
    {
        $case = $this->relationLoaded('osceCase') ? $this->osceCase : $this->osceCase()->first();
        $base = (int) ($case->duration_minutes ?? 30);
        $extension = (int) ($this->time_extended ?? 0);
        return max(0, $base + $extension);
    }

    public function getElapsedSecondsAttribute(): int
    {
        return $this->getActualElapsedSeconds();
    }

    public function getRemainingSecondsAttribute(): int
    {
        if ($this->status === 'completed') {
            return 0;
        }

        // If currently paused, return stored remaining seconds
        if ($this->isPaused()) {
            return max(0, (int) ($this->current_remaining_seconds ?? 0));
        }

        $durationSeconds = $this->duration_minutes * 60;
        $elapsedSeconds = $this->getActualElapsedSeconds();
        $remaining = max(0, $durationSeconds - $elapsedSeconds);
        return (int) $remaining;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->status !== 'completed' && $this->remaining_seconds <= 0;
    }

    public function getTimeStatusAttribute(): string
    {
        if ($this->status === 'completed') {
            return 'completed';
        }
        return $this->is_expired ? 'expired' : 'active';
    }

    public function markAsCompleted(?int $finalScore = null): void
    {
        if ($this->status !== 'completed') {
            $this->status = 'completed';
            if (!$this->completed_at) {
                $this->completed_at = now();
            }
            if (!is_null($finalScore)) {
                $this->score = $finalScore;
            }
            $this->save();
        }
    }

    public function isActive(): bool
    {
        return $this->status === 'in_progress' && !$this->is_expired;
    }

    public function canContinue(): bool
    {
        return $this->isActive();
    }

    /**
     * Get actual elapsed seconds accounting for paused time
     */
    public function getActualElapsedSeconds(): int
    {
        if (!$this->started_at) {
            return 0;
        }

        $totalElapsed = now()->diffInSeconds($this->started_at);
        $totalPausedSeconds = (int) ($this->total_paused_seconds ?? 0);

        // If currently paused, add the current pause duration
        if ($this->isPaused()) {
            $currentPauseDuration = now()->diffInSeconds($this->paused_at);
            $totalPausedSeconds += $currentPauseDuration;
        }

        return max(0, $totalElapsed - $totalPausedSeconds);
    }

    /**
     * Check if the timer is currently paused
     */
    public function isPaused(): bool
    {
        if (!$this->paused_at) {
            return false;
        }

        // If resumed_at is null or older than paused_at, then it's paused
        return !$this->resumed_at || $this->paused_at > $this->resumed_at;
    }

    /**
     * Pause the timer
     */
    public function pauseTimer(): void
    {
        if (!$this->isPaused() && $this->status === 'in_progress') {
            $this->current_remaining_seconds = $this->remaining_seconds;
            $this->paused_at = now();
            $this->save();
        }
    }

    /**
     * Resume the timer
     */
    public function resumeTimer(): void
    {
        if ($this->isPaused() && $this->status === 'in_progress') {
            $pauseDuration = now()->diffInSeconds($this->paused_at);
            $this->total_paused_seconds = ((int) ($this->total_paused_seconds ?? 0)) + $pauseDuration;
            $this->resumed_at = now();
            $this->current_remaining_seconds = null; // Clear stored remaining seconds
            $this->save();
        }
    }

    /**
     * Auto-pause timer (called when user leaves/refreshes page)
     */
    public function autoPauseTimer(): void
    {
        if (!$this->isPaused() && $this->status === 'in_progress') {
            $this->pauseTimer();
        }
    }

    /**
     * Auto-resume timer (called when user returns to page)
     */
    public function autoResumeTimer(): void
    {
        if ($this->isPaused() && $this->status === 'in_progress') {
            $this->resumeTimer();
        }
    }
}
