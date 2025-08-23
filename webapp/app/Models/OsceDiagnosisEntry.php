<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsceDiagnosisEntry extends Model
{
    protected $fillable = [
        'session_rationalization_id',
        'diagnosis_name',
        'reasoning',
        'diagnosis_type',
        'order_index',
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
        'submitted_at',
        'evaluated_at'
    ];

    protected $casts = [
        'citations' => 'array',
        'submitted_at' => 'datetime',
        'evaluated_at' => 'datetime'
    ];

    public function sessionRationalization(): BelongsTo
    {
        return $this->belongsTo(OsceSessionRationalization::class);
    }

    public function isPrimary(): bool
    {
        return $this->diagnosis_type === 'primary';
    }

    public function isDifferential(): bool
    {
        return $this->diagnosis_type === 'differential';
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
        return match($this->verdict) {
            'correct' => 'green',
            'partially_correct' => 'yellow', 
            'incorrect' => 'red',
            default => 'gray'
        };
    }

    public function hasEvaluation(): bool
    {
        return !empty($this->evaluation_summary) && !empty($this->verdict);
    }

    public function getCitationCount(): int
    {
        return count($this->citations ?? []);
    }

    public function scopePrimary($query)
    {
        return $query->where('diagnosis_type', 'primary');
    }

    public function scopeDifferential($query)
    {
        return $query->where('diagnosis_type', 'differential');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }
}
