<?php

namespace App\Http\Controllers;

use App\Models\OsceSession;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OsceRationalizationController extends Controller
{
    public function show(OsceSession $session): Response
    {
        // Authorization: only session owner
        if ($session->user_id !== Auth::id()) {
            abort(403, 'Unauthorized to view rationalization');
        }

        // Only available after session is completed
        if ($session->status !== 'completed') {
            return redirect()->route('osce.chat', $session);
        }

        $session->load(['osceCase', 'user']);

        return Inertia::render('OsceRationalization', [
            'session' => [
                'id' => $session->id,
                'status' => $session->status,
                'completed_at' => $session->completed_at?->toISOString(),
                'rationalization_completed_at' => $session->rationalization_completed_at?->toISOString(),
                'clinical_reasoning_score' => $session->clinical_reasoning_score,
                'total_test_cost' => $session->total_test_cost,
                'evaluation_feedback' => $session->evaluation_feedback,
                'case' => [
                    'id' => $session->osceCase->id,
                    'title' => $session->osceCase->title,
                    'chief_complaint' => $session->osceCase->chief_complaint,
                ],
            ],
        ]);
    }

    public function complete(OsceSession $session)
    {
        if ($session->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Mark rationalization as complete (idempotent)
        if (!$session->rationalization_completed_at) {
            $session->rationalization_completed_at = now();
            $session->save();
        }

        return redirect()->route('osce.results.show', $session)
            ->with('success', 'Rasionalisasi selesai. Hasil penilaian tersedia.');
    }
}

