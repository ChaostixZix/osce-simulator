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
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
        ]);

        $note = $patient->soapNotes()->create(array_merge($validated, [
            'author_id' => Auth::id(),
            'state' => 'draft',
        ]));

        return back()->with('newNoteId', $note->id);
    }

    public function update(Request $request, SoapNote $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $validated = $request->validate([
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
        ]);

        $note->update($validated);

        return back();
    }

    public function finalize(Request $request, SoapNote $note): RedirectResponse
    {
        $this->authorize('finalize', $note);

        if (empty($note->subjective) || empty($note->objective) || empty($note->assessment) || empty($note->plan)) {
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
}
