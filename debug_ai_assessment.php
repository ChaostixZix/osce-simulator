<?php

require_once __DIR__ . '/webapp/vendor/autoload.php';

use App\Models\OsceSession;
use App\Services\AiAssessorService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = new Application(realpath(__DIR__ . '/webapp'));
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/webapp');
$dotenv->load();

// Configure database
$app->instance('path.config', __DIR__ . '/webapp/config');
$app->instance('path.storage', __DIR__ . '/webapp/storage');
$app->instance('path.database', __DIR__ . '/webapp/database');

// Initialize the application
$app->boot();

echo "=== AI ASSESSMENT DEBUG ===\n\n";

// Check configuration
echo "1. CHECKING CONFIGURATION\n";
echo "Gemini API Key: " . (config('services.gemini.api_key') ? 'SET' : 'NOT SET') . "\n";
echo "Gemini Model: " . config('services.gemini.model') . "\n";
echo "Environment: " . config('app.env') . "\n\n";

// Test direct API connection
echo "2. TESTING DIRECT GEMINI API CONNECTION\n";
$apiKey = config('services.gemini.api_key');
$model = config('services.gemini.model', 'gemini-2.5-flash');

if (!$apiKey) {
    echo "❌ API Key not configured\n";
    exit(1);
}

try {
    $testPayload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => 'Say "Hello, I am working!" and nothing else.']
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0,
            'topK' => 1,
            'topP' => 1,
            'maxOutputTokens' => 100,
        ]
    ];

    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
    ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", $testPayload);

    echo "HTTP Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        echo "✅ Direct API connection successful\n";
        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No text returned';
        echo "Response: " . $text . "\n\n";
    } else {
        echo "❌ Direct API connection failed\n";
        echo "Response body: " . $response->body() . "\n\n";
    }
} catch (Exception $e) {
    echo "❌ Connection error: " . $e->getMessage() . "\n\n";
}

// Test AiAssessorService
echo "3. TESTING AI ASSESSOR SERVICE\n";
try {
    $assessorService = app(AiAssessorService::class);
    echo "✅ AiAssessorService instantiated\n";
    echo "Is configured: " . ($assessorService->isConfigured() ? 'YES' : 'NO') . "\n";
    
    if (!$assessorService->isConfigured()) {
        echo "❌ AiAssessorService not properly configured\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ AiAssessorService error: " . $e->getMessage() . "\n";
    exit(1);
}

// Find a test session or create mock data
echo "\n4. TESTING WITH MOCK SESSION DATA\n";

// Create mock session data for testing
$mockSession = (object) [
    'id' => 999,
    'user_id' => 1,
    'osce_case_id' => 1,
    'status' => 'completed',
    'started_at' => now(),
    'completed_at' => now()->addMinutes(30),
    'duration_minutes' => 45,
    'elapsed_seconds' => 1800,
    'time_extended' => 0,
    'assessed_at' => null,
    'score' => null,
    'max_score' => null,
    'assessor_model' => null,
    'assessor_output' => null,
    'assessor_payload' => null,
    'rubric_version' => null,
];

// Mock case data
$mockCase = (object) [
    'id' => 1,
    'title' => 'Chest Pain Case',
    'chief_complaint' => 'Chest pain for 2 hours',
    'duration_minutes' => 45,
    'budget' => 1000,
    'learning_objectives' => ['Assess chest pain', 'Order appropriate tests'],
    'required_tests' => ['ECG', 'Troponin'],
    'highly_appropriate_tests' => ['CBC', 'Chest X-ray'],
    'contraindicated_tests' => ['CT Brain'],
    'key_history_points' => ['Onset', 'Character', 'Radiation'],
    'critical_examinations' => ['Cardiac examination', 'Chest examination'],
];

// Mock relationships
$mockMessages = collect([
    (object) [
        'id' => 1,
        'sender_type' => 'user',
        'message' => 'Tell me about your chest pain',
        'sent_at' => now()->subMinutes(30),
    ],
    (object) [
        'id' => 2,
        'sender_type' => 'system',
        'message' => 'It started about 2 hours ago, feels like pressure',
        'sent_at' => now()->subMinutes(29),
    ],
]);

$mockTests = collect([
    (object) [
        'id' => 1,
        'medical_test' => (object) ['name' => 'ECG', 'cost' => 25, 'category' => 'cardiac'],
        'ordered_at' => now()->subMinutes(25),
        'result' => 'Normal sinus rhythm',
    ],
]);

$mockExaminations = collect([
    (object) [
        'id' => 1,
        'examination_type' => 'Cardiac',
        'body_part' => 'Chest',
        'finding' => 'Regular rate and rhythm',
        'performed_at' => now()->subMinutes(20),
    ],
]);

// Set up mock session with relationships
$mockSession->osceCase = $mockCase;
$mockSession->chatMessages = $mockMessages;
$mockSession->orderedTests = $mockTests;
$mockSession->examinations = $mockExaminations;

echo "Mock session created with ID: {$mockSession->id}\n";

try {
    // Test buildArtifact method
    echo "\n5. TESTING ARTIFACT BUILDING\n";
    $artifact = $assessorService->buildArtifact($mockSession);
    echo "✅ Artifact built successfully\n";
    echo "Artifact session ID: " . $artifact['session_id'] . "\n";
    echo "Transcript messages: " . count($artifact['transcript']) . "\n";
    echo "Tests count: " . count($artifact['actions']['tests']) . "\n";
    echo "Examinations count: " . count($artifact['actions']['examinations']) . "\n";
    
    // Test direct Gemini call for session assessment
    echo "\n6. TESTING DIRECT GEMINI ASSESSMENT CALL\n";
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($assessorService);
    $method = $reflection->getMethod('callGeminiForSessionScoring');
    $method->setAccessible(true);
    
    echo "Calling Gemini API for session assessment...\n";
    $result = $method->invoke($assessorService, $artifact, $mockSession);
    
    echo "✅ Gemini API call successful\n";
    echo "Assessment type: " . ($result['assessment_type'] ?? 'unknown') . "\n";
    echo "Total score: " . ($result['total_score'] ?? 'unknown') . "\n";
    echo "Max possible score: " . ($result['max_possible_score'] ?? 'unknown') . "\n";
    
    if (isset($result['clinical_areas']) && is_array($result['clinical_areas'])) {
        echo "Clinical areas assessed: " . count($result['clinical_areas']) . "\n";
        foreach ($result['clinical_areas'] as $area) {
            echo "  - {$area['area']}: {$area['score']}/{$area['max_score']}\n";
        }
    }
    
    echo "\n7. FULL SERVICE TEST\n";
    echo "Testing complete assess() method...\n";
    
    $assessedSession = $assessorService->assess($mockSession, true);
    echo "✅ Full assessment completed\n";
    echo "Final score: {$assessedSession->score}/{$assessedSession->max_score}\n";
    echo "Assessor model: {$assessedSession->assessor_model}\n";
    echo "Assessed at: " . ($assessedSession->assessed_at ? $assessedSession->assessed_at->toISOString() : 'null') . "\n";
    
} catch (Exception $e) {
    echo "❌ Assessment test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Try to get more details if it's a Gemini API error
    if (strpos($e->getMessage(), 'Gemini API error') !== false) {
        echo "\n🔍 DETAILED GEMINI API ERROR ANALYSIS\n";
        echo "This appears to be a Gemini API error. Possible causes:\n";
        echo "1. API key is invalid or expired\n";
        echo "2. Model name is incorrect (current: $model)\n";
        echo "3. Rate limiting or quota issues\n";
        echo "4. Request format or schema issues\n";
        echo "5. Network connectivity problems\n";
        
        echo "\nRecommended troubleshooting steps:\n";
        echo "1. Verify API key in Google AI Studio\n";
        echo "2. Check if the model 'gemini-2.5-flash' is available\n";
        echo "3. Try with 'gemini-1.5-flash' instead\n";
        echo "4. Check Google Cloud console for quota limits\n";
        echo "5. Verify network access to googleapis.com\n";
    }
}

echo "\n=== DEBUG COMPLETE ===\n";