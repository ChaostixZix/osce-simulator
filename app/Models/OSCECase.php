<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OSCECase extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'title',
        'description',
        'category',
        'difficulty',
        'expected_duration',
        'patient_data',
        'checklist',
        'scoring_weights',
        'metadata',
        'is_active'
    ];

    protected $casts = [
        'patient_data' => 'array',
        'checklist' => 'array',
        'scoring_weights' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function osceSessions(): HasMany
    {
        return $this->hasMany(OSCESession::class, 'case_id');
    }

    // Helper methods
    public function getExpectedDurationInMinutes(): int
    {
        return round($this->expected_duration / 60);
    }

    public function getPatientInfo(): array
    {
        return $this->patient_data['patient'] ?? [];
    }

    public function getVitalSigns(): array
    {
        return $this->patient_data['vitals'] ?? [];
    }

    public function getPhysicalExam(): array
    {
        return $this->patient_data['physical_exam'] ?? [];
    }

    public function getLabResults(): array
    {
        return $this->patient_data['lab_results'] ?? [];
    }

    public function getImagingResults(): array
    {
        return $this->patient_data['imaging'] ?? [];
    }

    public function getChecklistCategories(): array
    {
        return array_keys($this->checklist);
    }

    public function getChecklistItems(string $category): array
    {
        return $this->checklist[$category] ?? [];
    }

    public function getTotalChecklistItems(): int
    {
        $total = 0;
        foreach ($this->checklist as $category => $items) {
            $total += count($items);
        }
        return $total;
    }

    public function calculateMaxScore(): float
    {
        $maxScore = 0;
        foreach ($this->scoring_weights as $category => $weight) {
            $itemCount = count($this->checklist[$category] ?? []);
            $maxScore += $itemCount * $weight;
        }
        return $maxScore;
    }

    public function getCompletionStats(): array
    {
        $totalSessions = $this->osceSessions()->count();
        $completedSessions = $this->osceSessions()->where('status', 'completed')->count();
        $averageScore = $this->osceSessions()
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->avg('score') ?? 0;

        return [
            'total_attempts' => $totalSessions,
            'completed_attempts' => $completedSessions,
            'completion_rate' => $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100, 1) : 0,
            'average_score' => round($averageScore, 1)
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeByCaseId($query, string $caseId)
    {
        return $query->where('case_id', $caseId);
    }
}