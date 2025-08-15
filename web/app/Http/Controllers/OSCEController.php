<?php

namespace App\Http\Controllers;

use App\Services\CaseManagerService;
use App\Services\PatientSimulatorService;
use App\Models\Session;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OSCEController extends Controller
{
    protected $caseManager;
    protected $patientSimulator;
    
    public function __construct()
    {
        $this->caseManager = new CaseManagerService();
        $this->patientSimulator = new PatientSimulatorService();
    }

    /**
     * Display the main OSCE application
     */
    public function index()
    {
        return Inertia::render('OSCE/Dashboard');
    }

    /**
     * Start OSCE mode and display case selection
     */
    public function startOSCE()
    {
        try {
            $availableCases = $this->caseManager->loadAvailableCases();
            
            if (empty($availableCases)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No OSCE cases are currently available. Please check the cases directory.',
                    'cases' => []
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $this->caseManager->formatCaseSelectionMessage(),
                'cases' => $availableCases,
                'state' => [
                    'awaitingCaseSelection' => true,
                    'showingResults' => false,
                    'currentCase' => null
                ]
            ]);

        } catch (\Exception $error) {
            Log::error('Failed to start OSCE system', ['error' => $error->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start OSCE system: ' . $error->getMessage(),
                'cases' => []
            ]);
        }
    }

    /**
     * Select and initialize a case
     */
    public function selectCase(Request $request)
    {
        $request->validate([
            'caseId' => 'required|string'
        ]);

        try {
            $caseId = $request->caseId;
            $caseData = $this->caseManager->getCaseById($caseId);
            
            // Initialize patient simulator with case data
            $this->patientSimulator->initializePatient($caseData);
            
            // Create a new session
            $session = Session::create([
                'case_id' => $caseId,
                'case_title' => $caseData['title'],
                'started_at' => now(),
                'status' => 'active'
            ]);

            // Store session in request session for stateful interaction
            session(['current_osce_session' => $session->id]);

            $welcomeMessage = "🏥 **Case: {$caseData['title']}**\n\n";
            $welcomeMessage .= "📋 **Chief Complaint:** {$caseData['data']['chiefComplaint']}\n\n";
            $welcomeMessage .= "👋 You may now begin your examination. The patient is ready to see you.\n\n";
            $welcomeMessage .= "💡 **Tips:**\n";
            $welcomeMessage .= "• Start with greeting the patient\n";
            $welcomeMessage .= "• Ask about their symptoms and concerns\n";
            $welcomeMessage .= "• Perform appropriate examinations\n";
            $welcomeMessage .= "• Order relevant tests when needed\n\n";
            $welcomeMessage .= "Type your first interaction to begin...";

            return response()->json([
                'success' => true,
                'message' => $welcomeMessage,
                'case' => $caseData,
                'sessionId' => $session->id,
                'state' => [
                    'awaitingCaseSelection' => false,
                    'showingResults' => false,
                    'currentCase' => $caseId,
                    'sessionStartTime' => $session->started_at
                ]
            ]);

        } catch (\Exception $error) {
            Log::error('Failed to select case', ['caseId' => $request->caseId, 'error' => $error->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to select case: ' . $error->getMessage()
            ]);
        }
    }

    /**
     * Process user input during OSCE session
     */
    public function processInput(Request $request)
    {
        $request->validate([
            'input' => 'required|string',
            'action_type' => 'sometimes|string'
        ]);

        try {
            $sessionId = session('current_osce_session');
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active OSCE session found. Please start a case first.'
                ]);
            }

            $session = Session::findOrFail($sessionId);
            $userInput = $request->input;
            $actionType = $request->input('action_type', 'conversation');

            // Store user message
            ChatMessage::create([
                'session_id' => $session->id,
                'type' => 'user',
                'content' => $userInput,
                'action_type' => $actionType
            ]);

            // Generate patient response
            $patientResponse = $this->patientSimulator->processUserInput($userInput, $actionType);

            // Store patient response
            ChatMessage::create([
                'session_id' => $session->id,
                'type' => 'patient',
                'content' => $patientResponse,
                'action_type' => $actionType
            ]);

            return response()->json([
                'success' => true,
                'response' => $patientResponse,
                'sessionId' => $session->id,
                'state' => [
                    'awaitingCaseSelection' => false,
                    'showingResults' => false,
                    'currentCase' => $session->case_id,
                    'sessionDuration' => now()->diffInSeconds($session->started_at) * 1000
                ]
            ]);

        } catch (\Exception $error) {
            Log::error('Failed to process user input', ['error' => $error->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process input: ' . $error->getMessage()
            ]);
        }
    }

    /**
     * Get current session state
     */
    public function getState()
    {
        $sessionId = session('current_osce_session');
        
        if (!$sessionId) {
            return response()->json([
                'state' => [
                    'awaitingCaseSelection' => true,
                    'showingResults' => false,
                    'currentCase' => null
                ]
            ]);
        }

        $session = Session::find($sessionId);
        if (!$session) {
            return response()->json([
                'state' => [
                    'awaitingCaseSelection' => true,
                    'showingResults' => false,
                    'currentCase' => null
                ]
            ]);
        }

        return response()->json([
            'state' => [
                'awaitingCaseSelection' => false,
                'showingResults' => $session->status === 'completed',
                'currentCase' => $session->case_id,
                'sessionDuration' => now()->diffInSeconds($session->started_at) * 1000,
                'sessionId' => $session->id
            ]
        ]);
    }

    /**
     * End current case and show results
     */
    public function endCase()
    {
        try {
            $sessionId = session('current_osce_session');
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active session found.'
                ]);
            }

            $session = Session::findOrFail($sessionId);
            $session->update([
                'status' => 'completed',
                'ended_at' => now()
            ]);

            // Get conversation history for analysis
            $messages = ChatMessage::where('session_id', $session->id)->get();
            
            $resultsMessage = "🎉 **Case Completed!**\n\n";
            $resultsMessage .= "📊 **Session Summary:**\n";
            $resultsMessage .= "• Case: {$session->case_title}\n";
            $resultsMessage .= "• Duration: " . $session->started_at->diffForHumans($session->ended_at, true) . "\n";
            $resultsMessage .= "• Total interactions: " . $messages->count() . "\n\n";
            $resultsMessage .= "💡 **What's next?**\n";
            $resultsMessage .= "• Review your performance\n";
            $resultsMessage .= "• Try another case\n";
            $resultsMessage .= "• Practice different scenarios\n";

            // Clear session
            session()->forget('current_osce_session');
            $this->patientSimulator->reset();

            return response()->json([
                'success' => true,
                'message' => $resultsMessage,
                'session' => $session,
                'messageCount' => $messages->count(),
                'state' => [
                    'awaitingCaseSelection' => false,
                    'showingResults' => true,
                    'currentCase' => $session->case_id
                ]
            ]);

        } catch (\Exception $error) {
            Log::error('Failed to end case', ['error' => $error->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to end case: ' . $error->getMessage()
            ]);
        }
    }

    /**
     * Reset OSCE session
     */
    public function reset()
    {
        try {
            // Clear current session
            session()->forget('current_osce_session');
            $this->patientSimulator->reset();

            return response()->json([
                'success' => true,
                'message' => 'OSCE session reset successfully.',
                'state' => [
                    'awaitingCaseSelection' => true,
                    'showingResults' => false,
                    'currentCase' => null
                ]
            ]);

        } catch (\Exception $error) {
            Log::error('Failed to reset OSCE session', ['error' => $error->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset session: ' . $error->getMessage()
            ]);
        }
    }

    /**
     * Get session history
     */
    public function getSessionHistory($sessionId = null)
    {
        try {
            $sessionId = $sessionId ?? session('current_osce_session');
            
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No session specified.'
                ]);
            }

            $session = Session::findOrFail($sessionId);
            $messages = ChatMessage::where('session_id', $session->id)
                ->orderBy('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'session' => $session,
                'messages' => $messages
            ]);

        } catch (\Exception $error) {
            Log::error('Failed to get session history', ['error' => $error->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get session history: ' . $error->getMessage()
            ]);
        }
    }

    /**
     * List all completed sessions
     */
    public function listSessions()
    {
        try {
            $sessions = Session::with('messages')
                ->orderBy('started_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'sessions' => $sessions
            ]);

        } catch (\Exception $error) {
            Log::error('Failed to list sessions', ['error' => $error->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to list sessions: ' . $error->getMessage()
            ]);
        }
    }
}

