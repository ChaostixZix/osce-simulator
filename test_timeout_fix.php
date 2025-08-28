<?php

require_once __DIR__ . '/webapp/vendor/autoload.php';
$app = require_once __DIR__ . '/webapp/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OsceSession;
use App\Services\AiAssessorService;

echo "=== TESTING TIMEOUT FIX ===\n\n";

// Reset the session's assessment to test fresh
$session = OsceSession::with(['osceCase', 'chatMessages', 'orderedTests.medicalTest', 'examinations'])->find(1);
$session->update([
    'assessed_at' => null,
    'score' => null,
    'max_score' => null,
    'assessor_output' => null,
    'assessor_model' => null
]);

echo "🔄 Reset session assessment status\n";
echo "Session ID: {$session->id}\n";
echo "Data quality:\n";
echo "- Messages: " . $session->chatMessages->count() . "\n";
echo "- Tests: " . $session->orderedTests->count() . "\n";
echo "- Examinations: " . $session->examinations->count() . "\n\n";

$assessorService = app(AiAssessorService::class);

echo "🚀 Testing AI Assessment with 60s timeout...\n";
$startTime = microtime(true);

try {
    $result = $assessorService->assess($session, true);
    $endTime = microtime(true);
    $processingTime = round($endTime - $startTime, 2);
    
    echo "✅ SUCCESS! Processing time: {$processingTime} seconds\n";
    echo "📊 Results:\n";
    echo "- Score: {$result->score}/{$result->max_score}\n";
    echo "- Model: {$result->assessor_model}\n";
    
    // Check if it's detailed assessment or rubric fallback
    $output = is_array($result->assessor_output) ? $result->assessor_output : json_decode($result->assessor_output, true);
    
    if (isset($output['clinical_areas']) && is_array($output['clinical_areas'])) {
        echo "🎯 DETAILED AI ASSESSMENT FORMAT CONFIRMED!\n";
        echo "- Clinical areas: " . count($output['clinical_areas']) . "\n";
        echo "- Assessment type: " . ($output['assessment_type'] ?? 'unknown') . "\n";
        echo "\n✅ FIX SUCCESSFUL - NO MORE RUBRIC FALLBACK!\n";
    } elseif (isset($output['criteria'])) {
        echo "⚠️  Still falling back to rubric mode\n";
        echo "- Criteria count: " . count($output['criteria']) . "\n";
        echo "\n❌ FIX NOT COMPLETE - Still timing out\n";
    } else {
        echo "❓ Unknown assessment format\n";
        var_dump($output);
    }
    
} catch (Exception $e) {
    $endTime = microtime(true);
    $processingTime = round($endTime - $startTime, 2);
    
    echo "❌ FAILED after {$processingTime} seconds\n";
    echo "Error: " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), 'timeout') !== false) {
        echo "\n🔍 STILL TIMING OUT!\n";
        echo "Need to increase timeout further or optimize the request\n";
    }
}

echo "\n=== TIMEOUT FIX TEST COMPLETE ===\n";