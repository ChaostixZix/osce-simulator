<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpacedRepetitionCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'osce_case_id',
        'clinical_area',
        'card_content',
        'repetition_level',
        'easiness_factor',
        'next_review_date',
        'last_reviewed_at',
        'review_count',
        'created_from_session'
    ];

    protected $casts = [
        'card_content' => 'array',
        'next_review_date' => 'datetime',
        'last_reviewed_at' => 'datetime',
        'repetition_level' => 'integer',
        'review_count' => 'integer',
        'easiness_factor' => 'float'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function osceCase(): BelongsTo
    {
        return $this->belongsTo(OsceCase::class);
    }

    public function createdFromSession(): BelongsTo
    {
        return $this->belongsTo(OsceSession::class, 'created_from_session');
    }

    /**
     * Check if card is due for review
     */
    public function isDue(): bool
    {
        return $this->next_review_date <= now();
    }

    /**
     * Check if card is overdue
     */
    public function isOverdue(): bool
    {
        return $this->next_review_date < now()->subDay();
    }

    /**
     * Get difficulty level
     */
    public function getDifficulty(): string
    {
        return $this->card_content['difficulty'] ?? 'medium';
    }

    /**
     * Get question text
     */
    public function getQuestion(): string
    {
        return $this->card_content['question'] ?? '';
    }

    /**
     * Get answer text
     */
    public function getAnswer(): string
    {
        return $this->card_content['answer'] ?? '';
    }

    /**
     * Get explanation
     */
    public function getExplanation(): ?string
    {
        return $this->card_content['explanation'] ?? null;
    }

    /**
     * Get tags
     */
    public function getTags(): array
    {
        return $this->card_content['tags'] ?? [];
    }

    /**
     * Scope for due cards
     */
    public function scopeDue($query)
    {
        return $query->where('next_review_date', '<=', now());
    }

    /**
     * Scope for overdue cards
     */
    public function scopeOverdue($query)
    {
        return $query->where('next_review_date', '<', now()->subDay());
    }

    /**
     * Scope for specific clinical area
     */
    public function scopeForArea($query, string $area)
    {
        return $query->where('clinical_area', $area);
    }
}