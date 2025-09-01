<?php

namespace App\Events;

use App\Models\AiAssessmentRun;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssessmentCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $assessmentRun;
    public $sessionId;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(AiAssessmentRun $assessmentRun, int $userId)
    {
        $this->assessmentRun = $assessmentRun;
        $this->sessionId = $assessmentRun->osce_session_id;
        $this->userId = $userId;
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
            'status' => $this->assessmentRun->status,
            'total_score' => $this->assessmentRun->total_score,
            'max_possible_score' => $this->assessmentRun->max_possible_score,
            'completed_at' => $this->assessmentRun->completed_at?->toISOString(),
            'message' => 'Assessment completed successfully!',
            'redirect_url' => route('osce.results.show', $this->sessionId),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'assessment.completed';
    }
}