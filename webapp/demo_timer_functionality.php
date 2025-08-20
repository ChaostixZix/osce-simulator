<?php
/**
 * OSCE Timer System Demo
 * 
 * This script demonstrates how the new simplified server-side timer system works.
 * It shows the core timer calculation logic without requiring a full Laravel environment.
 */

// Simulate the OsceSession model's timer calculation methods
class OsceSessionDemo
{
    public $id;
    public $user_id;
    public $osce_case_id;
    public $status;
    public $started_at;
    public $completed_at;
    public $time_extended;
    public $osce_case;
    
    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->user_id = $data['user_id'];
        $this->osce_case_id = $data['osce_case_id'];
        $this->status = $data['status'];
        $this->started_at = $data['started_at'];
        $this->completed_at = $data['completed_at'];
        $this->time_extended = $data['time_extended'] ?? 0;
        $this->osce_case = $data['osce_case'] ?? null;
    }
    
    /**
     * Calculate elapsed time in seconds since session started
     */
    public function getElapsedSecondsAttribute()
    {
        if (!$this->started_at) {
            return 0;
        }
        
        // Calculate elapsed time from started_at timestamp
        $elapsed = time() - strtotime($this->started_at);
        
        // Ensure we never return negative values
        return max(0, $elapsed);
    }
    
    /**
     * Calculate remaining time in seconds
     */
    public function getRemainingSecondsAttribute()
    {
        if ($this->status === 'completed') {
            return 0;
        }
        
        $durationSeconds = $this->getDurationMinutesAttribute() * 60;
        $elapsedSeconds = $this->getElapsedSecondsAttribute();
        
        // Calculate remaining time
        $remaining = max(0, $durationSeconds - $elapsedSeconds);
        
        return (int) $remaining;
    }
    
    /**
     * Get duration in minutes (base + extension)
     */
    public function getDurationMinutesAttribute()
    {
        $base = $this->osce_case ? $this->osce_case['duration_minutes'] : 25;
        $extension = (int) ($this->time_extended ?? 0);
        return max(0, $base + $extension);
    }
    
    /**
     * Check if session has expired
     */
    public function getIsExpiredAttribute()
    {
        return $this->status !== 'completed' && $this->getRemainingSecondsAttribute() <= 0;
    }
    
    /**
     * Get current time status
     */
    public function getTimeStatusAttribute()
    {
        if ($this->status === 'completed') {
            return 'completed';
        }
        return $this->getIsExpiredAttribute() ? 'expired' : 'active';
    }
    
    /**
     * Format remaining time as MM:SS
     */
    public function getFormattedTimeRemaining()
    {
        $seconds = max(0, $this->getRemainingSecondsAttribute());
        $mm = str_pad(floor($seconds / 60), 2, '0', STR_PAD_LEFT);
        $ss = str_pad($seconds % 60, 2, '0', STR_PAD_LEFT);
        return "{$mm}:{$ss}";
    }
    
    /**
     * Calculate progress percentage
     */
    public function getProgressPercentage()
    {
        $durationSeconds = $this->getDurationMinutesAttribute() * 60;
        $elapsedSeconds = $this->getElapsedSecondsAttribute();
        
        if ($durationSeconds <= 0) return 0;
        
        $progress = (($durationSeconds - $this->getRemainingSecondsAttribute()) / $durationSeconds) * 100;
        return round($progress, 1);
    }
}

// Demo data
$demoCase = [
    'id' => 1,
    'title' => 'Emergency Room Assessment',
    'duration_minutes' => 25,
    'description' => 'Assess a patient presenting with chest pain'
];

$demoSession = [
    'id' => 1,
    'user_id' => 1,
    'osce_case_id' => 1,
    'status' => 'in_progress',
    'started_at' => date('Y-m-d H:i:s', time() - (15 * 60)), // Started 15 minutes ago
    'completed_at' => null,
    'time_extended' => 0,
    'osce_case' => $demoCase
];

// Create demo session
$session = new OsceSessionDemo($demoSession);

echo "=== OSCE Timer System Demo ===\n\n";

echo "Session Details:\n";
echo "- Case: {$session->osce_case['title']}\n";
echo "- Started: {$session->started_at}\n";
echo "- Duration: {$session->getDurationMinutesAttribute()} minutes\n";
echo "- Status: {$session->status}\n";
echo "- Time Extended: {$session->time_extended} minutes\n\n";

echo "Timer Calculations:\n";
echo "- Elapsed Time: " . gmdate('i:s', $session->getElapsedSecondsAttribute()) . " ({$session->getElapsedSecondsAttribute()} seconds)\n";
echo "- Remaining Time: {$session->getFormattedTimeRemaining()} ({$session->getRemainingSecondsAttribute()} seconds)\n";
echo "- Progress: {$session->getProgressPercentage()}%\n";
echo "- Time Status: {$session->getTimeStatusAttribute()}\n";
echo "- Is Expired: " . ($session->getIsExpiredAttribute() ? 'Yes' : 'No') . "\n\n";

// Simulate different scenarios
echo "=== Scenario 1: Session with 5 minutes remaining ===\n";
$session5min = new OsceSessionDemo([
    'id' => 2,
    'user_id' => 1,
    'osce_case_id' => 1,
    'status' => 'in_progress',
    'started_at' => date('Y-m-d H:i:s', time() - (20 * 60)), // Started 20 minutes ago
    'completed_at' => null,
    'time_extended' => 0,
    'osce_case' => $demoCase
]);

echo "- Started: {$session5min->started_at}\n";
echo "- Elapsed: " . gmdate('i:s', $session5min->getElapsedSecondsAttribute()) . "\n";
echo "- Remaining: {$session5min->getFormattedTimeRemaining()}\n";
echo "- Progress: {$session5min->getProgressPercentage()}%\n";
echo "- Status: {$session5min->getTimeStatusAttribute()}\n\n";

echo "=== Scenario 2: Session with time extension ===\n";
$sessionExtended = new OsceSessionDemo([
    'id' => 3,
    'user_id' => 1,
    'osce_case_id' => 1,
    'status' => 'in_progress',
    'started_at' => date('Y-m-d H:i:s', time() - (20 * 60)), // Started 20 minutes ago
    'completed_at' => null,
    'time_extended' => 5, // 5 minutes extension
    'osce_case' => $demoCase
]);

echo "- Started: {$sessionExtended->started_at}\n";
echo "- Base Duration: {$demoCase['duration_minutes']} minutes\n";
echo "- Extension: {$sessionExtended->time_extended} minutes\n";
echo "- Total Duration: {$sessionExtended->getDurationMinutesAttribute()} minutes\n";
echo "- Elapsed: " . gmdate('i:s', $sessionExtended->getElapsedSecondsAttribute()) . "\n";
echo "- Remaining: {$sessionExtended->getFormattedTimeRemaining()}\n";
echo "- Progress: {$sessionExtended->getProgressPercentage()}%\n\n";

echo "=== Scenario 3: Expired session ===\n";
$sessionExpired = new OsceSessionDemo([
    'id' => 4,
    'user_id' => 1,
    'osce_case_id' => 1,
    'status' => 'in_progress',
    'started_at' => date('Y-m-d H:i:s', time() - (30 * 60)), // Started 30 minutes ago
    'completed_at' => null,
    'time_extended' => 0,
    'osce_case' => $demoCase
]);

echo "- Started: {$sessionExpired->started_at}\n";
echo "- Elapsed: " . gmdate('i:s', $sessionExpired->getElapsedSecondsAttribute()) . "\n";
echo "- Remaining: {$sessionExpired->getFormattedTimeRemaining()}\n";
echo "- Progress: {$sessionExpired->getProgressPercentage()}%\n";
echo "- Status: {$sessionExpired->getTimeStatusAttribute()}\n";
echo "- Is Expired: " . ($sessionExpired->getIsExpiredAttribute() ? 'Yes' : 'No') . "\n\n";

echo "=== Key Benefits of New System ===\n";
echo "✅ Timer persists after page refresh (calculated from started_at)\n";
echo "✅ Timer continues when navigating between pages\n";
echo "✅ No complex pause/resume logic needed\n";
echo "✅ Server-side calculation prevents client manipulation\n";
echo "✅ Automatic session completion when time expires\n";
echo "✅ Real-time countdown updates in dashboard\n\n";

echo "=== How It Works ===\n";
echo "1. User starts session → started_at timestamp recorded\n";
echo "2. Server calculates elapsed time: now() - started_at\n";
echo "3. Server calculates remaining time: duration - elapsed\n";
echo "4. Client receives accurate timer data via API\n";
echo "5. Client displays countdown and syncs every 10 seconds\n";
echo "6. Session auto-completes when time expires\n\n";

echo "Demo completed successfully! 🎉\n";