<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OSCESession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'case_id',
        'status',
        'started_at',
        'completed_at',
        'duration',
        'score',
        'checklist_progress',
        'conversation_log',
        'performance_data',
        'feedback'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'checklist_progress' => 'array',
        'conversation_log' => 'array',
        'performance_data' => 'array'
    ];

    // Relationships
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id', 'session_id');
    }

    public function osceCase(): BelongsTo
    {
        return $this->belongsTo(OSCECase::class, 'case_id');
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isAbandoned(): bool
    {
        return $this->status === 'abandoned';
    }

    public function getDurationInMinutes(): int
    {
        return $this->duration ? round($this->duration / 60) : 0;
    }

    public function getProgressPercentage(): float
    {
        if (empty($this->checklist_progress)) {
            return 0;
        }

        $totalItems = 0;
        $completedItems = 0;

        foreach ($this->checklist_progress as $category => $items) {
            foreach ($items as $item => $completed) {
                $totalItems++;
                if ($completed) {
                    $completedItems++;
                }
            }
        }

        return $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 1) : 0;
    }

    public function addConversationEntry(string $role, string $content, array $metadata = []): void
    {
        $log = $this->conversation_log ?? [];
        $log[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => now()->toISOString(),
            'metadata' => $metadata
        ];

        $this->update(['conversation_log' => $log]);
    }

    public function updateChecklistItem(string $category, string $item, bool $completed = true): void
    {
        $progress = $this->checklist_progress ?? [];
        
        if (!isset($progress[$category])) {
            $progress[$category] = [];
        }

        $progress[$category][$item] = $completed;
        $this->update(['checklist_progress' => $progress]);
    }

    public function markCompleted(float $score, string $feedback = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'duration' => $this->started_at->diffInSeconds(now()),
            'score' => $score,
            'feedback' => $feedback
        ]);
    }

    public function markAbandoned(): void
    {
        $this->update([
            'status' => 'abandoned',
            'completed_at' => now(),
            'duration' => $this->started_at->diffInSeconds(now())
        ]);
    }

    public function getCategoryProgress(string $category): array
    {
        return $this->checklist_progress[$category] ?? [];
    }

    public function getConversationSummary(): array
    {
        $log = $this->conversation_log ?? [];
        
        return [
            'total_exchanges' => count($log),
            'user_messages' => count(array_filter($log, fn($entry) => $entry['role'] === 'user')),
            'ai_responses' => count(array_filter($log, fn($entry) => $entry['role'] === 'assistant')),
            'duration' => $this->getDurationInMinutes()
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByCase($query, int $caseId)
    {
        return $query->where('case_id', $caseId);
    }
}