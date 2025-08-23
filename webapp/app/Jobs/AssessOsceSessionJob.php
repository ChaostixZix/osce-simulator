<?php

namespace App\Jobs;

use App\Models\OsceSession;
use App\Services\AiAssessorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AssessOsceSessionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private int $sessionId,
        private bool $force = false
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            $session = OsceSession::find($this->sessionId);

            if (! $session) {
                Log::warning('AssessOsceSessionJob: Session not found', [
                    'session_id' => $this->sessionId,
                ]);

                return;
            }

            // Only assess completed or expired sessions
            if ($session->status !== 'completed' && ! $session->is_expired) {
                Log::info('AssessOsceSessionJob: Session not ready for assessment', [
                    'session_id' => $this->sessionId,
                    'status' => $session->status,
                    'is_expired' => $session->is_expired,
                ]);

                return;
            }

            // If session is expired but not completed, mark as completed first
            if ($session->is_expired && $session->status !== 'completed') {
                $session->markAsCompleted();
            }

            // Skip if already assessed unless forced
            if ($session->assessed_at && ! $this->force) {
                Log::info('AssessOsceSessionJob: Session already assessed', [
                    'session_id' => $this->sessionId,
                    'assessed_at' => $session->assessed_at->toISOString(),
                ]);

                return;
            }

            $assessorService = app(AiAssessorService::class);
            $assessorService->assess($session, $this->force);

            $duration = microtime(true) - $startTime;

            Log::info('AssessOsceSessionJob: Assessment completed', [
                'session_id' => $this->sessionId,
                'duration_seconds' => round($duration, 2),
                'score' => $session->fresh()->score,
                'max_score' => $session->fresh()->max_score,
            ]);

        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;

            Log::error('AssessOsceSessionJob: Assessment failed', [
                'session_id' => $this->sessionId,
                'duration_seconds' => round($duration, 2),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['assessment', 'osce-session:'.$this->sessionId];
    }
}
