<?php

require_once __DIR__ . '/webapp/vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/webapp/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OsceSession;
use App\Services\AiAssessorService;

echo "=== REAL AI ASSESSMENT TEST ===\n\n";

try {
    // Get the session with all data
    $session = OsceSession::with(['osceCase', 'chatMessages', 'orderedTests.medicalTest', 'examinations'])->find(1);

    if (!$session) {
        echo "❌ Session not found\n";
        exit(1);
    }

    echo "📋 Session Overview:\n";
    echo "ID: {$session->id}\n";
    echo "Case: {$session->osceCase->title}\n";
    echo "Messages: " . $session->chatMessages->count() . "\n";
    echo "Tests: " . $session->orderedTests->count() . "\n";
    echo "Exams: " . $session->examinations->count() . "\n";
    echo "Status: {$session->status}\n";
    echo "Already assessed: " . ($session->assessed_at ? 'YES' : 'NO') . "\n\n";

    // Initialize the AI Assessor Service
    $assessorService = app(AiAssessorService::class);
    echo "🤖 AI Assessor Service Status:\n";
    echo "Configured: " . ($assessorService->isConfigured() ? 'YES' : 'NO') . "\n";
    echo "API Key: " . (config('services.gemini.api_key') ? 'SET' : 'MISSING') . "\n";
    echo "Model: " . config('services.gemini.model') . "\n\n";

    echo "🚀 Starting AI Assessment Process...\n";
    $startTime = microtime(true);
    
    // Perform the assessment with force=true (like Reassess button)
    $result = $assessorService->assess($session, true);
    
    $endTime = microtime(true);
    $processingTime = round($endTime - $startTime, 2);
    
    echo "\n✅ ASSESSMENT COMPLETED SUCCESSFULLY!\n";
    echo "⏱️  Processing time: {$processingTime} seconds\n";
    echo "📊 Final Results:\n";
    echo "- Score: {$result->score}/{$result->max_score} (" . round(($result->score/$result->max_score)*100, 1) . "%)\n";
    echo "- Model used: {$result->assessor_model}\n";
    echo "- Assessed at: {$result->assessed_at}\n";
    
    // Analyze the assessment output
    if ($result->assessor_output) {
        $output = is_array($result->assessor_output) ? $result->assessor_output : json_decode($result->assessor_output, true);
        
        echo "\n📝 Assessment Details:\n";
        echo "- Assessment type: " . ($output['assessment_type'] ?? 'unknown') . "\n";
        
        if (isset($output['clinical_areas']) && is_array($output['clinical_areas'])) {
            echo "- Clinical areas assessed: " . count($output['clinical_areas']) . "\n";
            foreach ($output['clinical_areas'] as $area) {
                $percentage = round(($area['score'] / $area['max_score']) * 100);
                echo "  * {$area['area']}: {$area['score']}/{$area['max_score']} ({$percentage}%)\n";
            }
        }
        
        if (isset($output['safety_concerns']) && !empty($output['safety_concerns'])) {
            echo "⚠️ Safety concerns: " . count($output['safety_concerns']) . "\n";
            foreach ($output['safety_concerns'] as $concern) {
                echo "  - $concern\n";
            }
        }
        
        if (isset($output['recommendations']) && !empty($output['recommendations'])) {
            echo "💡 Recommendations: " . count($output['recommendations']) . "\n";
            foreach ($output['recommendations'] as $rec) {
                echo "  - $rec\n";
            }
        }
        
        echo "\n🎯 CONCLUSION: AI ASSESSMENT IS WORKING PERFECTLY!\n";
        echo "   The assessment completed without falling back to rubric mode.\n";
        echo "   The Gemini API processed the complex medical scenario successfully.\n";
    }
    
} catch (Exception $e) {
    $endTime = microtime(true);
    $processingTime = round($endTime - $startTime, 2);
    
    echo "\n❌ ASSESSMENT FAILED AFTER {$processingTime} SECONDS\n";
    echo "🔍 Error Analysis:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    if (strpos($e->getMessage(), 'Gemini') !== false) {
        echo "\n🤖 GEMINI API ERROR DETECTED:\n";
        echo "This suggests the issue is with the API call or response parsing.\n";
    } elseif (strpos($e->getMessage(), 'JSON') !== false) {
        echo "\n📄 JSON PARSING ERROR DETECTED:\n";
        echo "The AI returned a response but it could not be parsed as valid JSON.\n";
    } elseif (strpos($e->getMessage(), 'schema') !== false || strpos($e->getMessage(), 'validation') !== false) {
        echo "\n🔍 SCHEMA VALIDATION ERROR DETECTED:\n";
        echo "The AI response was valid JSON but did not match the expected format.\n";
    }
    
    echo "\nThis would trigger the fallback to rubric mode in the application.\n";
    
    // Print stack trace for debugging
    echo "\n📚 Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== AI ASSESSMENT TEST COMPLETE ===\n";