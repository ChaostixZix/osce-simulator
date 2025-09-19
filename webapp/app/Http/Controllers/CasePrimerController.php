<?php

namespace App\Http\Controllers;

use App\Models\OsceCase;
use App\Services\CasePrimerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CasePrimerController extends Controller
{
    private CasePrimerService $primerService;

    public function __construct(CasePrimerService $primerService)
    {
        $this->primerService = $primerService;
    }

    /**
     * Get full case primer
     */
    public function show(Request $request, $caseId)
    {
        $request->validate([
            'user_level' => 'sometimes|string|in:beginner,intermediate,advanced,expert',
            'focus_areas' => 'sometimes|array',
            'focus_areas.*' => 'string|max:100'
        ]);

        $osceCase = OsceCase::findOrFail($caseId);

        $options = [
            'user_level' => $request->get('user_level', 'intermediate'),
            'focus_areas' => $request->get('focus_areas', [])
        ];

        $primer = $this->primerService->getCasePrimer($osceCase, $options);

        // Record usage if from database
        if (isset($primer['cached']) && !$primer['cached']) {
            $casePrimer = \App\Models\CasePrimer::where('osce_case_id', $caseId)
                ->where('options_hash', md5(json_encode($options)))
                ->first();
            $casePrimer?->recordUsage();
        }

        return response()->json([
            'success' => true,
            'primer' => $primer,
            'case' => [
                'id' => $osceCase->id,
                'title' => $osceCase->title,
                'chief_complaint' => $osceCase->chief_complaint,
                'duration_minutes' => $osceCase->duration_minutes,
                'clinical_setting' => $osceCase->clinical_setting
            ]
        ]);
    }

    /**
     * Get quick primer for onboarding
     */
    public function quick(Request $request, $caseId)
    {
        $osceCase = OsceCase::findOrFail($caseId);
        $quickPrimer = $this->primerService->getQuickPrimer($osceCase);

        return response()->json([
            'success' => true,
            'quick_primer' => $quickPrimer,
            'case' => [
                'id' => $osceCase->id,
                'title' => $osceCase->title,
                'chief_complaint' => $osceCase->chief_complaint
            ]
        ]);
    }

    /**
     * Get case complexity and recommendations
     */
    public function complexity(Request $request, $caseId)
    {
        $osceCase = OsceCase::findOrFail($caseId);

        // Get basic primer to extract complexity
        $primer = $this->primerService->getCasePrimer($osceCase, ['user_level' => 'intermediate']);

        $complexity = $primer['complexity_rating'] ?? 'intermediate';

        $recommendations = $this->getComplexityRecommendations($complexity);

        return response()->json([
            'success' => true,
            'complexity' => $complexity,
            'recommendations' => $recommendations,
            'case' => [
                'id' => $osceCase->id,
                'title' => $osceCase->title,
                'chief_complaint' => $osceCase->chief_complaint
            ]
        ]);
    }

    /**
     * Get multiple case primers for comparison
     */
    public function compare(Request $request)
    {
        $request->validate([
            'case_ids' => 'required|array|min:2|max:4',
            'case_ids.*' => 'exists:osce_cases,id',
            'user_level' => 'sometimes|string|in:beginner,intermediate,advanced,expert'
        ]);

        $caseIds = $request->get('case_ids');
        $userLevel = $request->get('user_level', 'intermediate');

        $primers = [];
        foreach ($caseIds as $caseId) {
            $osceCase = OsceCase::find($caseId);
            if ($osceCase) {
                $primer = $this->primerService->getCasePrimer($osceCase, ['user_level' => $userLevel]);
                $primers[] = [
                    'case_id' => $caseId,
                    'case_title' => $osceCase->title,
                    'complexity' => $primer['complexity_rating'] ?? 'intermediate',
                    'key_diagnoses' => $primer['clinical_overview']['likely_diagnoses'] ?? [],
                    'red_flags' => $primer['clinical_overview']['red_flags'] ?? [],
                    'first_line_tests' => $primer['investigation_strategy']['first_line_tests'] ?? []
                ];
            }
        }

        return response()->json([
            'success' => true,
            'primers' => $primers,
            'user_level' => $userLevel
        ]);
    }

    /**
     * Get recommendations based on complexity level
     */
    private function getComplexityRecommendations(string $complexity): array
    {
        return match($complexity) {
            'beginner' => [
                'time_allocation' => 'Take your time with history - allocate 50% of session time',
                'approach' => 'Focus on systematic approach over speed',
                'key_tip' => 'Ask open-ended questions first, then narrow down with specific questions'
            ],
            'intermediate' => [
                'time_allocation' => 'Balance history (40%) and investigations (35%) with examination (25%)',
                'approach' => 'Develop differential diagnosis early and test hypotheses',
                'key_tip' => 'Consider cost-effectiveness of investigations'
            ],
            'advanced' => [
                'time_allocation' => 'Efficient history (30%) with focused exam and strategic testing',
                'approach' => 'Prioritize high-yield investigations and consider rare diagnoses',
                'key_tip' => 'Think about immediate vs delayed management strategies'
            ],
            'expert' => [
                'time_allocation' => 'Rapid systematic assessment with complex decision-making',
                'approach' => 'Integrate multiple clinical reasoning pathways simultaneously',
                'key_tip' => 'Consider systemic impacts and multi-specialty coordination'
            ],
            default => [
                'time_allocation' => 'Standard balanced approach across all domains',
                'approach' => 'Systematic clinical reasoning with evidence-based practice',
                'key_tip' => 'Focus on patient safety and clear documentation'
            ]
        };
    }
}