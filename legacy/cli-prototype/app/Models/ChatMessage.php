<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'role',
        'content',
        'is_summarized',
        'summary',
        'tokens_used',
        'response_time_ms',
        'metadata'
    ];

    protected $casts = [
        'is_summarized' => 'boolean',
        'metadata' => 'array'
    ];

    // Relationships
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id', 'session_id');
    }

    // Helper methods
    public function isUserMessage(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistantMessage(): bool
    {
        return $this->role === 'assistant';
    }

    public function isSystemMessage(): bool
    {
        return $this->role === 'system';
    }

    public function markAsSummarized(string $summary): void
    {
        $this->update([
            'is_summarized' => true,
            'summary' => $summary
        ]);
    }

    public function getDisplayContent(): string
    {
        return $this->is_summarized && $this->summary ? $this->summary : $this->content;
    }

    // Scopes
    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeUnsummarized($query)
    {
        return $query->where('is_summarized', false);
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public function scopeOldest($query, int $limit = 6)
    {
        return $query->orderBy('created_at', 'asc')->limit($limit);
    }
}