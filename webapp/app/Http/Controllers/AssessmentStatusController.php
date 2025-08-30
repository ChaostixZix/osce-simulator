<?php

namespace App\Http\Controllers;

use App\Services\AssessmentQueueService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssessmentStatusController extends Controller
{
    public function __construct(
        private AssessmentQueueService $queueService
    ) {
        $this->middleware('auth');
    }

    /**
     * Server-Sent Events stream for real-time status updates
     */
    public function stream(Request $request, int $sessionId): StreamedResponse
    {
        // Verify user owns this session
        $session = \App\Models\OsceSession::where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return new StreamedResponse(function () use ($sessionId) {
            // Set up SSE headers
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // Nginx specific

            $lastStatusHash = null;

            // Send initial status
            $this->sendStatusEvent($sessionId, $lastStatusHash);

            // Continue streaming for up to 5 minutes
            $startTime = time();
            $maxDuration = 300; // 5 minutes

            while ((time() - $startTime) < $maxDuration) {
                // Check if client disconnected
                if (connection_aborted()) {
                    break;
                }

                // Send status update if changed
                $this->sendStatusEvent($sessionId, $lastStatusHash);

                // Sleep for 2 seconds between checks
                sleep(2);

                // Flush output
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Get current status as JSON (for polling fallback)
     */
    public function status(Request $request, int $sessionId): Response
    {
        // Verify user owns this session
        $session = \App\Models\OsceSession::where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $status = $this->queueService->getQueueStatus($sessionId);

        return response()->json([
            'session_id' => $sessionId,
            'timestamp' => now()->toISOString(),
            ...$status,
        ]);
    }

    /**
     * Get queue overview (admin only)
     */
    public function queueOverview(): Response
    {
        // Simple auth check - in production you'd want proper role-based auth
        if (!Auth::user() || !Auth::user()->email === 'admin@example.com') {
            abort(403, 'Admin access required');
        }

        $queue = $this->queueService->getActiveQueue();

        return response()->json($queue);
    }

    /**
     * Send status event to SSE stream
     */
    private function sendStatusEvent(int $sessionId, ?string &$lastStatusHash): void
    {
        $status = $this->queueService->getQueueStatus($sessionId);
        $statusHash = md5(json_encode($status));

        // Only send if status changed
        if ($statusHash !== $lastStatusHash) {
            $lastStatusHash = $statusHash;

            $data = json_encode([
                'session_id' => $sessionId,
                'timestamp' => now()->toISOString(),
                ...$status,
            ]);

            echo "event: status-update\n";
            echo "data: {$data}\n\n";

            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }
    }
}