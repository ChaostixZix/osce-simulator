<?php

namespace App\Events;

use App\Models\AiAssessmentRun;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssessmentFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $assessmentRun;
    public $sessionId;
    public $userId;
    public $errorMessage;

    /**
     * Create a new event instance.
     */
    public function __construct(AiAssessmentRun $assessmentRun, int $userId, string $errorMessage)
    {
        $this->assessmentRun = $assessmentRun;
        $this->sessionId = $assessmentRun->osce_session_id;
        $this->userId = $userId;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("assessment.{$this->userId}")
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'run_id' => $this->assessmentRun->id,
            'status' => 'failed',
            'error_message' => $this->errorMessage,
            'message' => 'Assessment failed. Please try again.',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'assessment.failed';
    }
}