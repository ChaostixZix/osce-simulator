<?php

namespace App\Http\Controllers;

use App\Models\OsceSession;
use App\Models\SessionReplay;
use App\Services\ReplayStudioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReplayStudioController extends Controller
{
    private ReplayStudioService $replayService;

    public function __construct(ReplayStudioService $replayService)
    {
        $this->replayService = $replayService;
    }

    /**
     * Show the replay studio page
     */
    public function show(Request $request, $sessionId)
    {
        $session = OsceSession::where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->with('osceCase')
            ->firstOrFail();

        // Check if session is completed
        if ($session->status !== 'completed') {
            return redirect()->route('osce.chat', $session->id)
                ->with('error', 'Session must be completed to access replay studio');
        }

        return Inertia::render('ReplayStudio', [
            'session' => $session,
        ]);
    }

    /**
     * Generate or retrieve replay data
     */
    public function generate(Request $request, $sessionId)
    {
        $session = OsceSession::where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->with(['osceCase', 'chatMessages', 'orderedTests.medicalTest', 'examinations'])
            ->firstOrFail();

        // Check if session is completed
        if ($session->status !== 'completed') {
            return response()->json([
                'success' => false,
                'error' => 'Session must be completed to generate replay'
            ], 400);
        }

        try {
            $replayData = $this->replayService->generateReplay($session);

            // Mark as viewed
            $sessionReplay = SessionReplay::where('osce_session_id', $session->id)->first();
            $sessionReplay?->markAsViewed();

            return response()->json([
                'success' => true,
                'replay' => $replayData,
                'generated_at' => now(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate replay analysis'
            ], 500);
        }
    }

    /**
     * Get existing replay data
     */
    public function get(Request $request, $sessionId)
    {
        $session = OsceSession::where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $sessionReplay = SessionReplay::where('osce_session_id', $session->id)->first();

        if (!$sessionReplay) {
            return response()->json([
                'success' => false,
                'error' => 'No replay available for this session'
            ], 404);
        }

        $sessionReplay->markAsViewed();

        return response()->json([
            'success' => true,
            'replay' => $sessionReplay->replay_data,
            'generated_at' => $sessionReplay->created_at,
            'cached' => true
        ]);
    }

    /**
     * Submit feedback on replay
     */
    public function feedback(Request $request, $sessionId)
    {
        $request->validate([
            'feedback' => 'required|string|max:1000',
            'rating' => 'sometimes|integer|min:1|max:5'
        ]);

        $session = OsceSession::where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $sessionReplay = SessionReplay::where('osce_session_id', $session->id)->first();

        if (!$sessionReplay) {
            return response()->json([
                'success' => false,
                'error' => 'No replay found for feedback'
            ], 404);
        }

        $feedbackData = [
            'feedback' => $request->feedback,
            'rating' => $request->get('rating'),
            'submitted_at' => now(),
            'user_id' => Auth::id()
        ];

        $sessionReplay->update(['user_feedback' => json_encode($feedbackData)]);

        return response()->json(['success' => true]);
    }

    /**
     * Get replay statistics
     */
    public function stats(Request $request, $sessionId)
    {
        $session = OsceSession::where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $sessionReplay = SessionReplay::where('osce_session_id', $session->id)->first();

        if (!$sessionReplay) {
            return response()->json([
                'success' => false,
                'error' => 'No replay available'
            ], 404);
        }

        $timeline = $sessionReplay->getTimeline();
        $scenarios = $sessionReplay->getAlternativeScenarios();
        $insights = $sessionReplay->getPerformanceInsights();

        $stats = [
            'total_events' => count($timeline['events'] ?? []),
            'pivotal_moments' => count($timeline['pivotal_moments'] ?? []),
            'alternative_scenarios' => count($scenarios),
            'session_duration' => $timeline['duration_minutes'] ?? 0,
            'generated_at' => $sessionReplay->created_at,
            'viewed_at' => $sessionReplay->viewed_at,
            'has_feedback' => !is_null($sessionReplay->user_feedback),
            'phase_breakdown' => $timeline['phase_breakdown'] ?? [],
            'insight_categories' => [
                'strengths' => count($insights['strengths'] ?? []),
                'improvements' => count($insights['improvement_areas'] ?? []),
                'efficiency' => count($insights['efficiency_analysis'] ?? []),
                'resources' => count($insights['resource_management'] ?? [])
            ]
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Export replay data
     */
    public function export(Request $request, $sessionId)
    {
        $request->validate([
            'format' => 'required|string|in:json,pdf,summary'
        ]);

        $session = OsceSession::where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->with('osceCase')
            ->firstOrFail();

        $sessionReplay = SessionReplay::where('osce_session_id', $session->id)->first();

        if (!$sessionReplay) {
            return response()->json([
                'success' => false,
                'error' => 'No replay available for export'
            ], 404);
        }

        $format = $request->format;

        switch ($format) {
            case 'json':
                return response()->json([
                    'session_info' => [
                        'id' => $session->id,
                        'case_title' => $session->osceCase->title,
                        'completed_at' => $session->completed_at,
                    ],
                    'replay_data' => $sessionReplay->replay_data
                ])->header('Content-Disposition', 'attachment; filename="session_' . $session->id . '_replay.json"');

            case 'summary':
                $summary = [
                    'session_id' => $session->id,
                    'case_title' => $session->osceCase->title,
                    'duration' => $sessionReplay->getSessionDuration() . ' minutes',
                    'total_events' => $sessionReplay->getTotalEvents(),
                    'pivotal_moments' => count($sessionReplay->getPivotalMoments()),
                    'alternative_scenarios' => count($sessionReplay->getAlternativeScenarios()),
                    'key_insights' => array_merge(
                        $sessionReplay->getPerformanceInsights()['strengths'] ?? [],
                        $sessionReplay->getPerformanceInsights()['improvement_areas'] ?? []
                    )
                ];

                return response()->json($summary)
                    ->header('Content-Disposition', 'attachment; filename="session_' . $session->id . '_summary.json"');

            default:
                return response()->json([
                    'success' => false,
                    'error' => 'Unsupported export format'
                ], 400);
        }
    }

    /**
     * Delete replay data
     */
    public function delete(Request $request, $sessionId)
    {
        $session = OsceSession::where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $sessionReplay = SessionReplay::where('osce_session_id', $session->id)->first();

        if (!$sessionReplay) {
            return response()->json([
                'success' => false,
                'error' => 'No replay found to delete'
            ], 404);
        }

        $sessionReplay->delete();

        return response()->json(['success' => true]);
    }
}