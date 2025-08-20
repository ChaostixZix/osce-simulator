<?php

namespace App\Services;

use App\Models\Session;
use App\Models\SystemLog;
use Illuminate\Support\Str;

class SessionService
{
    /**
     * Create a new session
     */
    public function createSession(int $userId = null): Session
    {
        $sessionId = $this->generateSessionId();

        $session = Session::create([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'start_time' => now(),
            'metadata' => [
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
                'created_at' => now()->toISOString()
            ]
        ]);

        SystemLog::logPerformance(
            $sessionId,
            'Session Service',
            'New session created',
            [
                'user_id' => $userId,
                'ip_address' => request()->ip()
            ]
        );

        return $session;
    }

    /**
     * Get or create session by session ID
     */
    public function getOrCreateSession(string $sessionId, int $userId = null): Session
    {
        $session = Session::bySessionId($sessionId)->first();

        if (!$session) {
            $session = Session::create([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'start_time' => now(),
                'metadata' => [
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'created_at' => now()->toISOString()
                ]
            ]);

            SystemLog::logPerformance(
                $sessionId,
                'Session Service',
                'Session created from existing ID',
                ['user_id' => $userId]
            );
        }

        return $session;
    }

    /**
     * End a session
     */
    public function endSession(string $sessionId): array
    {
        $session = Session::bySessionId($sessionId)->first();
        
        if (!$session) {
            return [
                'success' => false,
                'error' => 'Session not found'
            ];
        }

        if (!$session->isActive()) {
            return [
                'success' => false,
                'error' => 'Session already ended'
            ];
        }

        $session->endSession();

        $summary = $this->getSessionSummary($sessionId);

        SystemLog::logPerformance(
            $sessionId,
            'Session Service',
            'Session ended',
            [
                'duration_minutes' => $summary['total_session_time'],
                'chat_messages' => $summary['chat_messages'],
                'osce_sessions' => $summary['osce_sessions_completed']
            ]
        );

        return [
            'success' => true,
            'summary' => $summary
        ];
    }

    /**
     * Get session summary statistics
     */
    public function getSessionSummary(string $sessionId): array
    {
        $session = Session::bySessionId($sessionId)->first();
        
        if (!$session) {
            return [];
        }

        $osceSessions = $session->osceSessions()->completed()->get();
        $totalOsceTime = $osceSessions->sum('duration');
        $averageOsceTime = $osceSessions->count() > 0 
            ? round($totalOsceTime / $osceSessions->count() / 60)
            : 0;

        $uniqueCases = $osceSessions->pluck('case_id')->unique()->count();
        $averageScore = $osceSessions->whereNotNull('score')->avg('score') ?? 0;

        $recentPerformance = $osceSessions->sortByDesc('completed_at')->take(3)->map(function ($osceSession) {
            return [
                'case_id' => $osceSession->osceCase->case_id,
                'score' => $osceSession->score,
                'duration_minutes' => $osceSession->getDurationInMinutes(),
                'completed_at' => $osceSession->completed_at->format('H:i')
            ];
        });

        return [
            'session_id' => $sessionId,
            'total_session_time' => $session->getDurationInMinutes(),
            'chat_messages' => $session->chat_messages,
            'osce_sessions_completed' => $session->osce_sessions_completed,
            'total_osce_time_minutes' => round($totalOsceTime / 60),
            'average_osce_time' => $averageOsceTime,
            'unique_cases_attempted' => $uniqueCases,
            'average_score' => round($averageScore, 1),
            'error_count' => $session->error_count,
            'recent_performance' => $recentPerformance->toArray(),
            'is_active' => $session->isActive(),
            'start_time' => $session->start_time->toISOString(),
            'end_time' => $session->end_time?->toISOString()
        ];
    }

    /**
     * Get system-wide statistics
     */
    public function getSystemStats(): array
    {
        $totalSessions = Session::count();
        $activeSessions = Session::active()->count();
        $totalChatMessages = Session::sum('chat_messages');
        $totalOsceSessions = Session::sum('osce_sessions_completed');
        $totalErrors = Session::sum('error_count');

        $averageSessionDuration = Session::whereNotNull('end_time')
            ->get()
            ->avg(function ($session) {
                return $session->start_time->diffInMinutes($session->end_time);
            });

        $recentSessions = Session::orderBy('start_time', 'desc')
            ->take(10)
            ->get()
            ->map(function ($session) {
                return [
                    'session_id' => $session->session_id,
                    'start_time' => $session->start_time->format('Y-m-d H:i'),
                    'duration_minutes' => $session->getDurationInMinutes(),
                    'chat_messages' => $session->chat_messages,
                    'osce_sessions' => $session->osce_sessions_completed,
                    'is_active' => $session->isActive()
                ];
            });

        return [
            'total_sessions' => $totalSessions,
            'active_sessions' => $activeSessions,
            'total_chat_messages' => $totalChatMessages,
            'total_osce_sessions' => $totalOsceSessions,
            'total_errors' => $totalErrors,
            'average_session_duration_minutes' => round($averageSessionDuration ?? 0),
            'recent_sessions' => $recentSessions->toArray()
        ];
    }

    /**
     * Clean up old sessions
     */
    public function cleanupOldSessions(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        $oldSessions = Session::where('start_time', '<', $cutoffDate)
            ->whereNotNull('end_time')
            ->get();

        $deletedCount = 0;
        
        foreach ($oldSessions as $session) {
            try {
                // This will cascade delete related records due to foreign key constraints
                $session->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                SystemLog::logError(
                    null,
                    'Session Service',
                    'Failed to delete old session: ' . $e->getMessage(),
                    ['session_id' => $session->session_id]
                );
            }
        }

        if ($deletedCount > 0) {
            SystemLog::logPerformance(
                null,
                'Session Service',
                "Cleaned up {$deletedCount} old sessions",
                ['days_old' => $daysOld, 'deleted_count' => $deletedCount]
            );
        }

        return $deletedCount;
    }

    /**
     * Get session health metrics
     */
    public function getSessionHealthMetrics(): array
    {
        $totalSessions = Session::count();
        $activeSessions = Session::active()->count();
        $sessionsWithErrors = Session::where('error_count', '>', 0)->count();
        
        $errorRate = $totalSessions > 0 ? round(($sessionsWithErrors / $totalSessions) * 100, 1) : 0;
        
        $averageResponseTime = SystemLog::byType('api_call')
            ->recent(24)
            ->whereNotNull('data->response_time_ms')
            ->get()
            ->avg(function ($log) {
                return $log->data['response_time_ms'] ?? 0;
            });

        $recentErrors = SystemLog::errors()
            ->recent(24)
            ->limit(5)
            ->get()
            ->map(function ($log) {
                return [
                    'context' => $log->context,
                    'message' => $log->message,
                    'time' => $log->created_at->format('H:i:s')
                ];
            });

        return [
            'total_sessions' => $totalSessions,
            'active_sessions' => $activeSessions,
            'error_rate_percentage' => $errorRate,
            'average_api_response_time_ms' => round($averageResponseTime ?? 0),
            'recent_errors' => $recentErrors->toArray(),
            'health_status' => $this->calculateHealthStatus($errorRate, $averageResponseTime ?? 0)
        ];
    }

    private function generateSessionId(): string
    {
        do {
            $sessionId = 'med_' . Str::random(16);
        } while (Session::where('session_id', $sessionId)->exists());

        return $sessionId;
    }

    private function calculateHealthStatus(float $errorRate, float $avgResponseTime): string
    {
        if ($errorRate > 10 || $avgResponseTime > 5000) {
            return 'unhealthy';
        } elseif ($errorRate > 5 || $avgResponseTime > 3000) {
            return 'degraded';
        } else {
            return 'healthy';
        }
    }
}