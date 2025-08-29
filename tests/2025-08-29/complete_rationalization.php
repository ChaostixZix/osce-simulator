<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OsceSessionRationalization;
use App\Models\OsceDiagnosisEntry;
use App\Services\RationalizationService;

$rationalization = OsceSessionRationalization::find(1);

if ($rationalization) {
    echo "=== COMPLETING RATIONALIZATION ===\n";

    // 1. Answer the first card (asked_question)
    $firstCard = $rationalization->cards->where('card_type', 'asked_question')->first();
    if ($firstCard && !$firstCard->is_answered) {
        $firstCard->markAsAnswered("The patient presented with chest pain. I asked about the onset, duration, character, and radiation of the pain to assess for cardiac etiology.");
        echo "✅ Answered first card\n";
    }

    // 2. Submit primary diagnosis
    if (!$rationalization->primary_diagnosis) {
        $rationalization->update([
            'primary_diagnosis' => 'Acute Coronary Syndrome (ACS)',
            'primary_reasoning' => 'Patient presented with chest pain, ECG changes, and elevated cardiac biomarkers consistent with myocardial ischemia.'
        ]);
        echo "✅ Set primary diagnosis\n";
    }

    // 3. Add differential diagnoses
    if ($rationalization->diagnosisEntries->where('diagnosis_type', 'differential')->count() === 0) {
        OsceDiagnosisEntry::create([
            'session_rationalization_id' => $rationalization->id,
            'diagnosis_name' => 'Pulmonary Embolism',
            'diagnosis_type' => 'differential',
            'reasoning' => 'Chest pain with shortness of breath could indicate PE, though ECG and troponin are more consistent with cardiac etiology.',
            'order_index' => 1
        ]);

        OsceDiagnosisEntry::create([
            'session_rationalization_id' => $rationalization->id,
            'diagnosis_name' => 'Pericarditis',
            'diagnosis_type' => 'differential',
            'reasoning' => 'Chest pain that may be positional, though ECG changes suggest ischemia rather than pericardial inflammation.',
            'order_index' => 2
        ]);

        echo "✅ Added differential diagnoses\n";
    }

    // 4. Submit care plan
    if (!$rationalization->care_plan) {
        $carePlan = "1. Immediate: Administer aspirin 325mg, nitroglycerin, morphine if needed, and prepare for cardiac catheterization\n";
        $carePlan .= "2. Diagnostic: Serial ECGs, cardiac biomarkers, chest X-ray\n";
        $carePlan .= "3. Therapeutic: Start heparin or LMWH, beta-blockers, ACE inhibitors\n";
        $carePlan .= "4. Monitoring: Continuous telemetry, oxygen saturation, pain assessment\n";
        $carePlan .= "5. Patient education: Explain diagnosis, treatment plan, lifestyle modifications";

        $rationalization->update(['care_plan' => $carePlan]);
        echo "✅ Set care plan\n";
    }

    // 5. Check if now ready for evaluation
    $service = app(RationalizationService::class);
    $isReady = $service->isReadyForEvaluation($rationalization);

    echo "\n🎯 Ready for evaluation: " . ($isReady ? 'YES' : 'NO') . "\n";

    if ($isReady) {
        echo "🚀 Triggering evaluation...\n";
        $service->completeRationalization($rationalization);
        echo "✅ Evaluation completed!\n";
        echo "Status: " . $rationalization->fresh()->status . "\n";
    }

} else {
    echo "❌ Rationalization not found\n";
}
