<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OsceCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminOsceCaseController extends Controller
{
    public function index(): Response
    {
        $cases = OsceCase::orderByDesc('created_at')
            ->get(['id', 'title', 'difficulty', 'is_active', 'duration_minutes', 'updated_at']);

        return Inertia::render('Admin/OsceCases/Index', [
            'cases' => $cases,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/OsceCases/Create', [
            'defaults' => [
                'title' => '',
                'description' => '',
                'difficulty' => 'medium',
                'duration_minutes' => 15,
                'scenario' => '',
                'objectives' => '',
                'is_active' => true,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedCaseData($request);

        OsceCase::create($data);

        return redirect()
            ->route('admin.osce-cases.index')
            ->with('success', 'OSCE case created.');
    }

    public function edit(OsceCase $osceCase): Response
    {
        return Inertia::render('Admin/OsceCases/Edit', [
            'case' => $osceCase,
        ]);
    }

    public function update(Request $request, OsceCase $osceCase): RedirectResponse
    {
        $data = $this->validatedCaseData($request);

        $osceCase->update($data);

        return redirect()
            ->route('admin.osce-cases.index')
            ->with('success', 'OSCE case updated.');
    }

    public function destroy(OsceCase $osceCase): RedirectResponse
    {
        $osceCase->delete();

        return redirect()
            ->route('admin.osce-cases.index')
            ->with('success', 'OSCE case deleted.');
    }

    private function validatedCaseData(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:480'],
            'scenario' => ['required', 'string'],
            'objectives' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'stations' => ['nullable', 'array'],
            'checklist' => ['nullable', 'array'],
            'ai_patient_profile' => ['nullable', 'string'],
            'ai_patient_instructions' => ['nullable', 'string'],
            'ai_patient_vitals' => ['nullable', 'array'],
            'ai_patient_symptoms' => ['nullable', 'array'],
            'ai_patient_responses' => ['nullable', 'array'],
            'highly_appropriate_tests' => ['nullable', 'array'],
            'appropriate_tests' => ['nullable', 'array'],
            'acceptable_tests' => ['nullable', 'array'],
            'inappropriate_tests' => ['nullable', 'array'],
            'contraindicated_tests' => ['nullable', 'array'],
            'required_tests' => ['nullable', 'array'],
            'clinical_setting' => ['nullable', 'string'],
            'urgency_level' => ['nullable', 'string'],
            'setting_limitations' => ['nullable', 'array'],
            'case_budget' => ['nullable', 'numeric'],
            'test_results_templates' => ['nullable', 'array'],
            'expected_anamnesis_questions' => ['nullable', 'array'],
            'red_flags' => ['nullable', 'array'],
            'common_differentials' => ['nullable', 'array'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
