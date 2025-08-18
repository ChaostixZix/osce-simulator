<?php

namespace App\Services;

use App\Models\OsceSession;

class ClinicalReasoningAnalyzer
{
    public function generateDetailedFeedback(OsceSession $session): array
    {
        $orderedTests = $session->orderedTests;
        $case = $session->osceCase;

        return [
            'diagnostic_accuracy' => $this->assessDiagnosticAccuracy($orderedTests, $case),
            'cost_efficiency' => null,
            'clinical_reasoning_quality' => null,
            'time_management' => null,
            'patient_safety' => null,
            'overall_score' => $session->clinical_reasoning_score ?? 0,
            'recommendations' => [],
        ];
    }

    private function assessDiagnosticAccuracy($tests, $case): array
    {
        $required = $case->required_tests ?? [];
        $highlyAppropriate = $case->highly_appropriate_tests ?? [];

        $ordered = $tests->pluck('test_name')->toArray();
        $requiredOrdered = array_intersect($required, $ordered);
        $appropriateOrdered = array_intersect($highlyAppropriate, $ordered);

        return [
            'score' => count($required) > 0 ? (count($requiredOrdered) / max(1, count($required)) * 100) : 0,
            'required_ordered' => count($requiredOrdered),
            'required_total' => count($required),
            'appropriate_ordered' => count($appropriateOrdered),
            'missed_critical' => array_values(array_diff($required, $ordered))
        ];
    }
}


