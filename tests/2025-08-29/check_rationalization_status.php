<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OsceSessionRationalization;

echo "=== CHECKING RATIONALIZATION STATUS ===\n\n";

$rationalizations = OsceSessionRationalization::all();

if ($rationalizations->count() === 0) {
    echo "❌ No rationalizations found in database\n";
    echo "This means the rationalization process hasn't been completed yet.\n";
    exit(1);
}

echo "📊 Found " . $rationalizations->count() . " rationalization(s):\n\n";

foreach ($rationalizations as $rat) {
    echo "🔍 Rationalization ID: {$rat->id}\n";
    echo "   Status: {$rat->status}\n";
    echo "   Session ID: {$rat->osce_session_id}\n";
    echo "   Created: {$rat->created_at}\n";
    echo "   Completed: " . ($rat->completed_at ? $rat->completed_at : 'Not completed') . "\n";
    echo "   Cards: {$rat->cards->count()}\n";
    echo "   Evaluations: {$rat->evaluations->count()}\n\n";

    if ($rat->cards->count() > 0) {
        echo "📝 Card Details:\n";
        foreach ($rat->cards as $index => $card) {
            echo "   Card " . ($index + 1) . ":\n";
            echo "     Type: {$card->card_type}\n";
            echo "     Question: " . substr($card->question_text, 0, 50) . "...\n";
            echo "     User Rationale: " . ($card->user_rationale ? substr($card->user_rationale, 0, 50) . "..." : 'None') . "\n";
            echo "     Verdict: " . ($card->verdict ?: 'Not evaluated') . "\n";
            echo "     Score: " . ($card->score ?: 'N/A') . "\n";

            $hasCitations = !empty($card->citations);
            echo "     Citations: " . ($hasCitations ? 'YES (' . count($card->citations) . ')' : 'NO') . "\n";

            if ($hasCitations && count($card->citations) > 0) {
                echo "     Sample Citation:\n";
                $citation = $card->citations[0];
                echo "       Title: " . ($citation['title'] ?? 'N/A') . "\n";
                echo "       Source: " . ($citation['source'] ?? 'N/A') . "\n";
                echo "       URL: " . ($citation['url'] ?? 'N/A') . "\n";
            }

            echo "     Evaluated At: " . ($card->evaluated_at ?: 'Not evaluated') . "\n\n";
        }
    }

    if ($rat->evaluations->count() > 0) {
        echo "📊 Evaluation Details:\n";
        foreach ($rat->evaluations as $eval) {
            echo "   Evaluation ID: {$eval->id}\n";
            echo "   Type: {$eval->evaluation_type}\n";
            echo "   Score: {$eval->total_score}\n";
            echo "   Citations: {$eval->citation_count}\n\n";
        }
    }

    echo "─" . str_repeat("─", 50) . "\n\n";
}

echo "🔍 Checking if evaluation job was triggered...\n";
echo "Check Laravel logs for 'ProcessRationalizationEvaluationJob' entries\n";
