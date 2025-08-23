<?php

namespace App\Http\Controllers;

use App\Models\OsceSession;
use App\Models\OsceSessionRationalization;
use App\Models\AnamnesisRationalizationCard;
use App\Services\RationalizationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RationalizationController extends Controller
{
    private RationalizationService $rationalizationService;

    public function __construct(RationalizationService $rationalizationService)
    {
        $this->rationalizationService = $rationalizationService;
    }

    /**
     * Show the rationalization interface for a completed OSCE session
     */
    public function show(OsceSession $session): Response
    {
        $user = auth()->user();
        
        // Ensure the session belongs to the authenticated user
        if ($session->user_id !== $user->id) {
            abort(403, 'Unauthorized access to session');
        }

        // Session must be completed to access rationalization
        if ($session->status !== 'completed') {
            return redirect()->route('osce.chat', $session)
                ->withErrors(['error' => 'Session must be completed before rationalization']);
        }

        // Initialize or get existing rationalization
        $rationalization = $this->rationalizationService->initializeRationalization($session);
        
        // Load all related data
        $rationalization->load([
            'cards' => function($query) {
                $query->orderBy('order_index');
            },
            'diagnosisEntries' => function($query) {
                $query->orderBy('diagnosis_type')->orderBy('order_index');
            },
            'evaluations'
        ]);

        return Inertia::render('Rationalization/Show', [
            'session' => $session->load('osceCase'),
            'rationalization' => $rationalization,
            'progress' => $this->rationalizationService->getCompletionProgress($rationalization),
            'canUnlockResults' => $this->rationalizationService->canUnlockResults($session)
        ]);
    }

    /**
     * Answer a rationalization card
     */
    public function answerCard(Request $request, AnamnesisRationalizationCard $card)
    {
        $request->validate([
            'rationale' => 'nullable|string|max:1000',
            'marked_as_forgot' => 'boolean'
        ]);

        // Ensure the card belongs to the user's session
        if ($card->sessionRationalization->osceSession->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to card');
        }

        if ($request->boolean('marked_as_forgot')) {
            $card->markAsForgot();
        } else {
            $card->markAsAnswered($request->input('rationale'));
        }

        return response()->json([
            'message' => 'Card answered successfully',
            'card' => $card->fresh(),
            'progress' => $this->rationalizationService->getCompletionProgress(
                $card->sessionRationalization
            )
        ]);
    }

    /**
     * Submit diagnosis entries
     */
    public function submitDiagnoses(Request $request, OsceSessionRationalization $rationalization)
    {
        $request->validate([
            'primary_diagnosis' => 'required|string|max:255',
            'primary_reasoning' => 'required|string|min:50|max:1000',
            'differential_diagnoses' => 'required|array|min:1',
            'differential_diagnoses.*.diagnosis' => 'required|string|max:255',
            'differential_diagnoses.*.reasoning' => 'required|string|min:30|max:1000'
        ]);

        // Ensure the rationalization belongs to the user
        if ($rationalization->osceSession->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to rationalization');
        }

        $this->rationalizationService->submitDiagnoses(
            $rationalization,
            $request->input('primary_diagnosis'),
            $request->input('primary_reasoning'),
            $request->input('differential_diagnoses')
        );

        return response()->json([
            'message' => 'Diagnoses submitted successfully',
            'progress' => $this->rationalizationService->getCompletionProgress($rationalization)
        ]);
    }

    /**
     * Submit care plan
     */
    public function submitCarePlan(Request $request, OsceSessionRationalization $rationalization)
    {
        $request->validate([
            'care_plan' => 'required|string|min:100|max:5000'
        ]);

        // Ensure the rationalization belongs to the user
        if ($rationalization->osceSession->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to rationalization');
        }

        $this->rationalizationService->submitCarePlan(
            $rationalization,
            $request->input('care_plan')
        );

        return response()->json([
            'message' => 'Care plan submitted successfully',
            'progress' => $this->rationalizationService->getCompletionProgress($rationalization)
        ]);
    }

    /**
     * Complete rationalization and unlock results
     */
    public function complete(OsceSessionRationalization $rationalization)
    {
        // Ensure the rationalization belongs to the user
        if ($rationalization->osceSession->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to rationalization');
        }

        // Check if ready for completion
        if (!$this->rationalizationService->isReadyForEvaluation($rationalization)) {
            return response()->json([
                'error' => 'Rationalization is not complete. Please answer all questions and submit diagnosis/plan.'
            ], 400);
        }

        // TODO: Trigger evaluation process here
        // For now, just mark as completed
        $this->rationalizationService->completeRationalization($rationalization);

        return response()->json([
            'message' => 'Rationalization completed successfully',
            'rationalization' => $rationalization->fresh(),
            'results_unlocked' => true
        ]);
    }

    /**
     * Get current progress
     */
    public function progress(OsceSessionRationalization $rationalization)
    {
        // Ensure the rationalization belongs to the user
        if ($rationalization->osceSession->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to rationalization');
        }

        return response()->json([
            'progress' => $this->rationalizationService->getCompletionProgress($rationalization),
            'can_unlock_results' => $rationalization->canUnlockResults()
        ]);
    }
}
