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
        
        // Prepare session data
        $sessionData = [
            'lab_results' => $session->getLabResults(),
            'procedure_results' => $session->getProcedureResults(),
            'examination_findings' => $session->getPhysicalExamFindings(),
            'available_labs' => $session->osceCase->available_labs ?? [],
            'available_procedures' => $session->osceCase->available_procedures ?? [],
            'available_examinations' => $session->osceCase->available_examinations ?? []
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

    /**
     * Order a lab test for the session
     */
    public function orderLab(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:osce_sessions,id',
            'test_name' => 'required|string'
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

            // Get available labs from case
            $availableLabs = $session->osceCase->available_labs ?? [];
            if (!in_array($request->test_name, $availableLabs)) {
                return back()->withErrors(['error' => 'Lab test not available for this case']);
            }

            // Check if test already ordered
            $existingTest = SessionOrderedTest::where('osce_session_id', $session->id)
                ->where('test_type', 'lab')
                ->where('test_name', $request->test_name)
                ->first();

            if ($existingTest) {
                return back()->withErrors(['error' => 'Lab test already ordered']);
            }

            // Get lab results template
            $labTemplates = $session->osceCase->lab_results_templates ?? [];
            $results = $labTemplates[$request->test_name] ?? ['error' => 'Results not available'];

            // Create ordered test record
            SessionOrderedTest::create([
                'osce_session_id' => $session->id,
                'test_type' => 'lab',
                'test_name' => $request->test_name,
                'results' => $results,
                'ordered_at' => now()
            ]);

            // Redirect back to chat with updated data
            return redirect()->route('osce.chat', $session);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to order lab test']);
        }
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

            // Get available procedures from case
            $availableProcedures = $session->osceCase->available_procedures ?? [];
            if (!in_array($request->procedure_name, $availableProcedures)) {
                return back()->withErrors(['error' => 'Procedure not available for this case']);
            }

            // Check if procedure already ordered
            $existingProcedure = SessionOrderedTest::where('osce_session_id', $session->id)
                ->where('test_type', 'procedure')
                ->where('test_name', $request->procedure_name)
                ->first();

            if ($existingProcedure) {
                return back()->withErrors(['error' => 'Procedure already ordered']);
            }

            // Get procedure results template
            $procedureTemplates = $session->osceCase->procedure_results_templates ?? [];
            $results = $procedureTemplates[$request->procedure_name] ?? ['error' => 'Results not available'];

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
