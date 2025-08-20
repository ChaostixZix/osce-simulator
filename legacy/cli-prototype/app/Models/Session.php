<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'start_time',
        'end_time',
        'chat_messages',
        'osce_sessions_completed',
        'total_osce_time',
        'error_count',
        'metadata'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'metadata' => 'array'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'session_id', 'session_id');
    }

    public function osceSessions(): HasMany
    {
        return $this->hasMany(OSCESession::class, 'session_id', 'session_id');
    }

    public function systemLogs(): HasMany
    {
        return $this->hasMany(SystemLog::class, 'session_id', 'session_id');
    }

    // Helper methods
    public function isActive(): bool
    {
        return is_null($this->end_time);
    }

    public function getDurationInMinutes(): int
    {
        $endTime = $this->end_time ?? now();
        return $this->start_time->diffInMinutes($endTime);
    }

    public function getAverageOsceTime(): int
    {
        return $this->osce_sessions_completed > 0 
            ? round($this->total_osce_time / $this->osce_sessions_completed / 60) 
            : 0;
    }

    public function incrementChatMessages(): void
    {
        $this->increment('chat_messages');
    }

    public function trackOsceSession(int $duration, float $score): void
    {
        $this->increment('osce_sessions_completed');
        $this->increment('total_osce_time', $duration);
    }

    public function trackError(): void
    {
        $this->increment('error_count');
    }

    public function endSession(): void
    {
        $this->update(['end_time' => now()]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('end_time');
    }

    public function scopeBySessionId($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}