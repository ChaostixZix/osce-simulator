<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RationalizationEvaluation extends Model
{
    protected $fillable = [
        'session_rationalization_id',
        'evaluation_type',
        'section_name',
        'section_score',
        'strengths',
        'gaps',
        'top_fixes',
        'grounding_metadata',
        'search_queries',
        'model_used',
        'evaluation_started_at',
        'evaluation_completed_at',
        'has_citations',
        'citation_count'
    ];

    protected $casts = [
        'strengths' => 'array',
        'gaps' => 'array',
        'top_fixes' => 'array',
        'grounding_metadata' => 'array',
        'search_queries' => 'array',
        'has_citations' => 'boolean',
        'evaluation_started_at' => 'datetime',
        'evaluation_completed_at' => 'datetime'
    ];

    public function sessionRationalization(): BelongsTo
    {
        return $this->belongsTo(OsceSessionRationalization::class);
    }

    public function isAnamnesis(): bool
    {
        return $this->evaluation_type === 'anamnesis';
    }

    public function isInvestigations(): bool
    {
        return $this->evaluation_type === 'investigations';
    }

    public function isDiagnosis(): bool
    {
        return $this->evaluation_type === 'diagnosis';
    }

    public function isPlan(): bool
    {
        return $this->evaluation_type === 'plan';
    }

    public function getEvaluationDuration(): ?int
    {
        if ($this->evaluation_started_at && $this->evaluation_completed_at) {
            return $this->evaluation_started_at->diffInSeconds($this->evaluation_completed_at);
        }
        return null;
    }

    public function getPerformanceLevel(): string
    {
        $score = $this->section_score;
        if ($score >= 8) {
            return 'strong';
        } elseif ($score >= 6) {
            return 'satisfactory';
        } else {
            return 'needs_work';
        }
    }

    public function hasGroundingData(): bool
    {
        return !empty($this->grounding_metadata);
    }

    public function getSearchQueriesCount(): int
    {
        return count($this->search_queries ?? []);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('evaluation_type', $type);
    }

    public function scopeWithCitations($query)
    {
        return $query->where('has_citations', true);
    }
}
