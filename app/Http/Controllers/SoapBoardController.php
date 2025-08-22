<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SoapBoardController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only('status', 'search', 'sort');

        $patients = Patient::query()
            ->withCount('soapNotes')
            ->with(['soapNotes' => fn ($query) => $query->latest()->limit(1)])
            ->when($request->input('status') && $request->input('status') !== 'all', function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->input('sort'), function ($query, $sort) {
                if ($sort === 'name') {
                    $query->orderBy('name');
                } elseif ($sort === 'admission') {
                    $query->orderBy('created_at', 'desc');
                } elseif ($sort === 'latest') {
                    $latestNotes = DB::table('soap_notes')
                        ->select('patient_id', DB::raw('MAX(created_at) as last_note_at'))
                        ->groupBy('patient_id');

                    $query->leftJoinSub($latestNotes, 'latest_notes', function ($join) {
                        $join->on('patients.id', '=', 'latest_notes.patient_id');
                    })
                    ->orderByDesc('last_note_at');
                }
            }, function ($query) {
                // Default sort
                $query->orderBy('name');
            })
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Soap/Board', [
            'patients' => $patients,
            'filters' => $filters,
        ]);
    }
}
