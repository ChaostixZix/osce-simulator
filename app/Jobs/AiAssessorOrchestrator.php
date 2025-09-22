<?php

namespace App\Jobs;

use App\Models\AiAssessmentAreaResult;
use App\Models\AiAssessmentRun;
use App\Models\OsceSession;
use App\Services\AreaAssessor;
use App\Services\AssessmentQueueService;
use App\Services\ResultReducer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class AiAssessorOrchestrator implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout
    public $tries = 1; // Only try once

    public function __construct(
        public int $sessionId,
        public bool $force = false,
        public ?int $runId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $session = OsceSession::findOrFail($this->sessionId);
        $queueService = app(AssessmentQueueService::class);
        
        Log::info('AiAssessorOrchestrator started', [
            'session_id' => $this->sessionId,
            'force' => $this->force,
            'run_id' => $this->runId
        ]);

        try {
            // Get or create assessment run
            if ($this->runId) {
                $assessmentRun = AiAssessmentRun::findOrFail($this->runId);
                // Mark as started
                $queueService->markAsStarted($assessmentRun->id, 'history');
            } else {
                // Create assessment run if not provided
                $assessmentRun = AiAssessmentRun::create([
                    'osce_session_id' => $this->sessionId,
                    'status' => 'in_progress',
                    'started_at' => now(),
                    'max_possible_score' => 85, // Sum of all clinical areas
                ]);
                
                // Mark as started
                $queueService->markAsStarted($assessmentRun->id, 'history');
            }

            // Initialize area results (idempotent for new runs)
            AiAssessmentAreaResult::initializeAreasForRun($assessmentRun->id);

            Log::info('Assessment run started (fan-out)', [
                'run_id' => $assessmentRun->id,
                'session_id' => $this->sessionId
            ]);
            // Fan-out: dispatch one job per clinical area so failures are isolated
            $areas = array_keys(AiAssessmentAreaResult::CLINICAL_AREAS);
            foreach ($areas as $area) {
                AssessAreaJob::dispatch($this->sessionId, $assessmentRun->id, $area)
                    ->onQueue('assessments');
            }

            // Schedule finalize job to aggregate when all areas finish
            FinalizeAssessmentRunJob::dispatch($assessmentRun->id)
                ->delay(now()->addSeconds(2))
                ->onQueue('assessments');

            Log::info('AiAssessorOrchestrator dispatched area jobs and finalize job', [
                'run_id' => $assessmentRun->id,
                'session_id' => $this->sessionId,
            ]);

        } catch (Exception $e) {
            Log::error('AiAssessorOrchestrator failed', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update assessment run as failed if it exists
            if (isset($assessmentRun)) {
                $assessmentRun->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
                
                // Mark as failed in queue service
                $queueService->markAsFailed($assessmentRun->id, $e->getMessage());
            }

            throw $e;
        }
    }

    // Progress calculation based on completed areas is provided by AiAssessmentRun accessors

    /**
     * Generate telemetry data for the assessment run
     */
    private function generateTelemetry(AiAssessmentRun $assessmentRun): array
    {
        $areaResults = $assessmentRun->areaResults;
        
        $telemetry = [
            'total_areas' => $areaResults->count(),
            'completed_areas' => $areaResults->where('status', 'completed')->count(),
            'fallback_areas' => $areaResults->where('status', 'fallback')->count(),
            'failed_areas' => $areaResults->where('status', 'failed')->count(),
            'areas_with_repairs' => $areaResults->where('was_repaired', true)->count(),
            'total_attempts' => $areaResults->sum('attempts'),
            'average_response_length' => $areaResults->where('response_length', '>', 0)->avg('response_length'),
            'processing_time_seconds' => $assessmentRun->completed_at?->diffInSeconds($assessmentRun->started_at),
            'area_breakdown' => [],
        ];

        foreach ($areaResults as $result) {
            $telemetry['area_breakdown'][$result->clinical_area] = [
                'status' => $result->status,
                'attempts' => $result->attempts,
                'was_repaired' => $result->was_repaired,
                'response_length' => $result->response_length,
                'score' => $result->score,
                'max_score' => $result->max_score,
            ];
        }

        return $telemetry;
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error('AiAssessorOrchestrator job failed', [
            'session_id' => $this->sessionId,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
