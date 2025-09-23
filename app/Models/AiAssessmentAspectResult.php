<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAssessmentAspectResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_assessment_area_result_id',
        'aspect',
        'score',
        'max_score',
        'performance_level',
        'feedback',
        'citations',
    ];

    protected $casts = [
        'citations' => 'array',
        'score' => 'integer',
        'max_score' => 'integer',
    ];

    public function areaResult(): BelongsTo
    {
        return $this->belongsTo(AiAssessmentAreaResult::class, 'ai_assessment_area_result_id');
    }

    public function getPercentageAttribute(): float
    {
        return $this->max_score > 0 ? ($this->score / $this->max_score) * 100 : 0;
    }

    public function getPerformanceBadgeColorAttribute(): string
    {
        return match ($this->performance_level) {
            'good' => 'green',
            'acceptable' => 'yellow',
            'needs_improvement' => 'red',
            default => 'gray'
        };
    }

    public function getPerformanceBadgeTextAttribute(): string
    {
        return match ($this->performance_level) {
            'good' => 'Good',
            'acceptable' => 'Acceptable',
            'needs_improvement' => 'Needs Improvement',
            default => 'Unknown'
        };
    }
}