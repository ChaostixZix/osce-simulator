<?php

namespace App\Http\Controllers;

use App\Models\OsceCase;
use App\Models\OsceSession;
use App\Models\SessionOrderedTest;
use App\Models\SessionExamination;
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
        
        // Prepare session data (legacy arrays removed in new system)
        $sessionData = [
            'lab_results' => $session->getLabResults(),
            'procedure_results' => $session->getProcedureResults(),
            'examination_findings' => $session->getPhysicalExamFindings(),
        ];
        
        return Inertia::render('OsceChat', [
            'session' => $session,
            'user' => $user,
            'sessionData' => $sessionData
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

        $session = OsceSession::create([
            'user_id' => $user->id,
            'osce_case_id' => $request->osce_case_id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Session started successfully',
            'session' => $session->load('osceCase')
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

            if ($session->status !== 'in_progress') {
                return response()->json(['error' => 'Session is not active'], 400);
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
                    'results_available_at' => now()->addSeconds($test->turnaround_seconds),
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
            if ($session->status !== 'in_progress') {
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
            if ($session->status !== 'in_progress') {
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

                // Get findings from template
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
}
