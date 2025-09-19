<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OsceCase;
use App\Http\Requests\Admin\GenerateOsceCaseRequest;
use App\Services\OsceCaseGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use stdClass;

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
                'stations' => [],
                'checklist' => [],
                'ai_patient_profile' => '',
                'ai_patient_instructions' => '',
                'ai_patient_vitals' => new stdClass(),
                'ai_patient_symptoms' => [],
                'ai_patient_responses' => new stdClass(),
                'expected_anamnesis_questions' => [],
                'red_flags' => [],
                'common_differentials' => [],
                'highly_appropriate_tests' => [],
                'appropriate_tests' => [],
                'acceptable_tests' => [],
                'inappropriate_tests' => [],
                'contraindicated_tests' => [],
                'required_tests' => [],
                'clinical_setting' => '',
                'urgency_level' => 3,
                'setting_limitations' => new stdClass(),
                'case_budget' => null,
                'test_results_templates' => new stdClass(),
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

    public function generate(GenerateOsceCaseRequest $request, OsceCaseGeneratorService $generator)
    {
        try {
            $data = $generator->generateFromUploads(
                $request->file('sources', []),
                $request->filled('instructions') ? (string) $request->input('instructions') : null
            );
        } catch (RuntimeException $exception) {
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors([
                    'generator' => $exception->getMessage(),
                ]);
            }

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        if ($request->header('X-Inertia')) {
            return redirect()->back()->with('generated_case', $data);
        }

        return response()->json([
            'data' => $data,
        ]);
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
            'clinical_setting' => ['nullable', 'string', 'max:255'],
            'urgency_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'setting_limitations' => ['nullable', 'array'],
            'case_budget' => ['nullable', 'numeric'],
            'test_results_templates' => ['nullable', 'array'],
            'expected_anamnesis_questions' => ['nullable', 'array'],
            'red_flags' => ['nullable', 'array'],
            'common_differentials' => ['nullable', 'array'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $validated['stations'] = $this->sanitizeStringArray($validated['stations'] ?? []);
        $validated['checklist'] = $this->sanitizeStringArray($validated['checklist'] ?? []);
        $validated['ai_patient_symptoms'] = $this->sanitizeStringArray($validated['ai_patient_symptoms'] ?? []);
        $validated['expected_anamnesis_questions'] = $this->sanitizeStringArray($validated['expected_anamnesis_questions'] ?? []);
        $validated['red_flags'] = $this->sanitizeStringArray($validated['red_flags'] ?? []);
        $validated['common_differentials'] = $this->sanitizeStringArray($validated['common_differentials'] ?? []);
        $validated['highly_appropriate_tests'] = $this->sanitizeStringArray($validated['highly_appropriate_tests'] ?? []);
        $validated['appropriate_tests'] = $this->sanitizeStringArray($validated['appropriate_tests'] ?? []);
        $validated['acceptable_tests'] = $this->sanitizeStringArray($validated['acceptable_tests'] ?? []);
        $validated['inappropriate_tests'] = $this->sanitizeStringArray($validated['inappropriate_tests'] ?? []);
        $validated['contraindicated_tests'] = $this->sanitizeStringArray($validated['contraindicated_tests'] ?? []);
        $validated['required_tests'] = $this->sanitizeStringArray($validated['required_tests'] ?? []);

        $validated['ai_patient_vitals'] = $this->sanitizeAssociativeArray($validated['ai_patient_vitals'] ?? []);
        $validated['ai_patient_responses'] = $this->sanitizeAssociativeArray($validated['ai_patient_responses'] ?? []);
        $validated['setting_limitations'] = $this->sanitizeAssociativeArray($validated['setting_limitations'] ?? []);
        $validated['test_results_templates'] = $this->sanitizeAssociativeArray($validated['test_results_templates'] ?? []);

        if (array_key_exists('case_budget', $validated) && $validated['case_budget'] === null) {
            $validated['case_budget'] = null;
        }

        return $validated;
    }

    private function sanitizeStringArray(array $items): array
    {
        return collect($items)
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim($value))
            ->values()
            ->all();
    }

    private function sanitizeAssociativeArray(array $items): array
    {
        $clean = [];

        foreach ($items as $key => $value) {
            $stringKey = is_string($key) ? trim($key) : '';

            if ($stringKey === '') {
                continue;
            }

            if (is_bool($value)) {
                $clean[$stringKey] = $value;
                continue;
            }

            if (is_scalar($value)) {
                $stringValue = trim((string) $value);
                if ($stringValue !== '') {
                    $clean[$stringKey] = $stringValue;
                }

                continue;
            }

            if (is_array($value)) {
                $clean[$stringKey] = $value;
            }
        }

        return $clean;
    }
}
