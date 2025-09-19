<?php

namespace App\Http\Controllers;

use App\Models\OsceSession;
use App\Models\CoachingIntervention;
use App\Services\MicroskillsCoachService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MicroskillsCoachController extends Controller
{
    private MicroskillsCoachService $coachService;

    public function __construct(MicroskillsCoachService $coachService)
    {
        $this->coachService = $coachService;
    }

    /**
     * Get coaching suggestions for current session
     */
    public function analyze(Request $request, $sessionId)
    {
        $session = OsceSession::where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $intervention = $this->coachService->analyzeSession($session);

        return response()->json([
            'success' => true,
            'has_intervention' => !is_null($intervention),
            'intervention' => $intervention,
            'session_stats' => $this->coachService->getCoachingStats($session)
        ]);
    }

    /**
     * Get micro-quiz for knowledge reinforcement
     */
    public function quiz(Request $request, $sessionId)
    {
        $session = OsceSession::where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $quiz = $this->coachService->generateMicroQuiz($session);

        return response()->json([
            'success' => true,
            'has_quiz' => !is_null($quiz),
            'quiz' => $quiz
        ]);
    }

    /**
     * Mark intervention as displayed
     */
    public function markDisplayed(Request $request, $sessionId, $interventionId)
    {
        // Verify the intervention belongs to the user's session
        $intervention = CoachingIntervention::whereHas('osceSession', function ($query) {
            $query->where('user_id', Auth::id());
        })->where('id', $interventionId)->firstOrFail();

        $this->coachService->markInterventionDisplayed($interventionId);

        return response()->json(['success' => true]);
    }

    /**
     * Submit user response to intervention
     */
    public function respond(Request $request, $sessionId, $interventionId)
    {
        $request->validate([
            'response' => 'required|string|max:500',
            'effectiveness_rating' => 'sometimes|integer|min:1|max:5'
        ]);

        $intervention = CoachingIntervention::whereHas('osceSession', function ($query) {
            $query->where('user_id', Auth::id());
        })->where('id', $interventionId)->firstOrFail();

        $intervention->update([
            'user_response' => $request->response,
            'effectiveness_rating' => $request->get('effectiveness_rating'),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get coaching history for session
     */
    public function history(Request $request, $sessionId)
    {
        $session = OsceSession::where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $interventions = CoachingIntervention::where('osce_session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'interventions' => $interventions,
            'stats' => $this->coachService->getCoachingStats($session)
        ]);
    }

    /**
     * Get real-time coaching status
     */
    public function status(Request $request, $sessionId)
    {
        $session = OsceSession::where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Check for pending interventions
        $pendingIntervention = CoachingIntervention::where('osce_session_id', $sessionId)
            ->whereNull('displayed_at')
            ->latest()
            ->first();

        $stats = $this->coachService->getCoachingStats($session);

        return response()->json([
            'success' => true,
            'has_pending_intervention' => !is_null($pendingIntervention),
            'pending_intervention' => $pendingIntervention,
            'session_stats' => $stats,
            'coaching_enabled' => true
        ]);
    }

    /**
     * Submit quiz answer
     */
    public function submitQuizAnswer(Request $request, $sessionId)
    {
        $request->validate([
            'question_id' => 'required|string',
            'selected_answer' => 'required|integer|min:0|max:3',
            'correct_answer' => 'required|integer|min:0|max:3'
        ]);

        $isCorrect = $request->selected_answer === $request->correct_answer;

        // You could store quiz results for analytics
        // QuizResponse::create([...]);

        return response()->json([
            'success' => true,
            'correct' => $isCorrect,
            'selected_answer' => $request->selected_answer,
            'correct_answer' => $request->correct_answer
        ]);
    }

    /**
     * Get coaching preferences
     */
    public function getPreferences(Request $request)
    {
        $user = Auth::user();

        // Get user preferences from settings or defaults
        $preferences = [
            'coaching_enabled' => true,
            'intervention_frequency' => 'medium', // low, medium, high
            'quiz_frequency' => 'medium',
            'preferred_intervention_types' => [
                'decision_support',
                'time_management',
                'resource_management'
            ],
            'auto_display' => true,
            'sound_notifications' => false
        ];

        return response()->json([
            'success' => true,
            'preferences' => $preferences
        ]);
    }

    /**
     * Update coaching preferences
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'coaching_enabled' => 'boolean',
            'intervention_frequency' => 'string|in:low,medium,high',
            'quiz_frequency' => 'string|in:low,medium,high',
            'preferred_intervention_types' => 'array',
            'auto_display' => 'boolean',
            'sound_notifications' => 'boolean'
        ]);

        // Save preferences to user settings
        // UserPreference::updateOrCreate([...]);

        return response()->json(['success' => true]);
    }
}