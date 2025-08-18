<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OsceSession;

class CleanupExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'osce:cleanup-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-complete OSCE sessions that have exceeded their allotted time';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expired OSCE sessions...');

        $expiredCount = 0;

        OsceSession::with('osceCase')
            ->where('status', 'in_progress')
            ->whereNotNull('started_at')
            ->chunkById(500, function ($sessions) use (&$expiredCount) {
                foreach ($sessions as $session) {
                    if ($session->is_expired) {
                        $session->markAsCompleted();
                        $expiredCount++;
                    }
                }
            });

        $this->info("Expired sessions completed: {$expiredCount}");

        return self::SUCCESS;
    }
}


