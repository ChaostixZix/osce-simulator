<?php

namespace App\Jobs;

use App\Models\AiAssessmentRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateQueuePositionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('UpdateQueuePositionsJob started');

        try {
            // Get all queued assessments in one query
            $queuedRuns = AiAssessmentRun::where('status', 'queued')
                ->orderBy('created_at', 'asc')
                ->get(['id', 'created_at']);

            if ($queuedRuns->isEmpty()) {
                Log::info('No queued assessments to update');
                return;
            }

            // Get processing count
            $processingCount = AiAssessmentRun::where('status', 'in_progress')->count();

            // Get average processing time (cached)
            $avgProcessingTime = $this->getAverageProcessingTime();

            // Prepare bulk update data
            $updates = [];
            foreach ($queuedRuns as $index => $run) {
                $position = $index + 1;
                $estimatedWaitTime = ($position + $processingCount - 1) * $avgProcessingTime;
                
                $updates[] = [
                    'id' => $run->id,
                    'queue_position' => $position,
                    'estimated_wait_time_minutes' => max(1, round($estimatedWaitTime)),
                    'status_message' => $this->getStatusMessage($position, $estimatedWaitTime),
                    'updated_at' => now(),
                ];
            }

            // Bulk update using raw SQL for better performance
            $this->bulkUpdateQueuePositions($updates);

            Log::info('Queue positions updated successfully', [
                'queued_count' => $queuedRuns->count(),
                'processing_count' => $processingCount,
                'avg_processing_time' => $avgProcessingTime
            ]);

        } catch (\Exception $e) {
            Log::error('UpdateQueuePositionsJob failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Bulk update queue positions using efficient SQL
     */
    private function bulkUpdateQueuePositions(array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        // Use CASE statements for bulk update
        $whenClauses = [];
        $ids = [];
        
        foreach ($updates as $update) {
            $id = $update['id'];
            $ids[] = $id;
            $whenClauses['queue_position'][] = "WHEN id = {$id} THEN {$update['queue_position']}";
            $whenClauses['estimated_wait_time_minutes'][] = "WHEN id = {$id} THEN {$update['estimated_wait_time_minutes']}";
            $whenClauses['status_message'][] = "WHEN id = {$id} THEN " . DB::getPdo()->quote($update['status_message']);
        }

        $idsString = implode(',', $ids);
        $updatedAt = now()->toDateTimeString();

        $sql = "UPDATE ai_assessment_runs SET 
                    queue_position = CASE " . implode(' ', $whenClauses['queue_position']) . " END,
                    estimated_wait_time_minutes = CASE " . implode(' ', $whenClauses['estimated_wait_time_minutes']) . " END,
                    status_message = CASE " . implode(' ', $whenClauses['status_message']) . " END,
                    updated_at = '{$updatedAt}'
                WHERE id IN ({$idsString})";

        DB::statement($sql);
    }

    /**
     * Get average processing time with caching
     */
    private function getAverageProcessingTime(): float
    {
        return Cache::remember('assessment_avg_processing_time', 300, function () {
            $result = DB::table('ai_assessment_runs')
                ->where('status', 'completed')
                ->whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->orderBy('completed_at', 'desc')
                ->limit(50)
                ->selectRaw('AVG(strftime("%s", completed_at) - strftime("%s", started_at)) as avg_seconds')
                ->first();

            $avgSeconds = $result->avg_seconds ?? 180; // Default 3 minutes
            return max(60, $avgSeconds / 60); // Convert to minutes, minimum 1 minute
        });
    }

    /**
     * Generate status message based on position and wait time
     */
    private function getStatusMessage(int $position, float $waitTimeMinutes): string
    {
        if ($position === 1) {
            return 'Next in queue - starting soon';
        }

        $waitTime = round($waitTimeMinutes);
        if ($waitTime <= 1) {
            return "Position {$position} in queue - starting soon";
        }

        return "Position {$position} in queue - estimated wait: {$waitTime} minutes";
    }
}