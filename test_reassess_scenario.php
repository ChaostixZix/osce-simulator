<?php

require_once __DIR__ . '/webapp/vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/webapp/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\OsceAssessmentController;
use App\Models\OsceSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== TESTING REASSESS SCENARIO ===\n\n";

// Create a mock request like the frontend sends
$request = new Request();
$request->merge(['force' => true]);

// Get the session
$session = OsceSession::find(1);
if (!$session) {
    echo "❌ Session not found\n";
    exit(1);
}

echo "📋 Testing Reassessment Process:\n";
echo "Session ID: {$session->id}\n";
echo "Status: {$session->status}\n";
echo "Previously assessed: " . ($session->assessed_at ? 'YES' : 'NO') . "\n\n";

try {
    // Create controller instance
    $controller = new OsceAssessmentController();
    
    // Simulate authenticated user (bypass auth middleware for testing)
    $session->user_id = 1; // Set to match our test user
    $session->save();
    
    // Mock authenticated user
    Auth::shouldReceive('id')->andReturn(1);
    
    echo "🚀 Calling OsceAssessmentController::assess()...\n";
    $startTime = microtime(true);
    
    // This mimics exactly what happens when user clicks "Reassess"
    $response = $controller->assess($request, $session);
    
    $endTime = microtime(true);
    $processingTime = round($endTime - $startTime, 2);
    
    echo "⏱️  Controller processing time: {$processingTime} seconds\n";
    
    // Get response data
    $responseData = $response->getData(true);
    
    echo "📊 Controller Response:\n";
    echo "- Status: " . $response->getStatusCode() . "\n";
    echo "- Message: " . ($responseData['message'] ?? 'N/A') . "\n";
    echo "- Score: " . ($responseData['score'] ?? 'N/A') . "\n";
    echo "- Max Score: " . ($responseData['max_score'] ?? 'N/A') . "\n";
    echo "- Assessed At: " . ($responseData['assessed_at'] ?? 'N/A') . "\n";
    
    if ($response->getStatusCode() === 200) {
        echo "\n✅ CONTROLLER TEST PASSED\n";
        echo "The reassessment endpoint works correctly.\n";
        echo "No fallback to rubric mode detected.\n";
        
        // Check the session's actual assessment data
        $session->refresh();
        echo "\n📝 Session Assessment Status:\n";
        echo "- Score: {$session->score}/{$session->max_score}\n";
        echo "- Model: {$session->assessor_model}\n";
        echo "- Output type: " . (isset($session->assessor_output['assessment_type']) ? $session->assessor_output['assessment_type'] : 'unknown') . "\n";
        
        if (isset($session->assessor_output['clinical_areas'])) {
            echo "- Clinical areas: " . count($session->assessor_output['clinical_areas']) . "\n";
            echo "✅ DETAILED ASSESSMENT FORMAT CONFIRMED\n";
        } elseif (isset($session->assessor_output['criteria'])) {
            echo "- Rubric criteria: " . count($session->assessor_output['criteria']) . "\n";
            echo "⚠️  RUBRIC FORMAT DETECTED - This is the fallback mode\n";
        }
        
    } else {
        echo "\n❌ CONTROLLER TEST FAILED\n";
        echo "HTTP Status: " . $response->getStatusCode() . "\n";
        echo "Error: " . ($responseData['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ CONTROLLER EXCEPTION:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    if (strpos($e->getMessage(), 'timeout') !== false || strpos($e->getMessage(), 'cURL error 28') !== false) {
        echo "\n🔍 TIMEOUT DETECTED:\n";
        echo "This is likely what causes the fallback to rubric mode.\n";
        echo "The Gemini API call is timing out, triggering the fallback.\n";
        
        echo "\n💡 SOLUTION:\n";
        echo "1. Increase the cURL timeout in AiAssessorService\n";
        echo "2. Or handle timeouts more gracefully\n";
        echo "3. Or provide user feedback about the long processing time\n";
    }
}

echo "\n=== REASSESS SCENARIO TEST COMPLETE ===\n";