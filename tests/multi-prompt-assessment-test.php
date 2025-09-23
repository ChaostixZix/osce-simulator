<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\AssessmentPromptManager;
use App\Models\OsceSession;
use App\Models\OsceCase;
use App\Models\User;
use App\Models\OsceChatMessage;
use App\Models\SessionOrderedTest;
use App\Models\MedicalTest;
use App\Models\SessionExamination;
use Illuminate\Support\Facades\DB;

class MultiPromptAssessmentTest
{
    private AssessmentPromptManager $promptManager;
    
    public function __construct()
    {
        // Bootstrap Laravel
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        $this->promptManager = new AssessmentPromptManager();
    }
    
    /**
     * Create mock OSCE session data for testing
     */
    private function createMockSession(): OsceSession
    {
        DB::beginTransaction();
        
        try {
            // Create user
            $user = User::firstOrCreate([
                'email' => 'test.student@example.com'
            ], [
                'name' => 'Test Student',
                'password' => bcrypt('password')
            ]);
            
            // Create OSCE case
            $case = OsceCase::firstOrCreate([
                'title' => 'Acute Chest Pain - Test Case'
            ], [
                'description' => 'A 65-year-old male presents with sudden onset chest pain',
                'chief_complaint' => 'Chest pain',
                'difficulty' => 'intermediate',
                'duration_minutes' => 15,
                'budget' => 1000,
                'key_history_points' => [
                    'Onset of pain',
                    'Location and radiation',
                    'Associated symptoms',
                    'Risk factors for CAD',
                    'Previous cardiac history'
                ],
                'critical_examinations' => [
                    'Cardiovascular examination',
                    'Respiratory examination',
                    'Vital signs'
                ],
                'required_tests' => [
                    'ECG',
                    'Cardiac enzymes'
                ]
            ]);
            
            // Create session
            $session = OsceSession::firstOrCreate([
                'user_id' => $user->id,
                'osce_case_id' => $case->id,
                'status' => 'completed',
                'completed_at' => now(),
                'duration_minutes' => 15,
                'total_test_cost' => 250
            ]);
            
            // Mock chat messages (History-taking)
            $messages = [
                [
                    'message' => 'Hello, I\'m Dr. Smith. What brings you in today?',
                    'sender' => 'user',
                    'timestamp' => now()->subMinutes(14)
                ],
                [
                    'message' => 'I have this really bad pain in my chest, doctor. It started about 2 hours ago suddenly.',
                    'sender' => 'patient',
                    'timestamp' => now()->subMinutes(13)
                ],
                [
                    'message' => 'Can you tell me more about the pain? Where exactly is it and does it go anywhere else?',
                    'sender' => 'user',
                    'timestamp' => now()->subMinutes(12)
                ],
                [
                    'message' => 'It\'s right here in the center of my chest, and it feels like someone is squeezing me. It also goes to my left arm.',
                    'sender' => 'patient',
                    'timestamp' => now()->subMinutes(11)
                ],
                [
                    'message' => 'Are you having any other symptoms? Like shortness of breath or sweating?',
                    'sender' => 'user',
                    'timestamp' => now()->subMinutes(10)
                ],
                [
                    'message' => 'Yes, I\'m feeling quite breathless and I\'m sweating a lot. This has never happened before.',
                    'sender' => 'patient',
                    'timestamp' => now()->subMinutes(9)
                ],
                [
                    'message' => 'Do you have any medical problems? Do you smoke or have diabetes?',
                    'sender' => 'user',
                    'timestamp' => now()->subMinutes(8)
                ],
                [
                    'message' => 'I have high blood pressure and I\'ve been smoking for 30 years. No diabetes though.',
                    'sender' => 'patient',
                    'timestamp' => now()->subMinutes(7)
                ]
            ];
            
            foreach ($messages as $msg) {
                OsceChatMessage::firstOrCreate([
                    'osce_session_id' => $session->id,
                    'message' => $msg['message'],
                    'sender' => $msg['sender'],
                    'timestamp' => $msg['timestamp']
                ]);
            }
            
            // Mock examinations
            $examinations = [
                [
                    'examination_category' => 'Cardiovascular',
                    'examination_type' => 'General Inspection',
                    'findings' => 'Patient appears distressed, diaphoretic',
                    'performed_at' => now()->subMinutes(6)
                ],
                [
                    'examination_category' => 'Cardiovascular',
                    'examination_type' => 'Pulse',
                    'findings' => 'Radial pulse 110 bpm, regular',
                    'performed_at' => now()->subMinutes(5)
                ],
                [
                    'examination_category' => 'Cardiovascular',
                    'examination_type' => 'Blood Pressure',
                    'findings' => 'BP 160/100 mmHg',
                    'performed_at' => now()->subMinutes(4)
                ],
                [
                    'examination_category' => 'Cardiovascular',
                    'examination_type' => 'Heart Sounds',
                    'findings' => 'Normal S1 S2, no murmurs',
                    'performed_at' => now()->subMinutes(3)
                ],
                [
                    'examination_category' => 'Respiratory',
                    'examination_type' => 'Chest Auscultation',
                    'findings' => 'Clear breath sounds bilaterally',
                    'performed_at' => now()->subMinutes(2)
                ]
            ];
            
            foreach ($examinations as $exam) {
                SessionExamination::firstOrCreate([
                    'osce_session_id' => $session->id,
                    'examination_category' => $exam['examination_category'],
                    'examination_type' => $exam['examination_type'],
                    'findings' => $exam['findings'],
                    'performed_at' => $exam['performed_at']
                ]);
            }
            
            // Mock ordered tests
            $tests = [
                [
                    'test_name' => 'ECG',
                    'cost' => 50,
                    'order_time' => now()->subMinutes(5)
                ],
                [
                    'test_name' => 'Cardiac Troponin',
                    'cost' => 100,
                    'order_time' => now()->subMinutes(4)
                ],
                [
                    'test_name' => 'Chest X-ray',
                    'cost' => 100,
                    'order_time' => now()->subMinutes(3)
                ]
            ];
            
            foreach ($tests as $test) {
                $medicalTest = MedicalTest::firstOrCreate([
                    'name' => $test['test_name'],
                    'category' => 'Cardiac',
                    'cost' => $test['cost']
                ]);
                
                SessionOrderedTest::firstOrCreate([
                    'osce_session_id' => $session->id,
                    'medical_test_id' => $medicalTest->id,
                    'cost' => $test['cost'],
                    'ordered_at' => $test['order_time']
                ]);
            }
            
            DB::commit();
            return $session;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Create mock artifact for assessment
     */
    private function createMockArtifact(OsceSession $session): array
    {
        return [
            'session' => [
                'id' => $session->id,
                'duration_minutes' => $session->duration_minutes,
                'total_test_cost' => $session->total_test_cost,
                'status' => $session->status
            ],
            'case' => [
                'title' => $session->osceCase->title,
                'chief_complaint' => $session->osceCase->chief_complaint,
                'difficulty' => $session->osceCase->difficulty,
                'key_history_points' => $session->osceCase->key_history_points ?? [],
                'critical_examinations' => $session->osceCase->critical_examinations ?? [],
                'required_tests' => $session->osceCase->required_tests ?? [],
                'budget' => $session->osceCase->budget ?? 1000
            ],
            'detailed_analysis' => [
                'communication' => [
                    'conversation_flow' => $session->chatMessages->map(function ($msg) {
                        return [
                            'sender' => $msg->sender,
                            'message' => $msg->message,
                            'timestamp' => $msg->timestamp->toISOString(),
                            'message_type' => $msg->sender === 'user' ? 'question' : 'response'
                        ];
                    })->toArray()
                ],
                'examinations' => [
                    'examination_timeline' => $session->examinations->map(function ($exam) {
                        return [
                            'category' => $exam->examination_category,
                            'type' => $exam->examination_type,
                            'findings' => $exam->findings,
                            'performed_at' => $exam->performed_at->toISOString()
                        ];
                    })->toArray(),
                    'critical_examinations_status' => [
                        'performed' => ['Cardiovascular examination', 'Vital signs', 'Heart Sounds'],
                        'missed' => ['JVP examination'],
                        'percentage_performed' => 75
                    ],
                    'body_systems_examined' => ['Cardiovascular', 'Respiratory'],
                    'systematic_approach_score' => 7
                ],
                'tests' => [
                    'test_ordering_timeline' => $session->orderedTests->map(function ($test) {
                        return [
                            'test_name' => $test->medicalTest->name,
                            'category' => $test->medicalTest->category,
                            'cost' => $test->cost,
                            'ordered_at' => $test->ordered_at->toISOString()
                        ];
                    })->toArray(),
                    'required_tests_status' => [
                        'ordered' => ['ECG', 'Cardiac Troponin'],
                        'missed' => [],
                        'percentage_ordered' => 100
                    ],
                    'appropriate_tests_status' => [
                        'appropriate' => ['ECG', 'Cardiac Troponin', 'Chest X-ray'],
                        'inappropriate' => [],
                        'percentage_appropriate' => 100
                    ],
                    'cost_effectiveness' => [
                        'total_cost' => 250,
                        'budget_used_percentage' => 25,
                        'cost_effective' => true
                    ]
                ],
                'timing' => [
                    'time_distribution' => [
                        'history_taking' => 7,
                        'examination' => 5,
                        'investigation_planning' => 3
                    ],
                    'overall_efficiency' => 'good'
                ]
            ]
        ];
    }
    
    /**
     * Test aspect prompts generation
     */
    public function testAspectPrompts(): array
    {
        echo "=== Testing Multi-Prompt Assessment System ===\n\n";
        
        // Create mock session
        $session = $this->createMockSession();
        echo "✓ Created mock OSCE session #{$session->id}\n";
        
        // Create mock artifact
        $artifact = $this->createMockArtifact($session);
        echo "✓ Created mock assessment artifact\n\n";
        
        $results = [];
        
        // Test each clinical area and its aspects
        foreach ($this->promptManager->getAllClinicalAreas() as $clinicalArea) {
            echo "--- Testing Clinical Area: " . ucfirst($clinicalArea) . " ---\n";
            
            $aspects = $this->promptManager->getAspectsForClinicalArea($clinicalArea);
            $areaResults = [];
            
            foreach ($aspects as $aspect) {
                echo "\n• Aspect: " . ucfirst(str_replace('_', ' ', $aspect)) . "\n";
                
                // Generate prompt for this aspect
                $promptData = $this->promptManager->buildAspectPrompt(
                    $session, 
                    $clinicalArea, 
                    $aspect,
                    $artifact
                );
                
                // Show key details
                echo "  - Max Score: {$promptData['config']['max_score']}\n";
                echo "  - Acceptable: {$promptData['config']['acceptable_criteria']}\n";
                echo "  - Good: {$promptData['config']['good_criteria']}\n";
                
                // Simulate AI assessment (mock result)
                $mockScore = rand(
                    (int)($promptData['config']['max_score'] * 0.7),
                    $promptData['config']['max_score']
                );
                
                $percentage = ($mockScore / $promptData['config']['max_score']) * 100;
                $performanceLevel = $percentage >= 80 ? 'good' : ($percentage >= 60 ? 'acceptable' : 'needs_improvement');
                
                $areaResults[$aspect] = [
                    'score' => $mockScore,
                    'max_score' => $promptData['config']['max_score'],
                    'percentage' => $percentage,
                    'performance_level' => $performanceLevel,
                    'mock_feedback' => $this->generateMockFeedback($clinicalArea, $aspect, $performanceLevel)
                ];
                
                echo "  - Mock Score: {$mockScore}/{$promptData['config']['max_score']} ({$percentage}%)\n";
                echo "  - Performance Level: {$performanceLevel}\n";
            }
            
            // Calculate overall score for clinical area
            $overallResult = $this->promptManager->calculateOverallScore($areaResults);
            
            echo "\n  Overall Result for {$clinicalArea}:\n";
            echo "  - Total Score: {$overallResult['total_score']}/{$overallResult['max_score']}\n";
            echo "  - Percentage: " . round($overallResult['percentage'], 1) . "%\n";
            echo "  - Performance Level: {$overallResult['performance_level']}\n";
            echo "  - Aspects at Good: {$overallResult['aspects_at_good']}/{$overallResult['total_aspects']}\n";
            
            $results[$clinicalArea] = [
                'aspects' => $areaResults,
                'overall' => $overallResult
            ];
            
            echo "\n";
        }
        
        // Generate final summary
        $this->generateSummaryReport($results);
        
        return $results;
    }
    
    /**
     * Generate mock feedback based on aspect and performance
     */
    private function generateMockFeedback(string $clinicalArea, string $aspect, string $performanceLevel): string
    {
        $feedbackTemplates = [
            'history' => [
                'systematic_approach' => [
                    'good' => 'Excellent systematic approach following a logical sequence from presenting complaint to relevant history',
                    'acceptable' => 'Good coverage of most history domains, could improve flow',
                    'needs_improvement' => 'History taking lacks structure, jumps between topics'
                ],
                'question_quality' => [
                    'good' => 'Superb use of open-ended questions with effective follow-up',
                    'acceptable' => 'Mix of open and closed questions, some leading questions detected',
                    'needs_improvement' => 'Primarily uses closed questions, misses opportunities for open exploration'
                ],
                'thoroughness' => [
                    'good' => 'Comprehensive coverage of all key history points including risk factors',
                    'acceptable' => 'Covers most important aspects but misses some key points',
                    'needs_improvement' => 'Incomplete history, misses critical information'
                ]
            ],
            'exam' => [
                'technique' => [
                    'good' => 'Flawless examination technique with proper positioning and exposure',
                    'acceptable' => 'Adequate technique, minor issues with patient positioning',
                    'needs_improvement' => 'Poor technique, needs significant improvement'
                ],
                'systematic_approach' => [
                    'good' => 'Highly systematic examination following proper sequence',
                    'acceptable' => 'Mostly systematic approach with some disorganization',
                    'needs_improvement' => 'Unsystematic examination, lacks clear structure'
                ],
                'critical_exams' => [
                    'good' => 'All critical examinations performed with proper technique',
                    'acceptable' => 'Most critical examinations performed',
                    'needs_improvement' => 'Misses several critical examinations'
                ]
            ]
        ];
        
        $defaultFeedback = "Student shows {$performanceLevel} performance in {$aspect}";
        
        return $feedbackTemplates[$clinicalArea][$aspect][$performanceLevel] ?? $defaultFeedback;
    }
    
    /**
     * Generate summary report
     */
    private function generateSummaryReport(array $results): void
    {
        echo "=== FINAL SUMMARY REPORT ===\n\n";
        
        $totalScore = 0;
        $totalMaxScore = 0;
        $areaBreakdown = [];
        
        foreach ($results as $area => $data) {
            $totalScore += $data['overall']['total_score'];
            $totalMaxScore += $data['overall']['max_score'];
            
            $areaBreakdown[] = [
                'area' => ucfirst(str_replace('_', ' ', $area)),
                'score' => $data['overall']['total_score'],
                'max_score' => $data['overall']['max_score'],
                'percentage' => round($data['overall']['percentage'], 1),
                'performance' => $data['overall']['performance_level']
            ];
        }
        
        $overallPercentage = $totalMaxScore > 0 ? ($totalScore / $totalMaxScore) * 100 : 0;
        
        echo "Overall Assessment Score: {$totalScore}/{$totalMaxScore} (" . round($overallPercentage, 1) . "%)\n\n";
        
        echo "Performance by Area:\n";
        foreach ($areaBreakdown as $area) {
            $badge = $area['performance'] === 'good' ? '🟢' : ($area['performance'] === 'acceptable' ? '🟡' : '🔴');
            echo "{$badge} {$area['area']}: {$area['score']}/{$area['max_score']} ({$area['percentage']}%)\n";
        }
        
        echo "\nKey Strengths:\n";
        foreach ($results as $area => $data) {
            foreach ($data['aspects'] as $aspect => $result) {
                if ($result['performance_level'] === 'good') {
                    echo "• Excellent " . str_replace('_', ' ', $aspect) . " in " . str_replace('_', ' ', $area) . "\n";
                }
            }
        }
        
        echo "\nAreas for Improvement:\n";
        foreach ($results as $area => $data) {
            foreach ($data['aspects'] as $aspect => $result) {
                if ($result['performance_level'] !== 'good') {
                    echo "• Improve " . str_replace('_', ' ', $aspect) . " in " . str_replace('_', ' ', $area) . "\n";
                }
            }
        }
        
        echo "\n=== TEST COMPLETED ===\n";
    }
}

// Run the test
if (php_sapi_name() === 'cli') {
    $test = new MultiPromptAssessmentTest();
    $results = $test->testAspectPrompts();
}