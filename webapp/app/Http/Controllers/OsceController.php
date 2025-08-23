<?php

namespace App\Http\Controllers;

use App\Models\OsceCase;
use App\Models\OsceSession;
use App\Models\SessionOrderedTest;
use App\Models\SessionExamination;
use App\Services\RationalizationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OsceController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        
        // Get all active OSCE cases
        $cases = OsceCase::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get user's recent sessions
        $userSessions = OsceSession::with('osceCase')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return Inertia::render('Osce', [
            'cases' => $cases,
            'userSessions' => $userSessions,
            'user' => $user
        ]);
    }

    public function showChat(OsceSession $session): Response
    {
        $user = auth()->user();
        
        // Ensure the session belongs to the authenticated user
        if ($session->user_id !== $user->id) {
            abort(403, 'Unauthorized access to session');
        }
        
        // Load the session with case information and related data
        $session->load(['osceCase', 'orderedTests', 'examinations']);

        // Auto-complete if expired before rendering and prevent reopening
        if ($session->time_status === 'expired') {
            $session->markAsCompleted();
            // Redirect back to OSCE list; do not allow returning to an ended session
            return redirect()->route('osce');
        }
        if (!$session->isActive() && $session->status === 'completed') {
            return redirect()->route('osce');
        }
        
        // Prepare session data (legacy arrays removed in new system)
        $sessionData = [
            'lab_results' => $session->getLabResults(),
            'procedure_results' => $session->getProcedureResults(),
            'examination_findings' => $session->getPhysicalExamFindings(),
        ];
        
        // Load exam catalog directly for now
        $examCatalog = [
            'general' => ['inspection', 'palpation'],
            'cardiovascular' => ['inspection', 'palpation', 'auscultation'],
            'respiratory' => ['inspection', 'palpation', 'percussion', 'auscultation'],
            'abdomen' => ['inspection', 'palpation', 'percussion', 'auscultation'],
            'neurological' => ['mental_status', 'cranial_nerves', 'motor', 'sensory', 'reflexes', 'gait'],
            'musculoskeletal' => ['inspection', 'palpation', 'range_of_motion'],
            'skin' => ['inspection'],
            'heent' => ['inspection']
        ];
        
        return Inertia::render('OsceChat', [
            'session' => $session,
            'user' => $user,
            'sessionData' => $sessionData,
            'examCatalog' => $examCatalog
        ]);
    }

    public function getCases()
    {
        $cases = OsceCase::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($cases);
    }

    public function getUserSessions()
    {
        $user = auth()->user();
        
        $sessions = OsceSession::with('osceCase')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($sessions);
    }

    public function startSession(Request $request)
    {
        $request->validate([
            'osce_case_id' => 'required|exists:osce_cases,id'
        ]);

        $user = auth()->user();
        
        // Check if user already has an active session for this case
        $existingSession = OsceSession::where('user_id', $user->id)
            ->where('osce_case_id', $request->osce_case_id)
            ->where('status', 'in_progress')
            ->first();
            
        if ($existingSession) {
            return response()->json([
                'message' => 'You already have an active session for this case',
                'session' => $existingSession
            ], 400);
        }

        // Create session with pending status first to avoid constraint violation
        $session = OsceSession::create([
            'user_id' => $user->id,
            'osce_case_id' => $request->osce_case_id,
            'status' => 'pending',
        ]);
        
        // Now set started_at and update to in_progress atomically
        $session->started_at = now();
        $session->status = 'in_progress';
        $session->save();

        return response()->json([
            'message' => 'Session started successfully',
            'session' => $session->load('osceCase')
        ]);
    }

    /**
     * Return the authoritative timer state for a session.
     *
     * The remaining time is recalculated from `started_at` on every request so
     * the client can simply hit this endpoint after a refresh or page change
     * and resume the countdown without risk of resetting it.
     */
    public function getSessionTimer(OsceSession $session)
    {
        $user = auth()->user();
        if ($session->user_id !== $user->id) {
            abort(403, 'Unauthorized access to session');
        }

        // Refresh session from database to ensure we have latest data
        $session = $session->fresh();

        // Defensive: ensure started_at is set for in-progress sessions (legacy rows)
        if ($session->status === 'in_progress' && !$session->started_at) {
            // Backfill from created_at to prevent full reset on refresh
            $session->started_at = $session->created_at ?? now();
            $session->save();
            $session = $session->fresh();
        }

        // Auto-complete expired sessions
        if ($session->time_status === 'expired') {
            $session->markAsCompleted();
            $session = $session->fresh(); // Reload after completion
        }

        // Add debug logging to track timer requests
        \Log::info('OSCE Timer Request', [
            'session_id' => $session->id,
            'started_at' => $session->started_at?->toISOString(),
            'current_time' => now()->toISOString(),
            'elapsed_seconds' => $session->elapsed_seconds,
            'remaining_seconds' => $session->remaining_seconds,
            'duration_minutes' => $session->duration_minutes,
            'status' => $session->status,
            'time_status' => $session->time_status
        ]);

        $response = [
            'session_id' => $session->id,
            'elapsed_seconds' => $session->elapsed_seconds,
            'remaining_seconds' => $session->remaining_seconds,
            'duration_minutes' => $session->duration_minutes,
            'case_duration_minutes' => $session->osceCase?->duration_minutes,
            'is_expired' => $session->is_expired,
            'time_status' => $session->time_status,
            'formatted_time_remaining' => gmdate('i:s', max(0, $session->remaining_seconds)),
            'progress_percentage' => $session->duration_minutes > 0
                ? round(((($session->duration_minutes * 60) - $session->remaining_seconds) / ($session->duration_minutes * 60)) * 100, 1)
                : 0.0,
            // Add server timestamp for frontend validation
            'server_timestamp' => now()->timestamp,
            'started_at_timestamp' => $session->started_at?->timestamp,
            'started_at_iso' => $session->started_at?->toISOString(),
        ];

        return response()->json($response);
    }

    public function completeSession(OsceSession $session)
    {
        $user = auth()->user();
        if ($session->user_id !== $user->id) {
            abort(403, 'Unauthorized access to session');
        }

        $session->markAsCompleted();

        return response()->json([
            'message' => 'Session marked as completed',
            'session' => $session->fresh()->load('osceCase')
        ]);
    }

    /**
     * Show session results with rationalization gating
     */
    public function showResults(OsceSession $session, RationalizationService $rationalizationService)
    {
        $user = auth()->user();
        if ($session->user_id !== $user->id) {
            abort(403, 'Unauthorized access to session');
        }

        // Session must be completed
        if ($session->status !== 'completed') {
            return redirect()->route('osce.chat', $session)
                ->withErrors(['error' => 'Session must be completed first']);
        }

        // Check if rationalization is required and completed
        if (!$rationalizationService->canUnlockResults($session)) {
            // Redirect to rationalization interface
            return redirect()->route('rationalization.show', $session)
                ->with('message', 'Complete the rationalization review to view your results.');
        }

        // Load all session data for results display
        $session->load([
            'osceCase',
            'orderedTests',
            'examinations',
            'rationalization.cards',
            'rationalization.diagnosisEntries',
            'rationalization.evaluations'
        ]);

        return Inertia::render('OsceResults', [
            'session' => $session,
            'user' => $user
        ]);
    }

    // New clinical reasoning-based ordering endpoint
    public function orderTests(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:osce_sessions,id',
            'orders' => 'required|array|min:1',
            'orders.*.medical_test_id' => 'required|integer',
            'orders.*.clinical_reasoning' => 'required|string|min:20|max:500',
            'orders.*.priority' => 'required|in:immediate,urgent,routine'
        ]);

        try {
            $session = OsceSession::with('osceCase')
                ->where('id', $request->session_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            if (!$session->isActive()) {
                if ($session->is_expired) {
                    $session->markAsCompleted();
                }
                return response()->json(['error' => 'Session is not active', 'time_status' => $session->time_status], 400);
            }

            $orderedTests = [];
            $totalCost = 0;

            foreach ($request->orders as $orderData) {
                $test = \App\Models\MedicalTest::findOrFail($orderData['medical_test_id']);

                $existingOrder = SessionOrderedTest::where('osce_session_id', $session->id)
                    ->where('medical_test_id', $test->id)
                    ->first();
                if ($existingOrder) {
                    continue;
                }

                $availableSettings = $test->available_settings ?? [];
                if (!in_array('all', $availableSettings) && !in_array($session->osceCase->clinical_setting ?? 'emergency', $availableSettings)) {
                    // Graceful no-data response for unavailable tests
                    $orderedTests[] = new \App\Models\SessionOrderedTest([
                        'osce_session_id' => $session->id,
                        'medical_test_id' => $test->id,
                        'test_name' => $test->name,
                        'test_type' => $test->type,
                        'clinical_reasoning' => $orderData['clinical_reasoning'],
                        'priority' => $orderData['priority'],
                        'cost' => $test->cost,
                        'ordered_at' => now(),
                        'results_available_at' => now(),
                        'results' => [
                            'status' => 'no_data',
                            'message' => $test->name . ' is not available in this setting'
                        ],
                    ]);
                    continue;
                }

                $order = SessionOrderedTest::create([
                    'osce_session_id' => $session->id,
                    'medical_test_id' => $test->id,
                    'test_name' => $test->name,
                    'test_type' => $test->type,
                    'clinical_reasoning' => $orderData['clinical_reasoning'],
                    'priority' => $orderData['priority'],
                    'cost' => $test->cost,
                    'ordered_at' => now(),
                    // memory ID 6519270: turnaround is in seconds now
                    'results_available_at' => now()->addSeconds($test->turnaround_minutes),
                    'results' => [],
                ]);

                $orderedTests[] = $order;
                $totalCost += (float) $test->cost;
            }

            $evaluation = $this->evaluateClinicalReasoning($session, $orderedTests);

            $session->clinical_reasoning_score = ($session->clinical_reasoning_score ?? 0) + $evaluation['score'];
            $session->total_test_cost = ($session->total_test_cost ?? 0) + $totalCost;
            $session->evaluation_feedback = array_values(array_merge((array) ($session->evaluation_feedback ?? []), $evaluation['feedback']));
            $session->save();

            return response()->json([
                'message' => 'Tests ordered successfully',
                'ordered_tests' => $orderedTests,
                'total_cost' => $totalCost,
                'evaluation' => $evaluation,
                'session' => $session->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to order tests'], 500);
        }
    }

    private function evaluateClinicalReasoning(OsceSession $session, array $orderedTests): array
    {
        $case = $session->osceCase;
        $score = 0;
        $feedback = [];
        $totalCost = 0;

        $highlyAppropriate = $case->highly_appropriate_tests ?? [];
        $appropriate = $case->appropriate_tests ?? [];
        $acceptable = $case->acceptable_tests ?? [];
        $inappropriate = $case->inappropriate_tests ?? [];
        $contraindicated = $case->contraindicated_tests ?? [];
        $required = $case->required_tests ?? [];

        foreach ($orderedTests as $order) {
            $testName = $order->test_name;
            $reasoning = (string) $order->clinical_reasoning;
            $cost = (float) $order->cost;
            $totalCost += $cost;

            if (in_array($testName, $highlyAppropriate, true)) {
                $score += 3;
                $feedback[] = 'Excellent: ' . $testName . ' is highly appropriate for this presentation.';
                if ($this->validateReasoning($reasoning, $testName)) {
                    $score += 1;
                    $feedback[] = 'Strong clinical reasoning for ' . $testName . '.';
                }
            } elseif (in_array($testName, $appropriate, true)) {
                $score += 1;
                $feedback[] = 'Good choice: ' . $testName . ' is appropriate.';
            } elseif (in_array($testName, $acceptable, true)) {
                $feedback[] = 'Acceptable: ' . $testName . ' is reasonable but may not be essential.';
            } elseif (in_array($testName, $inappropriate, true)) {
                $score -= 2;
                $feedback[] = 'Consider: ' . $testName . ' may not be necessary for this presentation.';
            } elseif (in_array($testName, $contraindicated, true)) {
                $score -= 5;
                $feedback[] = 'Warning: ' . $testName . ' could be harmful or contraindicated in this situation!';
            }

            if ($cost > 500) {
                $feedback[] = 'Cost consideration: ' . $testName . ' is expensive ($' . number_format($cost, 2) . '). Ensure it\'s justified.';
            }

            $testModel = \App\Models\MedicalTest::where('name', $testName)->first();
            if ($testModel && (int) $testModel->risk_level > 3) {
                $feedback[] = 'Risk: ' . $testName . ' is a high-risk procedure. Ensure benefits outweigh risks.';
            }
        }

        $orderedTestNames = collect($orderedTests)->pluck('test_name')->toArray();
        $missedRequired = array_values(array_diff($required, $orderedTestNames));
        foreach ($missedRequired as $missedTest) {
            $score -= 3;
            $feedback[] = 'Critical miss: ' . $missedTest . ' is essential for this case.';
        }

        $budgetLimit = $case->case_budget ?? 1000;
        if ($totalCost > (float) $budgetLimit) {
            $score -= 2;
            $feedback[] = 'Budget exceeded: Total cost $' . number_format($totalCost, 2) . ' exceeds recommended limit of $' . number_format((float) $budgetLimit, 2) . '.';
        }

        return [
            'score' => max(0, $score),
            'feedback' => $feedback,
            'total_cost' => $totalCost,
            'appropriateness_rating' => null,
            'efficiency_score' => null,
        ];
    }

    private function validateReasoning(string $reasoning, string $testName): bool
    {
        $qualityIndicators = [
            'rule out', 'differential', 'diagnos', 'assess', 'monitor',
            'because', 'suspect', 'evaluate', 'screen', 'confirm'
        ];
        $reasoningLower = strtolower($reasoning);
        $score = 0;
        foreach ($qualityIndicators as $indicator) {
            if (strpos($reasoningLower, $indicator) !== false) {
                $score++;
            }
        }
        return $score >= 2 && strlen($reasoning) >= 25;
    }

    /**
     * Order a procedure for the session
     */
    public function orderProcedure(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:osce_sessions,id',
            'procedure_name' => 'required|string'
        ]);

        try {
            $session = OsceSession::with('osceCase')
                ->where('id', $request->session_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Check if session is active
            if (!$session->isActive()) {
                if ($session->is_expired) {
                    $session->markAsCompleted();
                }
                return back()->withErrors(['error' => 'Session is not active']);
            }

            // In the new system, procedures should be ordered via orderTests API; keep legacy guard soft-disabled

            // Check if procedure already ordered
            $existingProcedure = SessionOrderedTest::where('osce_session_id', $session->id)
                ->where('test_type', 'procedure')
                ->where('test_name', $request->procedure_name)
                ->first();

            if ($existingProcedure) {
                return back()->withErrors(['error' => 'Procedure already ordered']);
            }

            // Results are generated asynchronously in the new system; store placeholder
            $results = ['status' => 'pending'];

            // Create ordered procedure record
            SessionOrderedTest::create([
                'osce_session_id' => $session->id,
                'test_type' => 'procedure',
                'test_name' => $request->procedure_name,
                'results' => $results,
                'ordered_at' => now()
            ]);

            // Redirect back to chat with updated data
            return redirect()->route('osce.chat', $session);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to order procedure']);
        }
    }

    /**
     * Perform physical examination(s) for the session
     */
    public function performExamination(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:osce_sessions,id',
            'examinations' => 'required|array',
            'examinations.*.category' => 'required|string',
            'examinations.*.type' => 'required|string'
        ]);

        try {
            $session = OsceSession::with('osceCase')
                ->where('id', $request->session_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Check if session is active
            if (!$session->isActive()) {
                if ($session->is_expired) {
                    $session->markAsCompleted();
                }
                return back()->withErrors(['error' => 'Session is not active']);
            }

            $examFindings = $session->osceCase->physical_exam_findings ?? [];
            $createdCount = 0;

            foreach ($request->examinations as $exam) {
                $category = $exam['category'];
                $type = $exam['type'];

                // Check if examination already performed
                $existingExam = SessionExamination::where('osce_session_id', $session->id)
                    ->where('examination_category', $category)
                    ->where('examination_type', $type)
                    ->first();

                if ($existingExam) {
                    continue; // Skip already performed examinations
                }

                // Get findings from template or use safe default
                $findings = $examFindings[$category][$type] ?? ['No significant findings'];

                // Create examination record
                SessionExamination::create([
                    'osce_session_id' => $session->id,
                    'examination_category' => $category,
                    'examination_type' => $type,
                    'findings' => $findings,
                    'performed_at' => now()
                ]);

                $createdCount++;
            }

            if ($createdCount === 0) {
                return back()->withErrors(['error' => 'All selected examinations have already been performed']);
            }

            // Redirect back to chat with updated data
            return redirect()->route('osce.chat', $session);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to perform examination']);
        }
    }

    public function extendSession(OsceSession $session, Request $request)
    {
        $user = auth()->user();
        if ($session->user_id !== $user->id) {
            abort(403, 'Unauthorized access to session');
        }
        $data = $request->validate([
            'minutes' => 'required|integer|min:1|max:180'
        ]);
        $session->time_extended = (int) ($session->time_extended ?? 0) + (int) $data['minutes'];
        $session->save();
        return response()->json([
            'message' => 'Session extended',
            'session' => $session->fresh()->load('osceCase')
        ]);
    }

    public function updateCaseDuration(OsceCase $case, Request $request)
    {
        // Optional policy check; comment if not using policies
        // $this->authorize('update', $case);
        $data = $request->validate([
            'duration_minutes' => 'required|integer|min:1|max:240'
        ]);
        $case->duration_minutes = (int) $data['duration_minutes'];
        $case->save();
        return response()->json([
            'message' => 'Case duration updated',
            'case' => $case
        ]);
    }

    public function refreshTestResults(OsceSession $session)
    {
        $user = auth()->user();
        
        // Ensure the session belongs to the authenticated user
        if ($session->user_id !== $user->id) {
            abort(403, 'Unauthorized access to session');
        }

        // Run ProcessTestResultsJob to update any ready results
        \App\Jobs\ProcessTestResultsJob::dispatch();

        // Load fresh session data with all relationships
        $session = $session->fresh(['osceCase', 'orderedTests', 'examinations']);

        return response()->json([
            'message' => 'Test results refreshed',
            'session' => $session,
            'ordered_tests' => $session->orderedTests
        ]);
    }
}
