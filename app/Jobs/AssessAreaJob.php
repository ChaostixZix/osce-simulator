<?php

namespace App\Jobs;

use App\Models\AiAssessmentAreaResult;
use App\Models\AiAssessmentRun;
use App\Models\OsceSession;
use App\Services\AreaAssessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AssessAreaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 1;

    public function __construct(
        public int $sessionId,
        public int $runId,
        public string $area
    ) {}

    public function handle(): void
    {
        $session = OsceSession::findOrFail($this->sessionId);
        $assessmentRun = AiAssessmentRun::findOrFail($this->runId);

        $areaResult = $assessmentRun->areaResults()
            ->where('clinical_area', $this->area)
            ->firstOrFail();

        // Update status to in_progress for this area
        $areaResult->update(['status' => 'in_progress']);

        $assessor = app(AreaAssessor::class);
        $assessor->assessArea($session, $this->area, $areaResult);

        Log::info('AssessAreaJob finished', [
            'run_id' => $this->runId,
            'area' => $this->area,
            'status' => $areaResult->refresh()->status,
            'score' => $areaResult->score,
        ]);
    }
}

