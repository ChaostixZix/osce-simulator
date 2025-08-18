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
        'feedback'
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
        'time_extended' => 'integer',
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
        return now()->diffInSeconds($this->started_at);
    }

    public function getRemainingSecondsAttribute(): int
    {
        if ($this->status === 'completed') {
            return 0;
        }
        $durationSeconds = $this->duration_minutes * 60;
        $remaining = max(0, $durationSeconds - $this->elapsed_seconds);
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
}
