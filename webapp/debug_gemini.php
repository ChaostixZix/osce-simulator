<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\GeminiService;

$geminiService = app(GeminiService::class);

echo "=== TESTING GEMINI GROUNDING ===\n";

$testPrompt = "Evaluate this medical decision: ordering an ECG for chest pain.";
$testRationale = "The patient has chest pain and I'm concerned about cardiac ischemia.";
$testContext = "This is a 55-year-old patient presenting with acute chest pain.";

try {
    echo "🚀 Testing evaluateWithGrounding...\n";
    $result = $geminiService->evaluateWithGrounding($testPrompt, $testRationale, $testContext);

    echo "✅ API call successful!\n\n";

    echo "📊 Result Structure:\n";
    print_r($result);

    echo "\n📝 Evaluation Data:\n";
    if (isset($result['evaluation'])) {
        $eval = $result['evaluation'];
        echo "User Rationale Summary: " . ($eval['user_rationale_summary'] ?? 'N/A') . "\n";
        echo "Verdict: " . ($eval['verdict'] ?? 'N/A') . "\n";
        echo "Total Score: " . ($eval['total_score'] ?? 'N/A') . "\n";
        echo "Has Citations: " . (!empty($eval['citations']) ? 'YES (' . count($eval['citations']) . ')' : 'NO') . "\n";

        if (!empty($eval['citations'])) {
            echo "Citations:\n";
            foreach ($eval['citations'] as $index => $citation) {
                echo "  " . ($index + 1) . ". " . ($citation['title'] ?? 'No title') . "\n";
                echo "     Source: " . ($citation['source'] ?? 'No source') . "\n";
                echo "     URL: " . ($citation['url'] ?? 'No URL') . "\n";
            }
        }
    }

    echo "\n🔍 Grounding Metadata:\n";
    if (isset($result['grounding_metadata'])) {
        echo "Grounding data present: YES\n";
        print_r($result['grounding_metadata']);
    } else {
        echo "Grounding data present: NO\n";
    }

    echo "\n📋 Raw Response:\n";
    if (isset($result['raw_response'])) {
        echo "Raw response available: YES\n";
        // Show first 500 chars of raw response
        $rawText = json_encode($result['raw_response'], JSON_PRETTY_PRINT);
        echo substr($rawText, 0, 500) . (strlen($rawText) > 500 ? "..." : "") . "\n";
    }

} catch (Exception $e) {
    echo "❌ Gemini evaluation failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
