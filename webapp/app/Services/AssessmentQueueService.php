<?php

namespace App\Services;

use App\Models\AiAssessmentRun;
use App\Models\OsceSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AssessmentQueueService
{
    /**
     * Get queue position and estimated wait time for a session
     */
    public function getQueueStatus(int $sessionId): array
    {
        $assessmentRun = AiAssessmentRun::where('osce_session_id', $sessionId)
            ->latest()
            ->first();

        if (!$assessmentRun) {
            return [
                'status' => 'not_queued',
                'message' => 'No assessment run found'
            ];
        }

        // Update queue position if still queued or processing
        if (in_array($assessmentRun->status, ['queued', 'in_progress'])) {
            $this->updateQueuePositions();
        }

        return [
            'status' => $assessmentRun->status,
            'queue_position' => $assessmentRun->queue_position,
            'estimated_wait_time_minutes' => $assessmentRun->estimated_wait_time_minutes,
            'current_area' => $assessmentRun->current_area,
            'status_message' => $assessmentRun->status_message,
            'progress_percentage' => $assessmentRun->progress_percentage,
            'queued_at' => $assessmentRun->queued_at?->toISOString(),
            'started_at' => $assessmentRun->started_at?->toISOString(),
            'total_areas' => $assessmentRun->total_areas,
            'completed_areas' => $assessmentRun->completed_areas,
        ];
    }

    /**
     * Update queue positions for all queued assessments
     */
    public function updateQueuePositions(): void
    {
        // Get all queued assessments in chronological order
        $queuedRuns = AiAssessmentRun::where('status', 'queued')
            ->orderBy('created_at', 'asc')
            ->get();

        // Get currently processing assessments count
        $processingCount = AiAssessmentRun::where('status', 'in_progress')->count();

        // Average processing time per assessment (in minutes)
        $avgProcessingTime = $this->getAverageProcessingTime();

        foreach ($queuedRuns as $index => $run) {
            $position = $index + 1;
            $estimatedWaitTime = ($position + $processingCount - 1) * $avgProcessingTime;

            $run->update([
                'queue_position' => $position,
                'estimated_wait_time_minutes' => max(1, round($estimatedWaitTime)),
                'status_message' => $this->getStatusMessage($position, $estimatedWaitTime)
            ]);
        }

        Log::info('Queue positions updated', [
            'queued_count' => $queuedRuns->count(),
            'processing_count' => $processingCount,
            'avg_processing_time' => $avgProcessingTime
        ]);
    }

    /**
     * Mark assessment as started
     */
    public function markAsStarted(int $runId, string $currentArea = null): void
    {
        $run = AiAssessmentRun::find($runId);
        if (!$run) {
            return;
        }

        $run->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'current_area' => $currentArea,
            'status_message' => 'Assessment in progress' . ($currentArea ? " - analyzing {$currentArea}" : ''),
            'queue_position' => null,
            'estimated_wait_time_minutes' => null,
        ]);

        // Update queue positions for remaining items
        $this->updateQueuePositions();
        
        // Broadcast status update
        $this->broadcastStatusUpdate($run->osce_session_id);
    }

    /**
     * Update current processing area
     */
    public function updateCurrentArea(int $runId, string $currentArea): void
    {
        $run = AiAssessmentRun::find($runId);
        if (!$run) {
            return;
        }

        $run->update([
            'current_area' => $currentArea,
            'status_message' => "Analyzing {$currentArea}..."
        ]);

        // Broadcast status update
        $this->broadcastStatusUpdate($run->osce_session_id);
    }

    /**
     * Mark assessment as completed
     */
    public function markAsCompleted(int $runId): void
    {
        $run = AiAssessmentRun::find($runId);
        if (!$run) {
            return;
        }

        $run->update([
            'status' => 'completed',
            'completed_at' => now(),
            'current_area' => null,
            'status_message' => 'Assessment completed successfully',
            'queue_position' => null,
            'estimated_wait_time_minutes' => null,
        ]);

        // Update queue positions for remaining items
        $this->updateQueuePositions();
        
        // Broadcast completion
        $this->broadcastStatusUpdate($run->osce_session_id);
    }

    /**
     * Mark assessment as failed
     */
    public function markAsFailed(int $runId, string $errorMessage): void
    {
        $run = AiAssessmentRun::find($runId);
        if (!$run) {
            return;
        }

        $run->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'current_area' => null,
            'status_message' => 'Assessment failed: ' . $errorMessage,
            'queue_position' => null,
            'estimated_wait_time_minutes' => null,
        ]);

        // Update queue positions for remaining items
        $this->updateQueuePositions();
        
        // Broadcast failure
        $this->broadcastStatusUpdate($run->osce_session_id);
    }

    /**
     * Create a new assessment run in queue
     */
    public function enqueueAssessment(int $sessionId, bool $force = false): AiAssessmentRun
    {
        // Cancel any existing queued runs for this session if forcing
        if ($force) {
            AiAssessmentRun::where('osce_session_id', $sessionId)
                ->where('status', 'queued')
                ->update(['status' => 'cancelled']);
        }

        $run = AiAssessmentRun::create([
            'osce_session_id' => $sessionId,
            'status' => 'queued',
            'queued_at' => now(),
            'status_message' => 'Assessment queued for processing',
        ]);

        // Update queue positions
        $this->updateQueuePositions();
        
        // Broadcast new queue item
        $this->broadcastStatusUpdate($sessionId);

        return $run;
    }

    /**
     * Get average processing time for assessments
     */
    protected function getAverageProcessingTime(): float
    {
        // Cache the calculation for 5 minutes
        return Cache::remember('assessment_avg_processing_time', 300, function () {
            $completedRuns = AiAssessmentRun::where('status', 'completed')
                ->whereNotNull('started_at')
                ->whereNotNull('completed_at')
                ->orderBy('completed_at', 'desc')
                ->limit(50)
                ->get();

            if ($completedRuns->isEmpty()) {
                return 3.0; // Default 3 minutes
            }

            $totalMinutes = $completedRuns->sum(function ($run) {
                return $run->started_at->diffInMinutes($run->completed_at);
            });

            return max(1.0, $totalMinutes / $completedRuns->count());
        });
    }

    /**
     * Generate status message based on position and wait time
     */
    protected function getStatusMessage(int $position, float $waitTimeMinutes): string
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

    /**
     * Broadcast status update via Server-Sent Events
     */
    protected function broadcastStatusUpdate(int $sessionId): void
    {
        $status = $this->getQueueStatus($sessionId);
        
        // Store status in cache for SSE endpoint
        Cache::put("assessment_status_{$sessionId}", $status, 300);
        
        // Log for debugging
        Log::info('Assessment status updated', [
            'session_id' => $sessionId,
            'status' => $status['status'],
            'position' => $status['queue_position'] ?? null,
            'current_area' => $status['current_area'] ?? null,
        ]);
    }

    /**
     * Get all active queue items for admin view
     */
    public function getActiveQueue(): array
    {
        $queued = AiAssessmentRun::with('osceSession.user', 'osceSession.osceCase')
            ->where('status', 'queued')
            ->orderBy('created_at', 'asc')
            ->get();

        $processing = AiAssessmentRun::with('osceSession.user', 'osceSession.osceCase')
            ->where('status', 'in_progress')
            ->orderBy('started_at', 'asc')
            ->get();

        return [
            'queued' => $queued->map(function ($run) {
                return [
                    'id' => $run->id,
                    'session_id' => $run->osce_session_id,
                    'user_name' => $run->osceSession->user->name ?? 'Unknown',
                    'case_title' => $run->osceSession->osceCase->title ?? 'Unknown Case',
                    'queue_position' => $run->queue_position,
                    'estimated_wait_time_minutes' => $run->estimated_wait_time_minutes,
                    'queued_at' => $run->queued_at?->toISOString(),
                    'status_message' => $run->status_message,
                ];
            }),
            'processing' => $processing->map(function ($run) {
                return [
                    'id' => $run->id,
                    'session_id' => $run->osce_session_id,
                    'user_name' => $run->osceSession->user->name ?? 'Unknown',
                    'case_title' => $run->osceSession->osceCase->title ?? 'Unknown Case',
                    'current_area' => $run->current_area,
                    'progress_percentage' => $run->progress_percentage,
                    'started_at' => $run->started_at?->toISOString(),
                    'status_message' => $run->status_message,
                ];
            }),
            'summary' => [
                'queued_count' => $queued->count(),
                'processing_count' => $processing->count(),
                'avg_processing_time' => $this->getAverageProcessingTime(),
            ]
        ];
    }
}