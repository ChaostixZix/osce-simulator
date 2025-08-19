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
        // 'started_at', // REMOVED: started_at should never be mass assignable to prevent timer resets
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
        if (!$this->started_at) {
            return 0;
        }
        
        // Ensure we're using the correct timezone and precision
        $now = now()->utc();
        $startedAt = $this->started_at->utc();
        
        return max(0, (int) $now->diffInSeconds($startedAt));
    }

    public function getRemainingSecondsAttribute(): int
    {
        if ($this->status === 'completed') {
            return 0;
        }
        
        $durationSeconds = $this->duration_minutes * 60;
        $elapsedSeconds = $this->elapsed_seconds;
        
        // Debug logging if time is going backwards (should never happen)
        if ($elapsedSeconds < 0) {
            \Log::error('OSCE Timer Bug: Negative elapsed time detected', [
                'session_id' => $this->id,
                'started_at' => $this->started_at?->toISOString(),
                'current_time' => now()->toISOString(),
                'elapsed_seconds' => $elapsedSeconds,
                'duration_minutes' => $this->duration_minutes
            ]);
            $elapsedSeconds = 0;
        }
        
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
     * Override save method to prevent started_at from being modified after creation
     * This prevents the timer reset bug where started_at gets accidentally updated
     */
    public function save(array $options = [])
    {
        // If this is an existing session and started_at is being changed, prevent it
        if ($this->exists && $this->isDirty('started_at') && $this->getOriginal('started_at')) {
            \Log::warning('Attempted to modify started_at on existing OSCE session', [
                'session_id' => $this->id,
                'original_started_at' => $this->getOriginal('started_at'),
                'new_started_at' => $this->started_at,
                'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
            ]);
            
            // Restore original started_at to prevent timer reset
            $this->started_at = $this->getOriginal('started_at');
        }
        
        return parent::save($options);
    }

    /**
     * Safely set started_at only during session creation
     */
    public function setStartedAt($timestamp): void
    {
        if ($this->exists && $this->started_at) {
            \Log::warning('Attempted to modify started_at on existing session', [
                'session_id' => $this->id,
                'current_started_at' => $this->started_at,
                'attempted_started_at' => $timestamp
            ]);
            return; // Ignore the update
        }
        
        $this->started_at = $timestamp;
    }

    /**
     * Check if the session is currently paused
     */
    public function isPaused(): bool
    {
        return !is_null($this->paused_at) && is_null($this->resumed_at);
    }

    /**
     * Auto-resume the timer when user returns to the page
     */
    public function autoResumeTimer(): void
    {
        if ($this->isPaused() && $this->status === 'in_progress') {
            $this->resumed_at = now();
            
            // Calculate total paused time and add to total_paused_seconds
            if ($this->paused_at) {
                $pausedDuration = now()->diffInSeconds($this->paused_at);
                $this->total_paused_seconds = ($this->total_paused_seconds ?? 0) + $pausedDuration;
            }
            
            $this->save();
        }
    }

    /**
     * Pause the session timer
     */
    public function pauseTimer(): void
    {
        if ($this->status === 'in_progress' && !$this->isPaused()) {
            $this->paused_at = now();
            $this->save();
        }
    }

    /**
     * Resume the session timer manually
     */
    public function resumeTimer(): void
    {
        if ($this->isPaused()) {
            $this->autoResumeTimer();
        }
    }
}
