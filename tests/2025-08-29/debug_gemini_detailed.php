<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\GeminiService;

$geminiService = app(GeminiService::class);

echo "=== DETAILED GEMINI DEBUGGING ===\n\n";

echo "🔍 Service Configuration:\n";
echo "API Key Set: " . (!empty(config('services.gemini.api_key')) ? 'YES' : 'NO') . "\n";
echo "Model: " . config('services.gemini.model') . "\n\n";

echo "🔧 Testing API Connection:\n";
$connectionResult = $geminiService->testConnection();
echo "Success: " . ($connectionResult['success'] ? 'YES' : 'NO') . "\n";
echo "Status Code: " . $connectionResult['status_code'] . "\n";
echo "Model: " . $connectionResult['model'] . "\n";
echo "Response Length: " . $connectionResult['response_length'] . "\n";
if ($connectionResult['error']) {
    echo "Error: " . $connectionResult['error'] . "\n";
}
echo "\n";

echo "📝 Testing Grounding with Simple Request:\n";
$testPrompt = "What is the recommended treatment for acute chest pain?";
$testRationale = "I need to know the standard protocol.";
$testContext = "Medical education context.";

try {
    $result = $geminiService->evaluateWithGrounding($testPrompt, $testRationale, $testContext);

    echo "Raw Response Preview:\n";
    if (isset($result['raw_response'])) {
        $rawResponse = $result['raw_response'];
        if (isset($rawResponse['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $rawResponse['candidates'][0]['content']['parts'][0]['text'];
            echo substr($text, 0, 300) . (strlen($text) > 300 ? "..." : "") . "\n";
        }
    }

    echo "\nGrounding Check:\n";
    if (isset($result['raw_response']['candidates'][0]['groundingMetadata'])) {
        echo "Grounding metadata found: YES\n";
        print_r($result['raw_response']['candidates'][0]['groundingMetadata']);
    } else {
        echo "Grounding metadata found: NO\n";
    }

    echo "\nAPI Request/Response Check:\n";
    echo "Request successful: " . (!isset($result['is_fallback']) ? 'YES' : 'NO (using fallback)') . "\n";

} catch (Exception $e) {
    echo "❌ Exception during evaluation: " . $e->getMessage() . "\n";
    echo "Exception Type: " . get_class($e) . "\n";
}
