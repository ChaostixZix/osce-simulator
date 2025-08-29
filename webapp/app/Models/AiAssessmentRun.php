<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAssessmentRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'osce_session_id',
        'status',
        'final_result',
        'total_score',
        'max_possible_score',
        'error_message',
        'telemetry',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'final_result' => 'array',
        'telemetry' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function osceSession(): BelongsTo
    {
        return $this->belongsTo(OsceSession::class);
    }

    public function areaResults(): HasMany
    {
        return $this->hasMany(AiAssessmentAreaResult::class);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsFailedAttribute(): bool
    {
        return $this->status === 'failed';
    }

    public function getProgressPercentageAttribute(): int
    {
        $totalAreas = 5; // history, exam, investigations, differential_diagnosis, management
        $completedAreas = $this->areaResults()->whereIn('status', ['completed', 'fallback'])->count();
        
        return $totalAreas > 0 ? (int) (($completedAreas / $totalAreas) * 100) : 0;
    }

    public function getHasFallbacksAttribute(): bool
    {
        return $this->areaResults()->where('status', 'fallback')->exists();
    }

    public function getCompletedAreasAttribute(): int
    {
        return $this->areaResults()->whereIn('status', ['completed', 'fallback'])->count();
    }

    public function getTotalAreasAttribute(): int
    {
        return 5; // Fixed number of clinical areas
    }
}