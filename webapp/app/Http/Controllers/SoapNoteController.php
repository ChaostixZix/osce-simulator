<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\SoapNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SoapNoteController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validate([
            'subjective' => 'nullable', // Allow both string and array (JSON)
            'objective' => 'nullable', 
            'assessment' => 'nullable',
            'plan' => 'nullable',
        ]);

        // Ensure no null values are passed to database (NOT NULL constraint)
        $cleanedData = [
            'subjective' => $validated['subjective'] ?? '',
            'objective' => $validated['objective'] ?? '',
            'assessment' => $validated['assessment'] ?? '',
            'plan' => $validated['plan'] ?? '',
            'author_id' => Auth::id(),
            'state' => 'draft',
        ];

        $note = $patient->soapNotes()->create($cleanedData);

        return back()->with('newNoteId', $note->id);
    }

    public function update(Request $request, SoapNote $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $validated = $request->validate([
            'subjective' => 'nullable', // Allow both string and array (JSON)
            'objective' => 'nullable', 
            'assessment' => 'nullable',
            'plan' => 'nullable',
        ]);

        // Ensure no null values are passed to database (NOT NULL constraint)
        $cleanedData = [
            'subjective' => $validated['subjective'] ?? '',
            'objective' => $validated['objective'] ?? '',
            'assessment' => $validated['assessment'] ?? '',
            'plan' => $validated['plan'] ?? '',
        ];

        $note->update($cleanedData);

        return back();
    }

    public function finalize(Request $request, SoapNote $note): RedirectResponse
    {
        $this->authorize('finalize', $note);

        // Check if all fields have content (handle both string and array formats)
        $hasSubjective = $this->hasContent($note->subjective);
        $hasObjective = $this->hasContent($note->objective);
        $hasAssessment = $this->hasContent($note->assessment);
        $hasPlan = $this->hasContent($note->plan);

        if (!$hasSubjective || !$hasObjective || !$hasAssessment || !$hasPlan) {
            throw ValidationException::withMessages([
                'form' => 'All SOAP fields must be filled before finalizing.',
            ]);
        }

        $note->update([
            'state' => 'finalized',
            'finalized_at' => now(),
        ]);

        return back();
    }

    public function destroy(SoapNote $note): RedirectResponse
    {
        $this->authorize('delete', $note);
        $note->delete();
        return back();
    }

    public function restore(Request $request, int $noteId): RedirectResponse
    {
        $note = SoapNote::onlyTrashed()->findOrFail($noteId);
        $this->authorize('restore', $note);
        $note->restore();
        return back();
    }

    /**
     * Check if content field has meaningful content (handles both string and JSON formats)
     */
    private function hasContent($content): bool
    {
        if (empty($content)) {
            return false;
        }

        // If it's a string, check if it's not just empty or whitespace
        if (is_string($content)) {
            return !empty(trim(strip_tags($content)));
        }

        // If it's an array (JSON), check if it has meaningful content
        if (is_array($content)) {
            // Basic check for TipTap JSON structure
            if (isset($content['type']) && $content['type'] === 'doc') {
                return isset($content['content']) && !empty($content['content']);
            }
            // Fallback: any non-empty array considered as having content
            return !empty($content);
        }

        return false;
    }
}
