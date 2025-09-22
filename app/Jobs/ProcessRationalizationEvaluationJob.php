<?php

namespace App\Jobs;

use App\Models\OsceSessionRationalization;
use App\Services\RationalizationEvaluationService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRationalizationEvaluationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The rationalization to evaluate
     */
    public OsceSessionRationalization $rationalization;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job should run.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(OsceSessionRationalization $rationalization)
    {
        $this->rationalization = $rationalization;
        $this->queue = 'evaluations'; // Use dedicated queue for evaluations
    }

    /**
     * Execute the job.
     */
    public function handle(RationalizationEvaluationService $evaluationService): void
    {
        Log::info('Starting rationalization evaluation job', [
            'rationalization_id' => $this->rationalization->id,
            'session_id' => $this->rationalization->osce_session_id,
        ]);

        try {
            // Update status to indicate evaluation is in progress
            $this->rationalization->update(['status' => 'in_progress']);

            // Perform the complete evaluation
            $results = $evaluationService->evaluateComplete($this->rationalization);

            // Mark as completed
            $this->rationalization->update([
                'status' => 'completed',
                'results_unlocked' => true,
                'completed_at' => now(),
            ]);

            Log::info('Completed rationalization evaluation job', [
                'rationalization_id' => $this->rationalization->id,
                'total_score' => $results['total_score'],
                'performance_band' => $results['performance_band'],
            ]);

        } catch (Exception $e) {
            Log::error('Rationalization evaluation job failed', [
                'rationalization_id' => $this->rationalization->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update status to indicate failure
            $this->rationalization->update(['status' => 'pending']);

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Rationalization evaluation job permanently failed', [
            'rationalization_id' => $this->rationalization->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Mark rationalization as completed but with error status
        $this->rationalization->update([
            'status' => 'completed',
            'results_unlocked' => true, // Allow results viewing even with partial evaluation
            'completed_at' => now(),
            'overall_summary' => 'Evaluation could not be completed due to technical issues. Manual review recommended.',
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 120, 300]; // 30 seconds, 2 minutes, 5 minutes
    }

    /**
     * Determine if the job should be retried based on the exception.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30); // Stop retrying after 30 minutes
    }
}
