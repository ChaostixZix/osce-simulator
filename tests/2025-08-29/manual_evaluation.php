<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OsceSessionRationalization;
use App\Services\RationalizationEvaluationService;

$rationalization = OsceSessionRationalization::find(1);

if ($rationalization) {
    echo "=== MANUAL RATIONALIZATION EVALUATION ===\n";
    echo "Rationalization ID: {$rationalization->id}\n";
    echo "Cards: {$rationalization->cards->count()}\n\n";

    $evaluationService = app(RationalizationEvaluationService::class);

    try {
        echo "🚀 Starting evaluation...\n";
        $results = $evaluationService->evaluateComplete($rationalization);
        echo "✅ Evaluation completed successfully!\n\n";

        echo "📊 Results:\n";
        echo "Total Score: {$results['total_score']}\n";
        echo "Performance Band: {$results['performance_band']}\n\n";

        // Check cards for citations
        echo "📝 Card Evaluation Results:\n";
        foreach ($rationalization->fresh()->cards as $card) {
            echo "Card ID: {$card->id}\n";
            echo "  Question: " . substr($card->question_text, 0, 50) . "...\n";
            echo "  Verdict: " . ($card->verdict ?: 'Not set') . "\n";
            echo "  Score: " . ($card->score ?: 'N/A') . "\n";
            echo "  Has Citations: " . (!empty($card->citations) ? 'YES (' . count($card->citations) . ')' : 'NO') . "\n";

            if (!empty($card->citations)) {
                echo "  Citations:\n";
                foreach ($card->citations as $index => $citation) {
                    echo "    " . ($index + 1) . ". " . ($citation['title'] ?? 'No title') . "\n";
                    echo "       Source: " . ($citation['source'] ?? 'No source') . "\n";
                    echo "       URL: " . ($citation['url'] ?? 'No URL') . "\n";
                }
            }
            echo "\n";
        }

        echo "🎉 Evaluation with citations completed!\n";

    } catch (Exception $e) {
        echo "❌ Evaluation failed: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }

} else {
    echo "❌ Rationalization not found\n";
}
