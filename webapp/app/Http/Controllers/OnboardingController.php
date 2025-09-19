<?php

namespace App\Http\Controllers;

use App\Models\OsceCase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function show(Request $request, $caseId)
    {
        $osceCase = OsceCase::findOrFail($caseId);
        $user = Auth::user();

        // Check if user has completed onboarding for this case recently
        $completedRecently = $user->onboardingCompletions()
            ->where('osce_case_id', $caseId)
            ->where('completed_at', '>', now()->subDays(7))
            ->exists();

        return Inertia::render('Onboarding/OSCEFlightCheck', [
            'osceCase' => $osceCase,
            'user' => $user,
            'skipAvailable' => $completedRecently,
        ]);
    }

    public function complete(Request $request, $caseId)
    {
        $request->validate([
            'step' => 'required|integer|min:1|max:4',
            'timeSpent' => 'required|integer|min:0',
        ]);

        $user = Auth::user();
        $osceCase = OsceCase::findOrFail($caseId);

        // Record completion
        $user->onboardingCompletions()->updateOrCreate(
            ['osce_case_id' => $caseId],
            [
                'completed_at' => now(),
                'steps_completed' => $request->step,
                'time_spent_seconds' => $request->timeSpent,
            ]
        );

        return response()->json(['success' => true]);
    }

    public function skip(Request $request, $caseId)
    {
        $user = Auth::user();

        // Allow skipping if they've completed onboarding before
        $canSkip = $user->onboardingCompletions()
            ->where('osce_case_id', $caseId)
            ->exists();

        if (!$canSkip) {
            return response()->json(['error' => 'Cannot skip onboarding for first-time case'], 403);
        }

        return response()->json(['success' => true]);
    }

    public function practiceChat(Request $request, $caseId)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $osceCase = OsceCase::findOrFail($caseId);
        $userMessage = $request->message;

        // Simulate AI patient response for practice
        $practiceResponses = [
            'hello' => "Hello, I'm feeling quite unwell today. I've been having chest pain.",
            'chest pain' => "The pain started about 2 hours ago. It's a sharp, crushing pain in the center of my chest.",
            'when' => "It started suddenly while I was climbing stairs. The pain is quite severe, about 8 out of 10.",
            'other symptoms' => "I'm also feeling nauseous and a bit sweaty. My left arm feels tingly too.",
            'medical history' => "I have high blood pressure and diabetes. I take medication for both.",
            'medications' => "I take lisinopril for my blood pressure and metformin for diabetes.",
            'family history' => "My father had a heart attack when he was 55. My mother has diabetes too.",
            'default' => "I'm not sure about that. Can you ask me something else about my symptoms?"
        ];

        // Simple keyword matching for practice responses
        $response = $practiceResponses['default'];
        $lowerMessage = strtolower($userMessage);

        foreach ($practiceResponses as $keyword => $reply) {
            if ($keyword !== 'default' && strpos($lowerMessage, $keyword) !== false) {
                $response = $reply;
                break;
            }
        }

        return response()->json([
            'ai_response' => [
                'message' => $response,
                'isPractice' => true,
            ]
        ]);
    }
}