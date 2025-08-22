<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SoapPageController extends Controller
{
    public function show(Request $request, Patient $patient): Response
    {
        $notes = $patient->soapNotes()
            ->with('author:id,name')
            ->latest()
            ->paginate(10);

        return Inertia::render('Soap/Page', [
            'patient' => $patient,
            'notes' => $notes,
            'can' => [
                'admin' => $request->user()->is_admin,
            ],
            'tz' => 'Asia/Jakarta',
        ]);
    }
}
