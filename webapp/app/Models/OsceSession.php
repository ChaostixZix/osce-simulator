<?php

namespace App\Models;

use App\Jobs\AssessOsceSessionJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for a single OSCE session.
 *
 * Timing is driven entirely by the `started_at` timestamp – we never store a
 * "remaining" field in the database. On every request the model calculates the
 * elapsed and remaining seconds from that timestamp. Because the value is
 * immutable after the session is created (see overridden `save()` below), a
 * user refreshing or navigating away from the page cannot reset the countdown.
 */
class OsceSession extends Model
{
    protected $fillable = [
        'user_id',
        'osce_case_id',
        'status',
        'completed_at',
        'score',
        'max_score',
        'time_extended',
        'clinical_reasoning_score',
        'total_test_cost',
        'evaluation_feedback',
        'responses',
        'feedback',
        'assessor_payload',
        'assessor_output',
        'assessed_at',
        'assessor_model',
        'rubric_version'
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
        'rationalization_completed_at' => 'datetime',
        'assessed_at' => 'datetime',
        'time_extended' => 'integer',
        'responses' => 'array',
        'feedback' => 'array',
        'evaluation_feedback' => 'array',
        'assessor_payload' => 'array',
        'assessor_output' => 'array'
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
        // Use the case's configured duration, do not hardcode defaults
        $base = (int) ($case?->duration_minutes ?? 0);
        $extension = (int) ($this->time_extended ?? 0);
        return max(0, $base + $extension);
    }

    /**
     * Calculate elapsed time in seconds since session started
     * This is the core method that ensures timer persistence
     */
    public function getElapsedSecondsAttribute(): int
    {
        // Fallback to created_at if started_at is not set (defensive for legacy rows)
        $startedBase = $this->started_at ?? $this->created_at;
        if (!$startedBase) {
            return 0;
        }

        // Use UTC to avoid timezone drift between DB/app servers
        $now = now()->utc();
        $startedAt = $startedBase->utc();

        // Signed diff to guard against future-dated started_at, clamped to 0
        return max(0, (int) $startedAt->diffInSeconds($now, false));
    }

    /**
     * Calculate remaining time in seconds
     * This is calculated server-side and cannot be manipulated by client
     */
    public function getRemainingSecondsAttribute(): int
    {
        if ($this->status === 'completed') {
            return 0;
        }
        
        $durationSeconds = $this->duration_minutes * 60;
        $elapsedSeconds = $this->elapsed_seconds;
        
        // Calculate remaining time
        $remaining = max(0, $durationSeconds - $elapsedSeconds);
        
        // Log if timer expires
        if ($remaining === 0 && $this->status === 'in_progress') {
            \Log::info('OSCE Session expired', [
                'session_id' => $this->id,
                'started_at' => $this->started_at?->toISOString(),
                'duration_minutes' => $this->duration_minutes,
                'elapsed_seconds' => $elapsedSeconds
            ]);
        }
        
        return (int) $remaining;
    }

    /**
     * Check if session has expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->status !== 'completed' && $this->remaining_seconds <= 0;
    }

    /**
     * Get current time status
     */
    public function getTimeStatusAttribute(): string
    {
        if ($this->status === 'completed') {
            return 'completed';
        }
        return $this->is_expired ? 'expired' : 'active';
    }

    /**
     * Mark session as completed
     */
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
            
            \Log::info('OSCE Session completed', [
                'session_id' => $this->id,
                'final_score' => $finalScore,
                'completed_at' => $this->completed_at?->toISOString()
            ]);

            // Dispatch assessment job if not already assessed
            if (!$this->assessed_at) {
                AssessOsceSessionJob::dispatch($this->id);
            }
        }
    }

    /**
     * Check if session is currently active
     */
    public function isActive(): bool
    {
        return $this->status === 'in_progress' && !$this->is_expired;
    }

    /**
     * Check if session can be continued
     */
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
     * Auto-complete expired sessions
     */
    public function autoCompleteIfExpired(): void
    {
        if ($this->is_expired && $this->status === 'in_progress') {
            $this->markAsCompleted();
        }
    }

    /**
     * Single source of truth for "rationalization complete" gating.
     *
     * In this project, the OSCE rationalization step is considered complete
     * when the session itself has been completed (status === 'completed').
     * This accessor is used by controllers/middleware to guard access to
     * viewing assessment results and any subsequent scoring views.
     */
    public function getIsRationalizationCompleteAttribute(): bool
    {
        return (bool) $this->rationalization_completed_at;
    }
}
