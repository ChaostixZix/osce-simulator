<?php

namespace App\Http\Controllers;

use App\Models\OsceCase;
use App\Models\SoapNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SoapController extends Controller
{
    public function index(OsceCase $osceCase)
    {
        return response()->json($osceCase->soapNotes()->with('author')->latest()->get());
    }

    public function store(Request $request, OsceCase $osceCase)
    {
        $data = $request->validate([
            'id' => 'nullable|exists:soap_notes,id',
            'subjective' => 'nullable|string',
            'objective' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
        ]);

        $note = $osceCase->soapNotes()->updateOrCreate(
            [
                'id' => $data['id'] ?? null,
                'author_id' => Auth::id(),
                'state' => 'draft',
            ],
            [
                'subjective' => $data['subjective'] ?? '',
                'objective' => $data['objective'] ?? '',
                'assessment' => $data['assessment'] ?? '',
                'plan' => $data['plan'] ?? '',
            ]
        );

        return response()->json($note);
    }

    public function finalize(SoapNote $soapNote)
    {
        $this->authorize('update', $soapNote);

        $soapNote->update(['state' => 'finalized']);

        return response()->json($soapNote);
    }
}
