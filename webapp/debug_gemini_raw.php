<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\GeminiService;

$geminiService = app(GeminiService::class);

echo "=== RAW GEMINI API DEBUG ===\n\n";

echo "🔍 Checking Google Search Tool Configuration:\n";

// Let's manually create a request to see what happens
$testPrompt = "What is the recommended treatment for acute chest pain?";
$testRationale = "I need to know the standard protocol.";
$testContext = "Medical education context.";

try {
    // Let's inspect the request that's being sent
    $reflection = new ReflectionClass($geminiService);
    $method = $reflection->getMethod('buildEvaluationPrompt');
    $method->setAccessible(true);

    $prompt = $method->invoke($geminiService, $testPrompt, $testRationale, $testContext);
    echo "📝 Generated Prompt:\n";
    echo substr($prompt, 0, 200) . "..." . PHP_EOL . PHP_EOL;

    // Now let's try to make the actual API call manually to see what happens
    echo "🌐 Making Direct API Call:\n";

    $requestBody = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt,
                    ],
                ],
            ],
        ],
        'tools' => [
            [
                'google_search' => (object) [],
            ],
        ],
        'generationConfig' => [
            'temperature' => 0.1,
            'topP' => 0.8,
            'maxOutputTokens' => 2048,
            // Remove response schema to test grounding functionality
            // 'responseMimeType' => 'application/json',
            // 'responseSchema' => $schema,
        ],
    ];

    $response = \Illuminate\Support\Facades\Http::timeout(30)
        ->withHeaders(['Content-Type' => 'application/json'])
        ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . config('services.gemini.api_key'), $requestBody);

    echo "Status Code: " . $response->status() . PHP_EOL;
    echo "Response Length: " . strlen($response->body()) . PHP_EOL;

    if ($response->successful()) {
        $data = $response->json();
        echo "✅ API Call Successful\n";

        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            echo "📝 Response Text (first 200 chars):\n";
            echo substr($text, 0, 200) . "..." . PHP_EOL;
        }

        if (isset($data['candidates'][0]['groundingMetadata'])) {
            echo "🎯 Grounding Metadata Found!\n";
            print_r($data['candidates'][0]['groundingMetadata']);
        } else {
            echo "❌ No Grounding Metadata\n";
        }
    } else {
        echo "❌ API Call Failed\n";
        echo "Error: " . $response->body() . PHP_EOL;
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}
