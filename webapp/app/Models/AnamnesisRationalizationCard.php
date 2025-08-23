<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnamnesisRationalizationCard extends Model
{
    protected $fillable = [
        'session_rationalization_id',
        'card_type',
        'question_text',
        'prompt_text',
        'user_rationale',
        'marked_as_forgot',
        'is_answered',
        'evaluation_summary',
        'verdict',
        'feedback_why',
        'score',
        'citations',
        'relevance_score',
        'evidence_accuracy_score',
        'completeness_score',
        'safety_score',
        'prioritization_score',
        'order_index',
        'answered_at',
        'evaluated_at',
    ];

    protected $casts = [
        'marked_as_forgot' => 'boolean',
        'is_answered' => 'boolean',
        'citations' => 'array',
        'answered_at' => 'datetime',
        'evaluated_at' => 'datetime',
    ];

    public function sessionRationalization(): BelongsTo
    {
        return $this->belongsTo(OsceSessionRationalization::class);
    }

    public function markAsAnswered(?string $rationale = null): void
    {
        $this->user_rationale = $rationale;
        $this->is_answered = true;
        $this->answered_at = now();
        $this->save();
    }

    public function markAsForgot(): void
    {
        $this->marked_as_forgot = true;
        $this->is_answered = true;
        $this->answered_at = now();
        $this->save();
    }

    public function isNegativeAnamnesis(): bool
    {
        return $this->card_type === 'negative_anamnesis';
    }

    public function isAskedQuestion(): bool
    {
        return $this->card_type === 'asked_question';
    }

    public function isInvestigation(): bool
    {
        return $this->card_type === 'investigation';
    }

    public function getTotalScore(): int
    {
        return $this->relevance_score +
               $this->evidence_accuracy_score +
               $this->completeness_score +
               $this->safety_score +
               $this->prioritization_score;
    }

    public function getVerdictColor(): string
    {
        return match ($this->verdict) {
            'correct' => 'green',
            'partially_correct' => 'yellow',
            'incorrect' => 'red',
            default => 'gray'
        };
    }

    public function hasEvaluation(): bool
    {
        return ! empty($this->evaluation_summary) && ! empty($this->verdict);
    }

    public function getCitationCount(): int
    {
        return count($this->citations ?? []);
    }
}
