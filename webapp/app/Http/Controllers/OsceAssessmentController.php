<?php

namespace App\Http\Controllers;

use App\Jobs\AiAssessorOrchestrator;
use App\Jobs\AssessOsceSessionJob;
use App\Models\AiAssessmentRun;
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
            'force' => 'boolean',
        ]);

        $force = $validated['force'] ?? false;

        // Check if session is ready for assessment
        if ($session->status !== 'completed' && ! $session->is_expired) {
            return response()->json([
                'error' => 'Session must be completed or expired before assessment',
            ], 400);
        }

        // Mark as completed if expired but not completed
        if ($session->is_expired && $session->status !== 'completed') {
            $session->markAsCompleted();
        }

        // Check if already assessed and not forced
        if ($session->assessed_at && ! $force) {
            return response()->json([
                'message' => 'Session already assessed',
                'assessed_at' => $session->assessed_at->toISOString(),
            ]);
        }

        // Check if there's already a running assessment
        $existingRun = AiAssessmentRun::where('osce_session_id', $session->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existingRun && !$force) {
            return response()->json([
                'message' => 'Assessment already in progress',
                'run_id' => $existingRun->id,
                'status' => 'in_progress',
                'progress' => $existingRun->progress_percentage,
            ]);
        }

        // Dispatch the new orchestrator job
        AiAssessorOrchestrator::dispatch($session->id, $force);
        
        // Get the latest assessment run
        $assessmentRun = AiAssessmentRun::where('osce_session_id', $session->id)
            ->latest()
            ->first();

        return response()->json([
            'message' => 'Assessment started',
            'session_id' => $session->id,
            'run_id' => $assessmentRun->id ?? null,
            'status' => 'in_progress',
        ]);
    }

    /**
     * Get assessment status/progress for polling
     */
    public function status(OsceSession $session)
    {
        // Authorization: only session owner
        if ($session->user_id !== Auth::id()) {
            abort(403, 'Unauthorized to view assessment status');
        }

        $assessmentRun = AiAssessmentRun::where('osce_session_id', $session->id)
            ->latest()
            ->with('areaResults')
            ->first();

        if (!$assessmentRun) {
            return response()->json([
                'status' => 'not_started',
                'message' => 'No assessment run found',
            ]);
        }

        $areaResults = $assessmentRun->areaResults->map(function ($result) {
            return [
                'area' => $result->area_display_name,
                'key' => $result->clinical_area,
                'status' => $result->status,
                'badge_color' => $result->badge_color,
                'badge_text' => $result->badge_text,
                'score' => $result->score,
                'max_score' => $result->max_score,
                'was_repaired' => $result->was_repaired,
                'attempts' => $result->attempts,
            ];
        });

        return response()->json([
            'run_id' => $assessmentRun->id,
            'status' => $assessmentRun->status,
            'progress' => $assessmentRun->progress_percentage,
            'total_score' => $assessmentRun->total_score,
            'max_possible_score' => $assessmentRun->max_possible_score,
            'has_fallbacks' => $assessmentRun->has_fallbacks,
            'completed_areas' => $assessmentRun->completed_areas,
            'total_areas' => $assessmentRun->total_areas,
            'area_results' => $areaResults,
            'started_at' => $assessmentRun->started_at?->toISOString(),
            'completed_at' => $assessmentRun->completed_at?->toISOString(),
            'error_message' => $assessmentRun->error_message,
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

        // Check if there's a completed assessment run
        $assessmentRun = AiAssessmentRun::where('osce_session_id', $session->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        if (!$assessmentRun && !$session->assessed_at) {
            return response()->json([
                'error' => 'Session has not been assessed yet',
            ], 404);
        }

        // If we have a new assessment run, use that data
        if ($assessmentRun) {
            return response()->json([
                'session_id' => $session->id,
                'run_id' => $assessmentRun->id,
                'score' => $assessmentRun->total_score,
                'max_score' => $assessmentRun->max_possible_score,
                'assessed_at' => $assessmentRun->completed_at->toISOString(),
                'assessor_model' => config('services.gemini.model', 'gemini-2.5-flash'),
                'assessment_type' => 'detailed_clinical_areas_assessment',
                'assessor_output' => $assessmentRun->final_result,
                'case_title' => $session->osceCase->title ?? 'Unknown Case',
                'user_name' => $session->user->name ?? 'Unknown User',
                'completed_at' => $session->completed_at?->toISOString(),
                'has_fallbacks' => $assessmentRun->has_fallbacks,
                'telemetry' => $assessmentRun->telemetry,
            ]);
        }

        // Fallback to legacy assessment data
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
                ->route('osce.rationalization.show', $session)
                ->with('warning', 'Selesaikan rasionalisasi terlebih dahulu.');
        }

        // Load necessary relationships
        $session->load(['osceCase', 'user']);

        // Check for new assessment run first
        $assessmentRun = AiAssessmentRun::where('osce_session_id', $session->id)
            ->where('status', 'completed')
            ->latest()
            ->with('areaResults')
            ->first();

        // Check if assessed (either new or legacy)
        if (!$assessmentRun && !$session->assessed_at) {
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
                'error' => 'This session has not been assessed yet.',
            ]);
        }

        // Prepare assessment data for frontend
        if ($assessmentRun) {
            // Use new assessment run data
            $areaResults = $assessmentRun->areaResults->map(function ($result) {
                return [
                    'area' => $result->area_display_name,
                    'key' => $result->clinical_area,
                    'status' => $result->status,
                    'badge_color' => $result->badge_color,
                    'badge_text' => $result->badge_text,
                    'score' => $result->score,
                    'max_score' => $result->max_score,
                    'justification' => $result->justification,
                    'was_repaired' => $result->was_repaired,
                    'attempts' => $result->attempts,
                ];
            });

            $assessmentData = [
                'run_id' => $assessmentRun->id,
                'score' => $assessmentRun->total_score,
                'max_score' => $assessmentRun->max_possible_score,
                'percentage' => $assessmentRun->max_possible_score > 0 ? 
                    round(($assessmentRun->total_score / $assessmentRun->max_possible_score) * 100, 1) : 0,
                'assessed_at' => $assessmentRun->completed_at->toISOString(),
                'assessor_model' => config('services.gemini.model', 'gemini-2.5-flash'),
                'assessment_type' => 'detailed_clinical_areas_assessment',
                'output' => $assessmentRun->final_result,
                'has_fallbacks' => $assessmentRun->has_fallbacks,
                'area_results' => $areaResults,
                'telemetry' => $assessmentRun->telemetry,
                'processing_time' => $assessmentRun->completed_at->diffInSeconds($assessmentRun->started_at),
            ];
        } else {
            // Use legacy assessment data
            $assessmentData = [
                'score' => $session->score,
                'max_score' => $session->max_score,
                'percentage' => $session->max_score > 0 ? round(($session->score / $session->max_score) * 100, 1) : 0,
                'assessed_at' => $session->assessed_at->toISOString(),
                'assessor_model' => $session->assessor_model,
                'assessment_type' => $session->assessor_output['assessment_type'] ?? 'session_assessment',
                'output' => $session->assessor_output,
                'has_fallbacks' => false,
                'is_legacy' => true,
            ];
        }

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
