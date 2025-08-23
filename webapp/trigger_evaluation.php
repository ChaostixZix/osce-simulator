<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OsceSessionRationalization;
use App\Services\RationalizationService;

$rationalization = OsceSessionRationalization::find(1);

if ($rationalization) {
    echo "Found rationalization with " . $rationalization->cards->count() . " cards\n";

    // Mark cards as answered if they have rationale
    foreach ($rationalization->cards as $card) {
        if ($card->user_rationale && !$card->is_answered) {
            $card->markAsAnswered($card->user_rationale);
            echo "Marked card " . $card->id . " as answered\n";
        }
    }

    // Check if ready for evaluation
    $service = app(RationalizationService::class);
    $isReady = $service->isReadyForEvaluation($rationalization);
    echo "Ready for evaluation: " . ($isReady ? 'YES' : 'NO') . "\n";

    if ($isReady) {
        // Complete the rationalization
        $service->completeRationalization($rationalization);
        echo "✅ Rationalization completed!\n";
        echo "Status: " . $rationalization->fresh()->status . "\n";
    } else {
        echo "❌ Not ready for evaluation yet\n";
    }
} else {
    echo "Rationalization not found\n";
}
