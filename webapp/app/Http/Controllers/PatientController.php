<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Patients/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bangsal' => 'required|string|max:255',
            'nomor_kamar' => 'required|string|max:255',
            'status' => 'required|in:active,discharged',
        ]);

        $patient = Patient::create($validated);

        // If triggered via Inertia modal on the board, keep user on the board
        if ($request->header('X-Inertia')) {
            return back();
        }

        return redirect()->route('soap.page', $patient);
    }
}
