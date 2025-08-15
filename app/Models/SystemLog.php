<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'type',
        'level',
        'context',
        'message',
        'data',
        'user_agent',
        'ip_address'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    // Relationships
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id', 'session_id');
    }

    // Helper methods
    public function isError(): bool
    {
        return $this->type === 'error' || $this->level === 'error';
    }

    public function isCritical(): bool
    {
        return $this->level === 'critical';
    }

    public function isHealthCheck(): bool
    {
        return $this->type === 'health_check';
    }

    public function isApiCall(): bool
    {
        return $this->type === 'api_call';
    }

    public function isPerformanceLog(): bool
    {
        return $this->type === 'performance';
    }

    public function getFormattedMessage(): string
    {
        $timestamp = $this->created_at->format('Y-m-d H:i:s');
        $level = strtoupper($this->level);
        return "[$timestamp] $level: {$this->context} - {$this->message}";
    }

    // Static helper methods for creating logs
    public static function logError(string $sessionId, string $context, string $message, array $data = [], string $userAgent = null, string $ipAddress = null): self
    {
        return self::create([
            'session_id' => $sessionId,
            'type' => 'error',
            'level' => 'error',
            'context' => $context,
            'message' => $message,
            'data' => $data,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress
        ]);
    }

    public static function logHealthCheck(string $message, array $data = []): self
    {
        return self::create([
            'type' => 'health_check',
            'level' => 'info',
            'context' => 'System Health',
            'message' => $message,
            'data' => $data
        ]);
    }

    public static function logApiCall(string $sessionId, string $message, array $data = [], int $responseTime = null): self
    {
        $logData = $data;
        if ($responseTime) {
            $logData['response_time_ms'] = $responseTime;
        }

        return self::create([
            'session_id' => $sessionId,
            'type' => 'api_call',
            'level' => 'info',
            'context' => 'API Call',
            'message' => $message,
            'data' => $logData
        ]);
    }

    public static function logPerformance(string $sessionId, string $context, string $message, array $data = []): self
    {
        return self::create([
            'session_id' => $sessionId,
            'type' => 'performance',
            'level' => 'info',
            'context' => $context,
            'message' => $message,
            'data' => $data
        ]);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeErrors($query)
    {
        return $query->where('type', 'error')->orWhere('level', 'error');
    }

    public function scopeCritical($query)
    {
        return $query->where('level', 'critical');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeByContext($query, string $context)
    {
        return $query->where('context', 'like', "%$context%");
    }
}