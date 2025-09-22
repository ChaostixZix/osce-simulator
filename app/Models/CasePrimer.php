<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CasePrimer extends Model
{
    use HasFactory;

    protected $fillable = [
        'osce_case_id',
        'primer_data',
        'user_level',
        'focus_areas',
        'options_hash',
        'generated_at',
        'usage_count'
    ];

    protected $casts = [
        'primer_data' => 'array',
        'focus_areas' => 'array',
        'generated_at' => 'datetime',
        'usage_count' => 'integer'
    ];

    public function osceCase(): BelongsTo
    {
        return $this->belongsTo(OsceCase::class);
    }

    /**
     * Increment usage count
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get complexity rating with fallback
     */
    public function getComplexityRating(): string
    {
        return $this->primer_data['complexity_rating'] ?? 'intermediate';
    }

    /**
     * Get clinical overview section
     */
    public function getClinicalOverview(): array
    {
        return $this->primer_data['clinical_overview'] ?? [];
    }

    /**
     * Get investigation strategy
     */
    public function getInvestigationStrategy(): array
    {
        return $this->primer_data['investigation_strategy'] ?? [];
    }

    /**
     * Get common pitfalls
     */
    public function getCommonPitfalls(): array
    {
        return $this->primer_data['common_pitfalls'] ?? [];
    }

    /**
     * Check if this is a fresh primer (generated recently)
     */
    public function isFresh(): bool
    {
        return $this->generated_at && $this->generated_at->gt(now()->subHours(24));
    }
}