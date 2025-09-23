<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAssessmentAreaResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_assessment_run_id',
        'clinical_area',
        'status',
        'score',
        'max_score',
        'justification',
        'raw_response',
        'response_length',
        'attempts',
        'was_repaired',
        'error_message',
        'telemetry',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'telemetry' => 'array',
        'was_repaired' => 'boolean',
    ];

    public const CLINICAL_AREAS = [
        'history' => ['name' => 'History-Taking', 'max_score' => 20, 'aspects' => ['systematic_approach', 'question_quality', 'thoroughness']],
        'exam' => ['name' => 'Physical Examination', 'max_score' => 15, 'aspects' => ['technique', 'systematic_approach', 'critical_exams']],
        'investigations' => ['name' => 'Investigations', 'max_score' => 20, 'aspects' => ['appropriateness', 'cost_effectiveness', 'sequencing']],
        'differential_diagnosis' => ['name' => 'Differential Diagnosis', 'max_score' => 15, 'aspects' => ['breadth', 'reasoning', 'prioritization']],
        'management' => ['name' => 'Management', 'max_score' => 15, 'aspects' => ['immediate_actions', 'treatment_plan', 'follow_up']],
        'communication' => ['name' => 'Communication Skills', 'max_score' => 10, 'aspects' => ['clarity', 'empathy', 'professionalism']],
        'safety' => ['name' => 'Safety & Professionalism', 'max_score' => 10, 'aspects' => ['error_prevention', 'time_management', 'documentation']],
    ];

    public function assessmentRun(): BelongsTo
    {
        return $this->belongsTo(AiAssessmentRun::class, 'ai_assessment_run_id');
    }

    public function getIsCompletedAttribute(): bool
    {
        return in_array($this->status, ['completed', 'fallback']);
    }

    public function getIsFailedAttribute(): bool
    {
        return $this->status === 'failed';
    }

    public function getIsFallbackAttribute(): bool
    {
        return $this->status === 'fallback';
    }

    public function getAreaDisplayNameAttribute(): string
    {
        return self::CLINICAL_AREAS[$this->clinical_area]['name'] ?? $this->clinical_area;
    }

    public function getBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => $this->was_repaired ? 'amber' : 'green',
            'fallback' => 'gray',
            'failed' => 'red',
            'in_progress' => 'blue',
            default => 'gray'
        };
    }

    public function getBadgeTextAttribute(): string
    {
        return match ($this->status) {
            'completed' => $this->was_repaired ? 'AI (Repaired)' : 'AI',
            'fallback' => 'Rubric',
            'failed' => 'Failed',
            'in_progress' => 'Processing',
            default => 'Pending'
        };
    }

    public function getMaxScoreForArea(string $area): int
    {
        return self::CLINICAL_AREAS[$area]['max_score'] ?? 10;
    }

    public static function initializeAreasForRun(int $assessmentRunId): void
    {
        foreach (self::CLINICAL_AREAS as $area => $config) {
            self::create([
                'ai_assessment_run_id' => $assessmentRunId,
                'clinical_area' => $area,
                'max_score' => $config['max_score'],
                'status' => 'pending',
            ]);
        }
    }
}