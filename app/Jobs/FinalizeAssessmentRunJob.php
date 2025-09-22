<?php

namespace App\Jobs;

use App\Models\AiAssessmentRun;
use App\Services\AssessmentQueueService;
use App\Services\ResultReducer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FinalizeAssessmentRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 1;

    public function __construct(public int $runId) {}

    public function handle(): void
    {
        $run = AiAssessmentRun::with('areaResults')->findOrFail($this->runId);

        // If any areas are still pending or in progress, requeue this job shortly
        $pending = $run->areaResults->whereIn('status', ['pending', 'in_progress'])->count();
        if ($pending > 0) {
            Log::info('FinalizeAssessmentRunJob requeue - areas pending', [
                'run_id' => $run->id,
                'pending' => $pending,
            ]);
            self::dispatch($run->id)->delay(now()->addSeconds(2))->onQueue('assessments');
            return;
        }

        // Aggregate results
        $reducer = app(ResultReducer::class);
        $final = $reducer->aggregateResults($run);

        $run->update([
            'status' => 'completed',
            'completed_at' => now(),
            'final_result' => $final,
            'total_score' => $final['total_score'] ?? 0,
            'max_possible_score' => $final['max_possible_score'] ?? $run->max_possible_score,
        ]);

        // Mark queue as completed
        app(AssessmentQueueService::class)->markAsCompleted($run->id);

        Log::info('FinalizeAssessmentRunJob completed', [
            'run_id' => $run->id,
            'total_score' => $final['total_score'] ?? 0,
        ]);
    }
}

