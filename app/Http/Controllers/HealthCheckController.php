<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthCheckController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Basic health check endpoint
     */
    public function basic()
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => app()->version(),
        ]);
    }

    /**
     * Detailed health check with authentication status
     */
    public function detailed()
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => app()->version(),
            'checks' => [],
        ];

        // Check database connection
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            $health['checks']['database'] = [
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
            ];
            $health['status'] = 'degraded';
        }

        // Check cache
        try {
            Cache::put('health-check', 'ok', 10);
            $health['checks']['cache'] = [
                'status' => 'healthy',
                'message' => 'Cache connection successful',
            ];
        } catch (\Exception $e) {
            $health['checks']['cache'] = [
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
            ];
            $health['status'] = 'degraded';
        }

        // Check Supabase connection (if enabled)
        if (config('auth.use_supabase') || config('auth.supabase_dual_mode')) {
            try {
                // Test with a simple API call
                $response = $this->supabase->listUsers(1, 1);
                $health['checks']['supabase'] = [
                    'status' => 'healthy',
                    'message' => 'Supabase connection successful',
                ];
            } catch (\Exception $e) {
                $health['checks']['supabase'] = [
                    'status' => 'unhealthy',
                    'message' => $e->getMessage(),
                ];
                $health['status'] = 'degraded';
            }
        }

        // Check authentication mode
        $health['authentication'] = [
            'mode' => $this->getAuthMode(),
            'use_supabase' => config('auth.use_supabase', false),
            'dual_mode' => config('auth.supabase_dual_mode', true),
        ];

        // Get migration statistics
        $health['migration'] = $this->getMigrationStats();

        return response()->json($health);
    }

    /**
     * Authentication-specific health check
     */
    public function authentication()
    {
        $stats = [
            'timestamp' => now()->toISOString(),
            'auth_mode' => $this->getAuthMode(),
            'metrics' => [],
        ];

        // Get authentication metrics
        $stats['metrics'] = $this->getAuthMetrics();

        // Check recent authentication failures
        $recentFailures = Log::channel()
            ->when(now()->subHours(24), function ($query) {
                return $query->where('created_at', '>=', now()->subHours(24));
            })
            ->where('level', 'error')
            ->where('message', 'like', '%authentication%')
            ->count();

        $stats['metrics']['recent_failures_24h'] = $recentFailures;

        // Get active sessions
        $stats['metrics']['active_sessions'] = DB::table('sessions')
            ->where('last_activity', '>=', now()->subHours(2))
            ->count();

        return response()->json($stats);
    }

    /**
     * Migration-specific health check
     */
    public function migration()
    {
        $stats = $this->getMigrationStats();
        
        // Get migration progress
        $stats['progress'] = [
            'percentage' => $stats['total'] > 0 ? round(($stats['migrated'] / $stats['total']) * 100, 2) : 0,
            'estimated_completion' => $this->estimateMigrationCompletion(),
        ];

        // Get recent migration activity
        $stats['recent_activity'] = DB::table('users')
            ->where('is_migrated', true)
            ->where('updated_at', '>=', now()->subHours(24))
            ->count();

        // Check for migration errors
        $migrationErrors = Log::channel()
            ->where('created_at', '>=', now()->subHours(24))
            ->where('level', 'error')
            ->where('message', 'like', '%migration%')
            ->count();

        $stats['errors_last_24h'] = $migrationErrors;

        return response()->json($stats);
    }

    /**
     * Get authentication mode
     */
    protected function getAuthMode()
    {
        if (config('auth.use_supabase')) {
            return 'supabase-only';
        } elseif (config('auth.supabase_dual_mode')) {
            return 'dual-mode';
        } else {
            return 'supabase-only';
        }
    }

    /**
     * Get migration statistics
     */
    protected function getMigrationStats()
    {
        $stats = DB::table('users')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(is_migrated) as migrated')
            ->selectRaw('sum(case when supabase_id is not null then 1 else 0 end) as has_supabase_id')
            ->first();

        return [
            'total' => (int) $stats->total,
            'migrated' => (int) $stats->migrated,
            'has_supabase_id' => (int) $stats->has_supabase_id,
            'pending' => (int) ($stats->total - $stats->migrated),
        ];
    }

    /**
     * Get authentication metrics
     */
    protected function getAuthMetrics()
    {
        // This would ideally use a metrics database like Prometheus
        // For now, we'll return basic metrics
        return [
            'logins_last_24h' => $this->getLoginsCount(24),
            'logins_last_hour' => $this->getLoginsCount(1),
            'failed_logins_last_24h' => $this->getFailedLoginsCount(24),
            'oauth_logins_last_24h' => $this->getOAuthLoginsCount(24),
            'token_refreshes_last_24h' => $this->getTokenRefreshesCount(24),
        ];
    }

    /**
     * Get login count for time period
     */
    protected function getLoginsCount($hours)
    {
        // This would track from your analytics or logs
        // For now, return placeholder
        return Cache::remember("logins_{$hours}h", 300, function () {
            return rand(50, 200); // Placeholder
        });
    }

    /**
     * Get failed login count
     */
    protected function getFailedLoginsCount($hours)
    {
        return Cache::remember("failed_logins_{$hours}h", 300, function () {
            return rand(5, 20); // Placeholder
        });
    }

    /**
     * Get OAuth login count
     */
    protected function getOAuthLoginsCount($hours)
    {
        return Cache::remember("oauth_logins_{$hours}h", 300, function () {
            return rand(10, 50); // Placeholder
        });
    }

    /**
     * Get token refresh count
     */
    protected function getTokenRefreshesCount($hours)
    {
        return Cache::remember("token_refreshes_{$hours}h", 300, function () {
            return rand(20, 100); // Placeholder
        });
    }

    /**
     * Estimate migration completion time
     */
    protected function estimateMigrationCompletion()
    {
        $pending = DB::table('users')
            ->where('is_migrated', false)
            ->count();

        if ($pending === 0) {
            return 'Completed';
        }

        // Get recent migration rate
        $recentMigrations = DB::table('users')
            ->where('is_migrated', true)
            ->where('updated_at', '>=', now()->subHours(1))
            ->count();

        if ($recentMigrations === 0) {
            return 'Unknown';
        }

        $hoursNeeded = ceil($pending / $recentMigrations);
        
        if ($hoursNeeded < 1) {
            return 'Less than 1 hour';
        } elseif ($hoursNeeded < 24) {
            return "About {$hoursNeeded} hours";
        } else {
            $days = round($hoursNeeded / 24, 1);
            return "About {$days} days";
        }
    }
}