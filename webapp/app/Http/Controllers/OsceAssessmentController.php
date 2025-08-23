<?php

namespace App\Http\Controllers;

use App\Jobs\AssessOsceSessionJob;
use App\Models\OsceSession;
use App\Services\AiAssessorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OsceAssessmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Manually trigger assessment for a session
     */
    public function assess(Request $request, OsceSession $session)
    {
        // Authorization: only session owner
        if ($session->user_id !== Auth::id()) {
            abort(403, 'Unauthorized to assess this session');
        }

        // Validate request
        $validated = $request->validate([
            'force' => 'boolean'
        ]);

        $force = $validated['force'] ?? false;

        // Check if session is ready for assessment
        if ($session->status !== 'completed' && !$session->is_expired) {
            return response()->json([
                'error' => 'Session must be completed or expired before assessment'
            ], 400);
        }

        // Mark as completed if expired but not completed
        if ($session->is_expired && $session->status !== 'completed') {
            $session->markAsCompleted();
        }

        // Check if already assessed and not forced
        if ($session->assessed_at && !$force) {
            return response()->json([
                'message' => 'Session already assessed',
                'assessed_at' => $session->assessed_at->toISOString()
            ]);
        }

        // For development, run synchronously; for production, use queue
        if (app()->environment('local')) {
            $assessorService = app(AiAssessorService::class);
            $assessorService->assess($session, $force);
            $session->refresh();
        } else {
            AssessOsceSessionJob::dispatch($session->id, $force);
        }

        return response()->json([
            'message' => 'Assessment ' . (app()->environment('local') ? 'completed' : 'queued'),
            'session_id' => $session->id,
            'score' => $session->score,
            'max_score' => $session->max_score,
            'assessed_at' => $session->assessed_at?->toISOString()
        ]);
    }

    /**
     * Get assessment results for a session (JSON API)
     */
    public function results(OsceSession $session)
    {
        // Authorization: only session owner
        if ($session->user_id !== Auth::id()) {
            abort(403, 'Unauthorized to view assessment results');
        }

        // Gating: results are only viewable after rationalization is complete
        // Single source of truth: $session->is_rationalization_complete (see OsceSession accessor)
        if (!$session->is_rationalization_complete) {
            return response()->json([
                'error' => 'Complete rationalization first.'
            ], 403);
        }

        if (!$session->assessed_at) {
            return response()->json([
                'error' => 'Session has not been assessed yet'
            ], 404);
        }

        return response()->json([
            'session_id' => $session->id,
            'score' => $session->score,
            'max_score' => $session->max_score,
            'assessed_at' => $session->assessed_at->toISOString(),
            'assessor_model' => $session->assessor_model,
            'assessment_type' => $session->assessor_output['assessment_type'] ?? 'session_assessment',
            'assessor_output' => $session->assessor_output,
            'case_title' => $session->osceCase->title ?? 'Unknown Case',
            'user_name' => $session->user->name ?? 'Unknown User',
            'completed_at' => $session->completed_at?->toISOString(),
        ]);
    }

    /**
     * Show assessment results page (Inertia)
     */
    public function show(OsceSession $session)
    {
        // Authorization: only session owner
        if ($session->user_id !== Auth::id()) {
            abort(403, 'Unauthorized to view assessment results');
        }

        // Gating: results page requires completed rationalization for the same session
        // Source of truth: $session->is_rationalization_complete
        if (!$session->is_rationalization_complete) {
            return redirect()
                ->route('osce.chat', $session)
                ->with('warning', 'Selesaikan rasionalisasi terlebih dahulu.');
        }

        // Load necessary relationships
        $session->load(['osceCase', 'user']);

        // Check if assessed
        if (!$session->assessed_at) {
            return Inertia::render('OsceResult', [
                'session' => [
                    'id' => $session->id,
                    'status' => $session->status,
                    'completed_at' => $session->completed_at?->toISOString(),
                    'duration_minutes' => $session->duration_minutes,
                    'time_extended' => $session->time_extended,
                    'clinical_reasoning_score' => $session->clinical_reasoning_score,
                    'total_test_cost' => $session->total_test_cost,
                    'evaluation_feedback' => $session->evaluation_feedback,
                    'case' => [
                        'id' => $session->osceCase->id,
                        'title' => $session->osceCase->title,
                        'chief_complaint' => $session->osceCase->chief_complaint,
                        'description' => $session->osceCase->description,
                        'scenario' => $session->osceCase->scenario,
                        'difficulty' => $session->osceCase->difficulty,
                        'duration_minutes' => $session->osceCase->duration_minutes,
                        'budget' => $session->osceCase->budget ?? 1000,
                        'learning_objectives' => $session->osceCase->learning_objectives ?? [],
                        'key_history_points' => $session->osceCase->key_history_points ?? [],
                        'critical_examinations' => $session->osceCase->critical_examinations ?? [],
                        'required_tests' => $session->osceCase->required_tests ?? [],
                        'highly_appropriate_tests' => $session->osceCase->highly_appropriate_tests ?? [],
                        'contraindicated_tests' => $session->osceCase->contraindicated_tests ?? [],
                        'expected_diagnosis' => $session->osceCase->expected_diagnosis ?? null,
                        'management_plan' => $session->osceCase->management_plan ?? null,
                        'teaching_points' => $session->osceCase->teaching_points ?? [],
                    ],
                    'user' => [
                        'id' => $session->user->id,
                        'name' => $session->user->name,
                    ],
                ],
                'isAssessed' => false,
                'canReassess' => $session->user_id === Auth::id(),
                'error' => 'This session has not been assessed yet.'
            ]);
        }

        // Prepare assessment data for frontend
        $assessmentData = [
            'score' => $session->score,
            'max_score' => $session->max_score,
            'percentage' => $session->max_score > 0 ? round(($session->score / $session->max_score) * 100, 1) : 0,
            'assessed_at' => $session->assessed_at->toISOString(),
            'assessor_model' => $session->assessor_model,
            'assessment_type' => $session->assessor_output['assessment_type'] ?? 'session_assessment',
            'output' => $session->assessor_output,
        ];

        return Inertia::render('OsceResult', [
            'session' => [
                'id' => $session->id,
                'status' => $session->status,
                'completed_at' => $session->completed_at?->toISOString(),
                'duration_minutes' => $session->duration_minutes,
                'time_extended' => $session->time_extended,
                'clinical_reasoning_score' => $session->clinical_reasoning_score,
                'total_test_cost' => $session->total_test_cost,
                'evaluation_feedback' => $session->evaluation_feedback,
                'case' => [
                    'id' => $session->osceCase->id,
                    'title' => $session->osceCase->title,
                    'chief_complaint' => $session->osceCase->chief_complaint,
                    'description' => $session->osceCase->description,
                    'scenario' => $session->osceCase->scenario,
                    'difficulty' => $session->osceCase->difficulty,
                    'duration_minutes' => $session->osceCase->duration_minutes,
                    'budget' => $session->osceCase->budget ?? 1000,
                    'learning_objectives' => $session->osceCase->learning_objectives ?? [],
                    'key_history_points' => $session->osceCase->key_history_points ?? [],
                    'critical_examinations' => $session->osceCase->critical_examinations ?? [],
                    'required_tests' => $session->osceCase->required_tests ?? [],
                    'highly_appropriate_tests' => $session->osceCase->highly_appropriate_tests ?? [],
                    'contraindicated_tests' => $session->osceCase->contraindicated_tests ?? [],
                    'expected_diagnosis' => $session->osceCase->expected_diagnosis ?? null,
                    'management_plan' => $session->osceCase->management_plan ?? null,
                    'teaching_points' => $session->osceCase->teaching_points ?? [],
                ],
                'user' => [
                    'id' => $session->user->id,
                    'name' => $session->user->name,
                ],
            ],
            'assessment' => $assessmentData,
            'isAssessed' => true,
            'canReassess' => $session->user_id === Auth::id(),
            'isAdmin' => false,
        ]);
    }
}
