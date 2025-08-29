<?php

require_once 'webapp/bootstrap/app.php';

use App\Services\AiAssessorService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

echo "=== COMPREHENSIVE AI ASSESSMENT TEST ===\n\n";

// Setup log capture
Log::spy();

try {
    $assessorService = app(AiAssessorService::class);
    echo "✅ AiAssessorService instantiated\n";
    
    // Create comprehensive mock session data
    $mockSession = (object) [
        'id' => 999,
        'user_id' => 1,
        'osce_case_id' => 1,
        'status' => 'completed',
        'started_at' => Carbon::now()->subMinutes(30),
        'completed_at' => Carbon::now()->subMinutes(5),
        'duration_minutes' => 45,
        'elapsed_seconds' => 1500, // 25 minutes
        'time_extended' => 0,
        'assessed_at' => null,
        'score' => null,
        'max_score' => null,
        'assessor_model' => null,
        'assessor_output' => null,
        'assessor_payload' => null,
        'rubric_version' => null,
        'update' => function($data) {
            foreach($data as $key => $value) {
                $this->$key = $value;
            }
            return true;
        },
        'load' => function($relations) {
            return $this;
        }
    ];

    // Mock comprehensive case data
    $mockCase = (object) [
        'id' => 1,
        'title' => 'Acute Chest Pain - Emergency Assessment',
        'chief_complaint' => 'Severe chest pain radiating to left arm for 2 hours',
        'description' => 'A 55-year-old male presents with acute onset chest pain',
        'scenario' => 'Emergency department presentation with chest pain',
        'difficulty' => 'intermediate',
        'duration_minutes' => 45,
        'budget' => 1000,
        'learning_objectives' => [
            'Assess acute chest pain systematically',
            'Order appropriate cardiac investigations',
            'Identify signs of acute coronary syndrome',
            'Formulate differential diagnosis'
        ],
        'required_tests' => ['ECG', 'Troponin I', 'Chest X-ray'],
        'highly_appropriate_tests' => ['CBC', 'BNP', 'D-dimer', 'Lipid panel'],
        'contraindicated_tests' => ['Stress test', 'CT Brain'],
        'key_history_points' => [
            'Onset and timing',
            'Character of pain',
            'Radiation pattern', 
            'Associated symptoms',
            'Risk factors',
            'Previous cardiac history'
        ],
        'critical_examinations' => [
            'Vital signs',
            'Cardiovascular examination',
            'Respiratory examination',
            'Peripheral pulse assessment'
        ],
        'expected_diagnosis' => 'Unstable Angina vs NSTEMI',
        'management_plan' => 'Dual antiplatelet therapy, anticoagulation, cardiology consult',
        'teaching_points' => ['Early recognition of ACS', 'Risk stratification']
    ];

    // Mock comprehensive chat messages
    $mockMessages = collect([
        (object) [
            'id' => 1,
            'sender_type' => 'user',
            'message' => 'Can you tell me about your chest pain? When did it start?',
            'sent_at' => Carbon::now()->subMinutes(28)
        ],
        (object) [
            'id' => 2,
            'sender_type' => 'system',
            'message' => 'It started about 2 hours ago while I was mowing the lawn. The pain is very severe, like someone is squeezing my chest.',
            'sent_at' => Carbon::now()->subMinutes(27)
        ],
        (object) [
            'id' => 3,
            'sender_type' => 'user',
            'message' => 'Can you describe the pain? Does it radiate anywhere?',
            'sent_at' => Carbon::now()->subMinutes(26)
        ],
        (object) [
            'id' => 4,
            'sender_type' => 'system',
            'message' => 'Yes, the pain goes down my left arm and up to my jaw. I also feel short of breath and a bit nauseous.',
            'sent_at' => Carbon::now()->subMinutes(25)
        ],
        (object) [
            'id' => 5,
            'sender_type' => 'user',
            'message' => 'Do you have any history of heart problems or risk factors like smoking, diabetes, or high blood pressure?',
            'sent_at' => Carbon::now()->subMinutes(24)
        ],
        (object) [
            'id' => 6,
            'sender_type' => 'system',
            'message' => 'I have high blood pressure and high cholesterol. My father had a heart attack when he was 60. I quit smoking 5 years ago.',
            'sent_at' => Carbon::now()->subMinutes(23)
        ],
        (object) [
            'id' => 7,
            'sender_type' => 'user',
            'message' => 'Have you taken any medications for this pain?',
            'sent_at' => Carbon::now()->subMinutes(22)
        ],
        (object) [
            'id' => 8,
            'sender_type' => 'system',
            'message' => 'I took some aspirin before coming here, but it didnt help much.',
            'sent_at' => Carbon::now()->subMinutes(21)
        ]
    ]);

    // Mock comprehensive test orders
    $mockTests = collect([
        (object) [
            'id' => 1,
            'medicalTest' => (object) [
                'name' => 'ECG',
                'cost' => 50,
                'category' => 'cardiac'
            ],
            'ordered_at' => Carbon::now()->subMinutes(20),
            'result' => 'ST depression in leads V4-V6, T-wave inversion'
        ],
        (object) [
            'id' => 2,
            'medicalTest' => (object) [
                'name' => 'Troponin I',
                'cost' => 75,
                'category' => 'cardiac'
            ],
            'ordered_at' => Carbon::now()->subMinutes(18),
            'result' => 'Elevated at 0.8 ng/mL (normal <0.04)'
        ],
        (object) [
            'id' => 3,
            'medicalTest' => (object) [
                'name' => 'Chest X-ray',
                'cost' => 100,
                'category' => 'imaging'
            ],
            'ordered_at' => Carbon::now()->subMinutes(15),
            'result' => 'No acute pulmonary edema, normal heart size'
        ],
        (object) [
            'id' => 4,
            'medicalTest' => (object) [
                'name' => 'CBC',
                'cost' => 25,
                'category' => 'laboratory'
            ],
            'ordered_at' => Carbon::now()->subMinutes(14),
            'result' => 'Normal hemoglobin, mild leukocytosis'
        ]
    ]);

    // Mock comprehensive examinations
    $mockExaminations = collect([
        (object) [
            'id' => 1,
            'examination_type' => 'Vital Signs',
            'body_part' => 'General',
            'finding' => 'BP 160/95, HR 95, RR 18, Sat 97% on room air',
            'performed_at' => Carbon::now()->subMinutes(25)
        ],
        (object) [
            'id' => 2,
            'examination_type' => 'Cardiovascular',
            'body_part' => 'Heart',
            'finding' => 'Regular rate and rhythm, S4 gallop, no murmurs',
            'performed_at' => Carbon::now()->subMinutes(23)
        ],
        (object) [
            'id' => 3,
            'examination_type' => 'Respiratory',
            'body_part' => 'Lungs',
            'finding' => 'Clear to auscultation bilaterally, no crackles',
            'performed_at' => Carbon::now()->subMinutes(22)
        ],
        (object) [
            'id' => 4,
            'examination_type' => 'Peripheral Vascular',
            'body_part' => 'Extremities',
            'finding' => 'Distal pulses palpable, no peripheral edema',
            'performed_at' => Carbon::now()->subMinutes(20)
        ]
    ]);

    // Set up mock session with all relationships
    $mockSession->osceCase = $mockCase;
    $mockSession->chatMessages = $mockMessages;
    $mockSession->orderedTests = $mockTests;
    $mockSession->examinations = $mockExaminations;

    echo "Mock comprehensive session created:\n";
    echo "- Session ID: {$mockSession->id}\n";
    echo "- Case: {$mockCase->title}\n";
    echo "- Messages: " . $mockMessages->count() . "\n";
    echo "- Tests ordered: " . $mockTests->count() . "\n";
    echo "- Examinations: " . $mockExaminations->count() . "\n";
    echo "- Total test cost: $" . $mockTests->sum(fn($t) => $t->medicalTest->cost) . "\n\n";

    // Test 1: Build artifact
    echo "TEST 1: Building Assessment Artifact\n";
    $artifact = $assessorService->buildArtifact($mockSession);
    echo "✅ Artifact built successfully\n";
    echo "- Artifact session ID: {$artifact['session_id']}\n";
    echo "- Assessment type: {$artifact['assessment_type']}\n";
    echo "- Transcript messages: " . count($artifact['transcript']) . "\n";
    echo "- Test timeline entries: " . count($artifact['detailed_analysis']['tests']['test_ordering_timeline']) . "\n";
    echo "- Required tests status: " . count($artifact['detailed_analysis']['tests']['required_tests_status']) . "\n";
    echo "- Critical exams status: " . count($artifact['detailed_analysis']['examinations']['critical_examinations_status']) . "\n\n";

    // Test 2: Direct Gemini API call for session assessment
    echo "TEST 2: Direct Gemini API Session Assessment\n";
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass($assessorService);
    $method = $reflection->getMethod('callGeminiForSessionScoring');
    $method->setAccessible(true);
    
    echo "Calling Gemini API for detailed clinical assessment...\n";
    $start_time = microtime(true);
    
    try {
        $result = $method->invoke($assessorService, $artifact, $mockSession);
        $end_time = microtime(true);
        
        echo "✅ Gemini API call successful\n";
        echo "- Response time: " . round($end_time - $start_time, 2) . " seconds\n";
        echo "- Assessment type: {$result['assessment_type']}\n";
        echo "- Total score: {$result['total_score']}/{$result['max_possible_score']}\n";
        echo "- Model used: {$result['model_info']['name']}\n";
        
        if (isset($result['clinical_areas']) && is_array($result['clinical_areas'])) {
            echo "- Clinical areas assessed: " . count($result['clinical_areas']) . "\n";
            foreach ($result['clinical_areas'] as $area) {
                echo "  * {$area['area']}: {$area['score']}/{$area['max_score']}\n";
            }
        }
        
        echo "- Safety concerns: " . count($result['safety_concerns']) . "\n";
        echo "- Recommendations: " . count($result['recommendations']) . "\n\n";
        
    } catch (Exception $e) {
        $end_time = microtime(true);
        echo "❌ Gemini API call failed after " . round($end_time - $start_time, 2) . " seconds\n";
        echo "Error: {$e->getMessage()}\n\n";
        
        // Detailed error analysis
        echo "DETAILED ERROR ANALYSIS:\n";
        if (strpos($e->getMessage(), 'Gemini API error') !== false) {
            echo "This appears to be a Gemini API error. Analyzing...\n";
            
            // Extract HTTP status if available
            if (preg_match('/(\d{3})/', $e->getMessage(), $matches)) {
                $status = $matches[1];
                echo "HTTP Status: $status\n";
                
                switch($status) {
                    case '400':
                        echo "Bad Request - Check request format and parameters\n";
                        break;
                    case '401':
                        echo "Unauthorized - API key may be invalid\n";
                        break;
                    case '403':
                        echo "Forbidden - API key may not have required permissions\n";
                        break;
                    case '429':
                        echo "Too Many Requests - Rate limiting active\n";
                        break;
                    case '500':
                    case '502':
                    case '503':
                        echo "Server Error - Google's servers are having issues\n";
                        break;
                }
            }
        }
        
        if (strpos($e->getMessage(), 'JSON') !== false) {
            echo "JSON parsing error - Response format may be incorrect\n";
        }
        
        if (strpos($e->getMessage(), 'schema') !== false) {
            echo "Schema validation error - Response doesn't match expected format\n";
        }
        
        return;
    }

    // Test 3: Full assessment workflow
    echo "TEST 3: Full Assessment Workflow\n";
    echo "Running complete assess() method...\n";
    
    $start_time = microtime(true);
    try {
        $assessedSession = $assessorService->assess($mockSession, true);
        $end_time = microtime(true);
        
        echo "✅ Full assessment completed successfully\n";
        echo "- Total processing time: " . round($end_time - $start_time, 2) . " seconds\n";
        echo "- Final score: {$assessedSession->score}/{$assessedSession->max_score}\n";
        echo "- Percentage: " . round(($assessedSession->score / $assessedSession->max_score) * 100, 1) . "%\n";
        echo "- Assessor model: {$assessedSession->assessor_model}\n";
        echo "- Assessed at: {$assessedSession->assessed_at}\n\n";
        
        // Analyze the assessment output
        if ($assessedSession->assessor_output) {
            echo "ASSESSMENT OUTPUT ANALYSIS:\n";
            $output = is_string($assessedSession->assessor_output) 
                ? json_decode($assessedSession->assessor_output, true)
                : $assessedSession->assessor_output;
                
            if (isset($output['clinical_areas'])) {
                echo "Clinical Areas Breakdown:\n";
                foreach ($output['clinical_areas'] as $area) {
                    $percentage = round(($area['score'] / $area['max_score']) * 100);
                    echo "- {$area['area']}: {$area['score']}/{$area['max_score']} ({$percentage}%)\n";
                    if (!empty($area['areas_for_improvement'])) {
                        echo "  Improvements: " . implode(', ', $area['areas_for_improvement']) . "\n";
                    }
                }
            }
            
            if (isset($output['safety_concerns']) && !empty($output['safety_concerns'])) {
                echo "\nSafety Concerns:\n";
                foreach ($output['safety_concerns'] as $concern) {
                    echo "- $concern\n";
                }
            }
            
            if (isset($output['recommendations']) && !empty($output['recommendations'])) {
                echo "\nRecommendations:\n";
                foreach ($output['recommendations'] as $rec) {
                    echo "- $rec\n";
                }
            }
        }
        
    } catch (Exception $e) {
        $end_time = microtime(true);
        echo "❌ Full assessment failed after " . round($end_time - $start_time, 2) . " seconds\n";
        echo "Error: {$e->getMessage()}\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }

} catch (Exception $e) {
    echo "❌ Setup failed: {$e->getMessage()}\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== COMPREHENSIVE TEST COMPLETE ===\n";
echo "Summary:\n";
echo "- Gemini API Key: " . (config('services.gemini.api_key') ? 'CONFIGURED' : 'MISSING') . "\n";
echo "- Model: " . config('services.gemini.model') . "\n";
echo "- Service Status: " . (app(AiAssessorService::class)->isConfigured() ? 'READY' : 'NOT READY') . "\n";