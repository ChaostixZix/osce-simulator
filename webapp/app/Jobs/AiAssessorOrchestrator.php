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

            // Initialize area results
            AiAssessmentAreaResult::initializeAreasForRun($assessmentRun->id);

            Log::info('Assessment run started', [
                'run_id' => $assessmentRun->id,
                'session_id' => $this->sessionId
            ]);

            // Process each clinical area sequentially
            $areaAssessor = app(AreaAssessor::class);
            $areas = AiAssessmentAreaResult::CLINICAL_AREAS;

            foreach ($areas as $area => $config) {
                try {
                    Log::info('Processing clinical area', [
                        'run_id' => $assessmentRun->id,
                        'area' => $area
                    ]);

                    // Update queue status with current area (lightweight update)
                    $this->updateAreaQuietly($assessmentRun, $area);

                    $areaResult = $assessmentRun->areaResults()
                        ->where('clinical_area', $area)
                        ->firstOrFail();

                    $areaResult->update(['status' => 'in_progress']);

                    // Process the area
                    $result = $areaAssessor->assessArea($session, $area, $areaResult);
                    
                    Log::info('Area processing completed', [
                        'run_id' => $assessmentRun->id,
                        'area' => $area,
                        'status' => $result['status'],
                        'score' => $result['score'] ?? null
                    ]);

                } catch (Exception $e) {
                    Log::error('Area processing failed', [
                        'run_id' => $assessmentRun->id,
                        'area' => $area,
                        'error' => $e->getMessage()
                    ]);

                    $assessmentRun->areaResults()
                        ->where('clinical_area', $area)
                        ->update([
                            'status' => 'failed',
                            'error_message' => $e->getMessage(),
                        ]);
                }
            }

            // Aggregate results using ResultReducer
            $resultReducer = app(ResultReducer::class);
            $finalResult = $resultReducer->aggregateResults($assessmentRun);

            // Update assessment run with final results
            $assessmentRun->update([
                'status' => 'completed',
                'completed_at' => now(),
                'final_result' => $finalResult,
                'total_score' => $finalResult['total_score'] ?? 0,
                'telemetry' => $this->generateTelemetry($assessmentRun),
            ]);

            // Mark as completed in queue service
            $queueService->markAsCompleted($assessmentRun->id);

            Log::info('AiAssessorOrchestrator completed', [
                'run_id' => $assessmentRun->id,
                'session_id' => $this->sessionId,
                'total_score' => $finalResult['total_score'] ?? 0,
                'has_fallbacks' => $assessmentRun->has_fallbacks
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

    /**
     * Update current area without triggering heavy operations
     */
    private function updateAreaQuietly(AiAssessmentRun $assessmentRun, string $area): void
    {
        // Direct database update without triggering events
        $assessmentRun->update([
            'current_area' => $area,
            'status_message' => "Analyzing {$area}...",
            'progress_percentage' => $this->calculateProgress($area),
        ]);
        
        // Lightweight cache update for status polling
        Cache::put("assessment_area_{$assessmentRun->osce_session_id}", [
            'current_area' => $area,
            'updated_at' => now()->toISOString(),
        ], 300);
    }
    
    /**
     * Calculate progress percentage based on current area
     */
    private function calculateProgress(string $currentArea): int
    {
        $areas = array_keys(AiAssessmentAreaResult::CLINICAL_AREAS);
        $currentIndex = array_search($currentArea, $areas);
        
        if ($currentIndex === false) {
            return 0;
        }
        
        return (int) round((($currentIndex + 1) / count($areas)) * 100);
    }

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
