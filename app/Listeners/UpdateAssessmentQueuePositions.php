<?php

namespace App\Listeners;

use App\Events\AssessmentQueueUpdated;
use App\Jobs\UpdateQueuePositionsJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateAssessmentQueuePositions implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AssessmentQueueUpdated $event): void
    {
        Log::info('AssessmentQueueUpdated event received', [
            'session_id' => $event->sessionId,
            'queue_status' => $event->queueData['status'] ?? 'unknown'
        ]);

        // Only trigger queue position updates for major state changes
        $triggerStates = ['queued', 'completed', 'failed', 'cancelled'];
        
        if (in_array($event->queueData['status'] ?? '', $triggerStates)) {
            UpdateQueuePositionsJob::dispatch()
                ->onQueue('management')
                ->delay(now()->addSeconds(3)); // Batch updates
        }
    }
}