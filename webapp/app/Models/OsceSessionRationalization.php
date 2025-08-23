<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OsceSessionRationalization extends Model
{
    protected $fillable = [
        'osce_session_id',
        'status',
        'results_unlocked',
        'primary_diagnosis',
        'primary_diagnosis_reasoning',
        'differential_diagnoses',
        'care_plan',
        'anamnesis_score',
        'investigations_score',
        'diagnosis_score',
        'plan_score',
        'total_score',
        'performance_band',
        'strengths',
        'gaps',
        'top_fixes',
        'overall_summary',
        'suggested_study_topics',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'results_unlocked' => 'boolean',
        'differential_diagnoses' => 'array',
        'strengths' => 'array',
        'gaps' => 'array',
        'top_fixes' => 'array',
        'suggested_study_topics' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function osceSession(): BelongsTo
    {
        return $this->belongsTo(OsceSession::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(AnamnesisRationalizationCard::class, 'session_rationalization_id');
    }

    public function diagnosisEntries(): HasMany
    {
        return $this->hasMany(OsceDiagnosisEntry::class, 'session_rationalization_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(RationalizationEvaluation::class, 'session_rationalization_id');
    }

    public function getPrimaryDiagnosisEntry(): ?OsceDiagnosisEntry
    {
        return $this->diagnosisEntries()->where('diagnosis_type', 'primary')->first();
    }

    public function getDifferentialDiagnosisEntries()
    {
        return $this->diagnosisEntries()->where('diagnosis_type', 'differential')->orderBy('order_index')->get();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function canUnlockResults(): bool
    {
        if ($this->results_unlocked) {
            return true;
        }

        // Check if all required items are completed
        $allCardsAnswered = $this->cards()->where('is_answered', false)->count() === 0;
        $hasPrimaryDiagnosis = !empty($this->primary_diagnosis);
        $hasCarePlan = !empty($this->care_plan);
        $hasAtLeastOneDifferential = $this->diagnosisEntries()->where('diagnosis_type', 'differential')->count() > 0;

        return $allCardsAnswered && $hasPrimaryDiagnosis && $hasCarePlan && $hasAtLeastOneDifferential;
    }

    public function calculatePerformanceBand(): string
    {
        $totalScore = $this->total_score;
        
        if ($totalScore >= 8) {
            return 'strong';
        } elseif ($totalScore >= 6) {
            return 'satisfactory';
        } else {
            return 'needs_work';
        }
    }

    public function getCompletionProgress(): array
    {
        $totalCards = $this->cards()->count();
        $answeredCards = $this->cards()->where('is_answered', true)->count();
        
        return [
            'cards_completed' => $answeredCards,
            'cards_total' => $totalCards,
            'cards_percentage' => $totalCards > 0 ? round(($answeredCards / $totalCards) * 100, 1) : 0,
            'has_primary_diagnosis' => !empty($this->primary_diagnosis),
            'has_care_plan' => !empty($this->care_plan),
            'differential_count' => $this->diagnosisEntries()->where('diagnosis_type', 'differential')->count(),
            'can_unlock' => $this->canUnlockResults()
        ];
    }
}
