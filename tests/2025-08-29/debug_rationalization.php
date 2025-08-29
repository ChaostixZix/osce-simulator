<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OsceSessionRationalization;
use App\Services\RationalizationService;

$rationalization = OsceSessionRationalization::find(1);

if ($rationalization) {
    echo "=== RATIONALIZATION DEBUG ===\n";
    echo "ID: {$rationalization->id}\n";
    echo "Status: {$rationalization->status}\n";
    echo "Primary Diagnosis: " . ($rationalization->primary_diagnosis ?: 'NOT SET') . "\n";
    echo "Care Plan: " . ($rationalization->care_plan ? 'SET' : 'NOT SET') . "\n";
    echo "Differential Diagnoses: " . $rationalization->diagnosisEntries->where('diagnosis_type', 'differential')->count() . "\n";

    echo "\n=== CARDS STATUS ===\n";
    $allAnswered = true;
    foreach ($rationalization->cards as $index => $card) {
        $answered = $card->is_answered;
        echo "Card " . ($index + 1) . ": " . ($answered ? 'ANSWERED' : 'NOT ANSWERED') . "\n";
        echo "  Type: {$card->card_type}\n";
        echo "  Rationale: " . ($card->user_rationale ? substr($card->user_rationale, 0, 50) . "..." : 'NONE') . "\n";
        if (!$answered) $allAnswered = false;
    }

    echo "\n=== EVALUATION READINESS CHECK ===\n";
    $service = app(RationalizationService::class);

    echo "✓ All cards answered: " . ($allAnswered ? 'YES' : 'NO') . "\n";
    echo "✓ Primary diagnosis: " . ($rationalization->primary_diagnosis ? 'YES' : 'NO') . "\n";
    echo "✓ Care plan: " . ($rationalization->care_plan ? 'YES' : 'NO') . "\n";
    echo "✓ Min differentials: " . ($rationalization->diagnosisEntries->where('diagnosis_type', 'differential')->count() >= 1 ? 'YES' : 'NO') . "\n";

    $isReady = $service->isReadyForEvaluation($rationalization);
    echo "\n🎯 Overall ready: " . ($isReady ? 'YES' : 'NO') . "\n";

    if (!$isReady) {
        echo "\n💡 To make it ready, you need to:\n";
        if (!$rationalization->primary_diagnosis) echo "  - Submit a primary diagnosis\n";
        if (!$rationalization->care_plan) echo "  - Submit a care plan\n";
        if ($rationalization->diagnosisEntries->where('diagnosis_type', 'differential')->count() < 1) echo "  - Add at least 1 differential diagnosis\n";
    }
} else {
    echo "Rationalization not found\n";
}
