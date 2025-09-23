<?php

namespace App\Jobs;

use App\Jobs\AssessAreaJob;
use App\Models\AiAssessmentRun;
use App\Models\OsceSession;
use App\Services\AiAssessorService;
use App\Services\AssessmentPromptManager;
use App\Services\MultiPromptAreaAssessor;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MultiPromptAssessAreaJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public OsceSession $session,
        public string $area,
        public int $areaResultId,
        public bool $useMultiPrompt = true
    ) {
        $this->onQueue('assessments');
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // If this job is part of a batch that was cancelled, skip processing
        if ($this->batch()?->cancelled()) {
            Log::info('MultiPromptAssessAreaJob skipped due to batch cancellation', [
                'session_id' => $this->session->id,
                'area' => $this->area,
                'area_result_id' => $this->areaResultId
            ]);
            return;
        }

        Log::info('MultiPromptAssessAreaJob starting', [
            'session_id' => $this->session->id,
            'area' => $this->area,
            'area_result_id' => $this->areaResultId,
            'use_multi_prompt' => $this->useMultiPrompt
        ]);

        try {
            // Find the area result
            $areaResult = \App\Models\AiAssessmentAreaResult::findOrFail($this->areaResultId);
            
            // Update status to in_progress
            $areaResult->update(['status' => 'in_progress']);
            
            if ($this->useMultiPrompt) {
                // Use the new multi-prompt assessor
                $multiPromptAssessor = app(MultiPromptAreaAssessor::class);
                $result = $multiPromptAssessor->assessArea($this->session, $this->area, $areaResult);
            } else {
                // Fallback to original single-prompt assessor
                $assessor = app(\App\Services\AreaAssessor::class);
                $result = $assessor->assessArea($this->session, $this->area, $areaResult);
            }
            
            Log::info('MultiPromptAssessAreaJob completed', [
                'session_id' => $this->session->id,
                'area' => $this->area,
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('MultiPromptAssessAreaJob failed', [
                'session_id' => $this->session->id,
                'area' => $this->area,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Update area result status to failed
            if (isset($areaResult)) {
                $areaResult->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }
            
            throw $e;
        }
    }
    
    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'assessment',
            'multi-prompt',
            'area:' . $this->area,
            'session:' . $this->session->id,
        ];
    }
}