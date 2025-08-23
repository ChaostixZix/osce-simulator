<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OsceSessionRationalization;

echo "=== CHECKING ALL RATIONALIZATIONS ===\n";
$rationalizations = OsceSessionRationalization::all();

if ($rationalizations->count() > 0) {
    foreach ($rationalizations as $rat) {
        echo "ID: {$rat->id}, Status: {$rat->status}, Session: {$rat->osce_session_id}\n";
        echo "  Cards: {$rat->cards->count()}, Evaluations: {$rat->evaluations->count()}\n";
        if ($rat->cards->count() > 0) {
            $card = $rat->cards->first();
            $hasCitations = !empty($card->citations);
            echo "  Has citations: " . ($hasCitations ? 'YES (' . count($card->citations) . ')' : 'NO') . "\n";
        }
        echo "---\n";
    }
} else {
    echo "No rationalizations found\n";
}

// Check specific rationalization if provided
$targetId = 14;
$rationalization = OsceSessionRationalization::find($targetId);

if ($rationalization) {
    echo "\n=== SPECIFIC RATIONALIZATION {$targetId} ===\n";
    echo "Status: " . $rationalization->status . PHP_EOL;
    echo "Has evaluations: " . ($rationalization->evaluations->count() > 0 ? 'YES' : 'NO') . PHP_EOL;
    echo "Has cards: " . ($rationalization->cards->count() > 0 ? 'YES' : 'NO') . PHP_EOL;

    if ($rationalization->cards->count() > 0) {
        $card = $rationalization->cards->first();
        $hasCitations = !empty($card->citations);
        echo "First card has citations: " . ($hasCitations ? 'YES' : 'NO') . PHP_EOL;

        if ($hasCitations) {
            echo "Citation count: " . count($card->citations) . PHP_EOL;
            echo "Sample citation: " . json_encode($card->citations[0]) . PHP_EOL;
        }
    }
} else {
    echo "\nRationalization {$targetId} not found" . PHP_EOL;
}
