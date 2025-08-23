<?php

namespace App\Services;

use App\Models\OsceSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAssessorService
{
    private ?string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
    }

    public function assess(OsceSession $session, bool $force = false): OsceSession
    {
        // Skip if already assessed and not forced
        if ($session->assessed_at && !$force) {
            return $session;
        }

        // Check if AI is configured
        if (!$this->isConfigured()) {
            // AI not available - return without assessment
            $session->update([
                'score' => null,
                'max_score' => null,
                'assessor_payload' => null,
                'assessor_output' => [
                    'error' => 'AI is not available right now and no scoring done',
                    'message' => 'Assessment requires AI functionality which is currently unavailable. Please try again later.',
                    'status' => 'ai_unavailable',
                ],
                'assessed_at' => now(),
                'assessor_model' => 'unavailable',
                'rubric_version' => null,
            ]);

            return $session;
        }

        // Load all necessary relationships
        $session->load([
            'osceCase',
            'chatMessages',
            'orderedTests.medicalTest',
            'examinations'
        ]);

        // Build assessment artifact
        $artifact = $this->buildArtifact($session);
        
        // Get scoring configuration
        $config = config('osce_scoring');

        // Get AI assessment with direct session scoring (no rubric dependency)
        $assessorOutput = $this->getAiSessionAssessment($artifact, $session);

        if (isset($assessorOutput['error'])) {
            // AI assessment failed - return error state
            $session->update([
                'score' => null,
                'max_score' => null,
                'assessor_payload' => $artifact,
                'assessor_output' => $assessorOutput,
                'assessed_at' => now(),
                'assessor_model' => $this->model,
                'rubric_version' => null, // No rubric dependency
            ]);

            return $session;
        }

        // Use AI-determined total scores directly
        $totalScore = $assessorOutput['total_score'];
        $maxScore = $assessorOutput['max_possible_score'];

        // Persist results
        $session->update([
            'score' => $totalScore,
            'max_score' => $maxScore,
            'assessor_payload' => $artifact,
            'assessor_output' => $assessorOutput,
            'assessed_at' => now(),
            'assessor_model' => $this->model,
            'rubric_version' => null, // AI scoring without rubric
        ]);

        return $session;
    }

    public function buildArtifact(OsceSession $session): array
    {
        // Get last 30 chat messages to bound context
        $recentMessages = $session->chatMessages()
            ->latest('sent_at')
            ->take(30)
            ->get()
            ->reverse()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'sender_type' => $message->sender_type,
                    'text' => $message->message,
                    'sent_at' => $message->sent_at->toISOString(),
                ];
            })
            ->values()
            ->toArray();

        // Get ordered tests with details
        $tests = $session->orderedTests->map(function ($orderedTest) {
            return [
                'id' => $orderedTest->id,
                'test_name' => $orderedTest->medicalTest?->name ?? 'Unknown Test',
                'test_category' => $orderedTest->medicalTest?->category ?? 'unknown',
                'cost' => $orderedTest->medicalTest?->cost ?? 0,
                'ordered_at' => $orderedTest->ordered_at->toISOString(),
                'result' => $orderedTest->result,
            ];
        })->toArray();

        // Get examinations
        $examinations = $session->examinations->map(function ($exam) {
            return [
                'id' => $exam->id,
                'examination_type' => $exam->examination_type,
                'body_part' => $exam->body_part,
                'finding' => $exam->finding,
                'performed_at' => $exam->performed_at->toISOString(),
            ];
        })->toArray();

        // Calculate timing metrics
        $elapsedMinutes = $session->elapsed_seconds / 60;
        $durationMinutes = $session->duration_minutes;
        $totalCost = $session->orderedTests->sum(fn($test) => $test->medicalTest?->cost ?? 0);

        // Get case context
        $case = $session->osceCase;
        $caseContext = [
            'id' => $case->id,
            'title' => $case->title,
            'chief_complaint' => $case->chief_complaint,
            'duration_minutes' => $case->duration_minutes,
            'budget' => $case->budget ?? 1000,
            'learning_objectives' => $case->learning_objectives ?? [],
            'required_tests' => $case->required_tests ?? [],
            'highly_appropriate_tests' => $case->highly_appropriate_tests ?? [],
            'contraindicated_tests' => $case->contraindicated_tests ?? [],
            'key_history_points' => $case->key_history_points ?? [],
            'critical_examinations' => $case->critical_examinations ?? [],
        ];

        // Build detailed test analysis
        $testAnalysis = [
            'total_tests_ordered' => count($tests),
            'total_cost_spent' => $totalCost,
            'budget_remaining' => ($case->budget ?? 1000) - $totalCost,
            'tests_by_category' => [],
            'test_ordering_timeline' => [],
            'required_tests_status' => [],
            'appropriate_tests_status' => [],
            'contraindicated_tests_ordered' => [],
        ];

        // Categorize tests
        foreach ($tests as $test) {
            $category = $test['test_category'];
            if (!isset($testAnalysis['tests_by_category'][$category])) {
                $testAnalysis['tests_by_category'][$category] = [];
            }
            $testAnalysis['tests_by_category'][$category][] = $test;
            
            $testAnalysis['test_ordering_timeline'][] = [
                'test_name' => $test['test_name'],
                'cost' => $test['cost'],
                'ordered_at' => $test['ordered_at'],
                'result' => $test['result'],
            ];
        }

        // Check required tests
        $orderedTestNames = array_column($tests, 'test_name');
        $requiredTests = $case->required_tests ?? [];
        foreach ($requiredTests as $requiredTest) {
            $testAnalysis['required_tests_status'][] = [
                'test_name' => $requiredTest,
                'ordered' => in_array($requiredTest, $orderedTestNames),
                'status' => in_array($requiredTest, $orderedTestNames) ? 'ORDERED' : 'MISSING',
            ];
        }

        // Check appropriate tests
        $appropriateTests = $case->highly_appropriate_tests ?? [];
        foreach ($appropriateTests as $appropriateTest) {
            $testAnalysis['appropriate_tests_status'][] = [
                'test_name' => $appropriateTest,
                'ordered' => in_array($appropriateTest, $orderedTestNames),
                'status' => in_array($appropriateTest, $orderedTestNames) ? 'ORDERED' : 'NOT_ORDERED',
            ];
        }

        // Check contraindicated tests
        $contraindicatedTests = $case->contraindicated_tests ?? [];
        foreach ($contraindicatedTests as $contraindicatedTest) {
            if (in_array($contraindicatedTest, $orderedTestNames)) {
                $testAnalysis['contraindicated_tests_ordered'][] = $contraindicatedTest;
            }
        }

        // Build detailed examination analysis
        $examinationAnalysis = [
            'total_examinations_performed' => count($examinations),
            'examinations_by_type' => [],
            'examination_timeline' => [],
            'critical_examinations_status' => [],
            'body_systems_examined' => [],
        ];

        // Categorize examinations
        foreach ($examinations as $exam) {
            $type = $exam['examination_type'];
            if (!isset($examinationAnalysis['examinations_by_type'][$type])) {
                $examinationAnalysis['examinations_by_type'][$type] = [];
            }
            $examinationAnalysis['examinations_by_type'][$type][] = $exam;
            
            $examinationAnalysis['examination_timeline'][] = [
                'examination_type' => $exam['examination_type'],
                'body_part' => $exam['body_part'],
                'finding' => $exam['finding'],
                'performed_at' => $exam['performed_at'],
            ];

            // Track body systems
            $bodySystem = $this->categorizeBodySystem($exam['examination_type'], $exam['body_part']);
            if (!in_array($bodySystem, $examinationAnalysis['body_systems_examined'])) {
                $examinationAnalysis['body_systems_examined'][] = $bodySystem;
            }
        }

        // Check critical examinations
        $criticalExams = $case->critical_examinations ?? [];
        foreach ($criticalExams as $criticalExam) {
            $found = false;
            foreach ($examinations as $exam) {
                if (stripos($exam['examination_type'] . ' ' . $exam['body_part'], $criticalExam) !== false) {
                    $found = true;
                    break;
                }
            }
            $examinationAnalysis['critical_examinations_status'][] = [
                'examination' => $criticalExam,
                'performed' => $found,
                'status' => $found ? 'PERFORMED' : 'MISSING',
            ];
        }

        // Build communication analysis
        $communicationAnalysis = [
            'total_messages' => count($recentMessages),
            'user_messages' => count(array_filter($recentMessages, fn($msg) => $msg['sender_type'] === 'user')),
            'system_messages' => count(array_filter($recentMessages, fn($msg) => $msg['sender_type'] === 'system')),
            'average_message_length' => count($recentMessages) > 0 ? 
                array_sum(array_map(fn($msg) => strlen($msg['text']), $recentMessages)) / count($recentMessages) : 0,
            'question_patterns' => [],
            'conversation_flow' => [],
        ];

        // Analyze question patterns
        $userMessages = array_filter($recentMessages, fn($msg) => $msg['sender_type'] === 'user');
        foreach ($userMessages as $msg) {
            $text = $msg['text'];
            $questionType = $this->categorizeQuestionType($text);
            if (!isset($communicationAnalysis['question_patterns'][$questionType])) {
                $communicationAnalysis['question_patterns'][$questionType] = 0;
            }
            $communicationAnalysis['question_patterns'][$questionType]++;
            
            $communicationAnalysis['conversation_flow'][] = [
                'message_id' => $msg['id'],
                'text' => $text,
                'question_type' => $questionType,
                'sent_at' => $msg['sent_at'],
            ];
        }

        return [
            'session_id' => $session->id,
            'assessment_type' => 'holistic_session_assessment',
            'case' => $caseContext,
            'transcript' => $recentMessages,
            'actions' => [
                'tests' => $tests,
                'examinations' => $examinations,
            ],
            'detailed_analysis' => [
                'tests' => $testAnalysis,
                'examinations' => $examinationAnalysis,
                'communication' => $communicationAnalysis,
            ],
            'metrics' => [
                'total_cost' => $totalCost,
                'case_budget' => $case->budget ?? 1000,
                'elapsed_minutes' => round($elapsedMinutes, 2),
                'duration_minutes' => $durationMinutes,
                'time_remaining' => max(0, $durationMinutes - $elapsedMinutes),
                'started_at' => $session->started_at?->toISOString(),
                'completed_at' => $session->completed_at?->toISOString(),
                'time_extended' => $session->time_extended ?? 0,
            ],
        ];
    }

    public function computeScores(OsceSession $session, array $config): array
    {
        $case = $session->osceCase;
        $scores = [];

        foreach ($config['criteria'] as $criterion) {
            $score = $this->computeCriterionScore($criterion, $session, $case, $config);
            $scores[] = [
                'key' => $criterion['key'],
                'score' => $score,
                'max' => $criterion['max'],
            ];
        }

        return $scores;
    }

    private function computeCriterionScore(array $criterion, OsceSession $session, $case, array $config): int
    {
        $key = $criterion['key'];
        $max = $criterion['max'];
        $weights = $config['weights'][$key] ?? [];

        switch ($key) {
            case 'history':
                return $this->scoreHistory($session, $case, $max, $weights);
            
            case 'exam':
                return $this->scoreExamination($session, $case, $max, $weights);
            
            case 'investigations':
                return $this->scoreInvestigations($session, $case, $max, $weights, $config['penalties']);
            
            case 'diagnosis':
                return $this->scoreDiagnosis($session, $case, $max, $weights);
            
            case 'management':
                return $this->scoreManagement($session, $case, $max, $weights);
            
            case 'communication':
                return $this->scoreCommunication($session, $case, $max, $weights);
            
            case 'safety':
                return $this->scoreSafety($session, $case, $max, $weights);
            
            default:
                return 0;
        }
    }

    private function scoreHistory(OsceSession $session, $case, int $max, array $weights): int
    {
        $keyPoints = $case->key_history_points ?? [];
        $chatMessages = $session->chatMessages->where('sender_type', 'user');
        
        if (empty($keyPoints) || $chatMessages->isEmpty()) {
            return (int) ($max * 0.5); // Base score if no specific criteria
        }

        $coveredPoints = 0;
        $totalQuestions = $chatMessages->count();
        
        foreach ($keyPoints as $point) {
            $found = $chatMessages->filter(function ($message) use ($point) {
                return stripos($message->message, $point) !== false;
            })->isNotEmpty();
            
            if ($found) {
                $coveredPoints++;
            }
        }

        $coverage = count($keyPoints) > 0 ? $coveredPoints / count($keyPoints) : 0.5;
        $efficiency = min(1.0, 10 / max(1, $totalQuestions)); // Penalty for too many questions
        
        $score = $coverage * ($weights['appropriate_questions'] ?? 0.6) * $max +
                $coverage * ($weights['thoroughness'] ?? 0.3) * $max +
                $efficiency * ($weights['efficiency'] ?? 0.1) * $max;

        return min($max, (int) round($score));
    }

    private function scoreExamination(OsceSession $session, $case, int $max, array $weights): int
    {
        $criticalExams = $case->critical_examinations ?? [];
        $examinations = $session->examinations;
        
        if (empty($criticalExams) || $examinations->isEmpty()) {
            return (int) ($max * 0.5);
        }

        $performedCritical = 0;
        foreach ($criticalExams as $critical) {
            $found = $examinations->filter(function ($exam) use ($critical) {
                return stripos($exam->examination_type . ' ' . $exam->body_part, $critical) !== false;
            })->isNotEmpty();
            
            if ($found) {
                $performedCritical++;
            }
        }

        $relevance = count($criticalExams) > 0 ? $performedCritical / count($criticalExams) : 0.5;
        $technique = min(1.0, $examinations->count() / max(1, count($criticalExams)));
        
        $score = $relevance * ($weights['relevant_examinations'] ?? 0.7) * $max +
                min(1.0, $technique) * ($weights['technique'] ?? 0.3) * $max;

        return min($max, (int) round($score));
    }

    private function scoreInvestigations(OsceSession $session, $case, int $max, array $weights, array $penalties): int
    {
        $requiredTests = $case->required_tests ?? [];
        $appropriateTests = $case->highly_appropriate_tests ?? [];
        $contraindicatedTests = $case->contraindicated_tests ?? [];
        $orderedTests = $session->orderedTests;
        $budget = $case->budget ?? 1000;
        
        $testNames = $orderedTests->pluck('medicalTest.name')->filter()->toArray();
        $totalCost = $orderedTests->sum(fn($test) => $test->medicalTest?->cost ?? 0);
        
        // Check required tests
        $requiredScore = 0;
        if (!empty($requiredTests)) {
            $foundRequired = 0;
            foreach ($requiredTests as $required) {
                if (in_array($required, $testNames)) {
                    $foundRequired++;
                }
            }
            $requiredScore = count($requiredTests) > 0 ? $foundRequired / count($requiredTests) : 1;
        } else {
            $requiredScore = 1; // No penalties if no required tests specified
        }
        
        // Check appropriate tests
        $appropriateScore = 1;
        if (!empty($appropriateTests)) {
            $foundAppropriate = 0;
            foreach ($testNames as $testName) {
                if (in_array($testName, $appropriateTests)) {
                    $foundAppropriate++;
                }
            }
            $appropriateScore = count($testNames) > 0 ? $foundAppropriate / count($testNames) : 1;
        }
        
        // Cost effectiveness
        $costScore = $totalCost <= $budget ? 1 : max(0, 1 - (($totalCost - $budget) / $budget));
        
        // Apply penalties
        $penaltyDeduction = 0;
        foreach ($testNames as $testName) {
            if (in_array($testName, $contraindicatedTests)) {
                $penaltyDeduction += $penalties['contraindicated_test'] ?? 5;
            }
        }
        
        // Missing required tests penalty
        $missedRequired = count($requiredTests) - count(array_intersect($requiredTests, $testNames));
        $penaltyDeduction += $missedRequired * ($penalties['missed_required_test'] ?? 3);
        
        // Over budget penalty
        if ($totalCost > $budget) {
            $penaltyDeduction += $penalties['over_budget'] ?? 2;
        }
        
        $baseScore = $requiredScore * ($weights['appropriate_tests'] ?? 0.5) * $max +
                    $costScore * ($weights['cost_effectiveness'] ?? 0.3) * $max +
                    $appropriateScore * ($weights['timing'] ?? 0.2) * $max;
        
        $finalScore = max(0, $baseScore - $penaltyDeduction);
        
        return min($max, (int) round($finalScore));
    }

    private function scoreDiagnosis(OsceSession $session, $case, int $max, array $weights): int
    {
        // This would typically analyze the final diagnosis/reasoning
        // For now, return a base score since diagnosis evaluation isn't fully implemented
        return (int) ($max * 0.7);
    }

    private function scoreManagement(OsceSession $session, $case, int $max, array $weights): int
    {
        // This would analyze management plans mentioned in chat or clinical reasoning
        // For now, return a base score
        return (int) ($max * 0.7);
    }

    private function scoreCommunication(OsceSession $session, $case, int $max, array $weights): int
    {
        $chatMessages = $session->chatMessages->where('sender_type', 'user');
        
        if ($chatMessages->isEmpty()) {
            return 0;
        }
        
        // Basic communication scoring based on message characteristics
        $totalMessages = $chatMessages->count();
        $averageLength = $chatMessages->avg(fn($msg) => strlen($msg->message));
        
        // Score based on interaction quality (rough heuristic)
        $clarityScore = min(1.0, $averageLength / 50); // Reasonable message length
        $professionalismScore = 0.8; // Default assumption
        $empathyScore = 0.7; // Default assumption
        
        $score = $clarityScore * ($weights['clarity'] ?? 0.5) * $max +
                $empathyScore * ($weights['empathy'] ?? 0.3) * $max +
                $professionalismScore * ($weights['professionalism'] ?? 0.2) * $max;
        
        return min($max, (int) round($score));
    }

    private function scoreSafety(OsceSession $session, $case, int $max, array $weights): int
    {
        $elapsedMinutes = $session->elapsed_seconds / 60;
        $durationMinutes = $session->duration_minutes;
        
        // Time management score
        $timeScore = 1.0;
        if ($elapsedMinutes > $durationMinutes) {
            $timeScore = max(0, 1 - (($elapsedMinutes - $durationMinutes) / $durationMinutes));
        } else if ($elapsedMinutes < $durationMinutes * 0.5) {
            $timeScore = 0.8; // Slight penalty for finishing too quickly
        }
        
        // Critical actions (placeholder - would need case-specific analysis)
        $criticalActionsScore = 0.8;
        
        $score = $timeScore * ($weights['time_management'] ?? 0.6) * $max +
                $criticalActionsScore * ($weights['critical_actions'] ?? 0.4) * $max;
        
        return min($max, (int) round($score));
    }

    private function getAiSessionAssessment(array $artifact, OsceSession $session): array
    {
        try {
            return $this->callGeminiForSessionScoring($artifact, $session);
        } catch (\Exception $e) {
            Log::error('AI Session Assessor error', [
                'message' => $e->getMessage(),
                'session_id' => $artifact['session_id'] ?? null,
            ]);
            
            return [
                'error' => 'AI is not available right now and no scoring done',
                'message' => 'AI assessment failed: ' . $e->getMessage(),
                'status' => 'ai_error',
            ];
        }
    }

    private function getAssessment(array $artifact, array $computedScores, array $config): array
    {
        if (!$this->isConfigured()) {
            return $this->getFallbackAssessment($computedScores, $config);
        }

        try {
            return $this->callGemini($artifact, $computedScores, $config);
        } catch (\Exception $e) {
            Log::error('AI Assessor error', [
                'message' => $e->getMessage(),
                'session_id' => $artifact['session_id'] ?? null,
            ]);
            
            return $this->getFallbackAssessment($computedScores, $config);
        }
    }

    private function callGeminiForSessionScoring(array $artifact, OsceSession $session): array
    {
        $prompt = $this->buildSessionScoringPrompt($artifact, $session);
        
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/' . $this->model . ':generateContent?key=' . $this->apiKey, [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'topK' => 1,
                'topP' => 1,
                'maxOutputTokens' => 3000,
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Gemini API error: ' . $response->status() . ' - ' . $response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        // Log the raw response for debugging
        Log::info('Gemini raw response (session scoring)', [
            'session_id' => $artifact['session_id'] ?? null,
            'raw_text' => $text,
            'response_length' => strlen($text)
        ]);
        
        // Try to parse JSON
        $decoded = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('JSON decode failed, attempting repair', [
                'session_id' => $artifact['session_id'] ?? null,
                'json_error' => json_last_error_msg(),
                'text_preview' => substr($text, 0, 200)
            ]);
            
            // Attempt repair
            $repaired = $this->repairSessionJsonResponse($text, $artifact);
            if ($repaired) {
                Log::info('JSON repair successful', ['session_id' => $artifact['session_id'] ?? null]);
                return $repaired;
            }
            
            Log::error('JSON repair failed', [
                'session_id' => $artifact['session_id'] ?? null,
                'original_text' => $text
            ]);
            throw new \Exception('Invalid JSON response from AI');
        }
        
        // Validate schema for session assessment
        if (!$this->validateSessionAssessmentSchema($decoded)) {
            throw new \Exception('Invalid session assessment schema from AI');
        }
        
        // Ensure model name is set
        if (!isset($decoded['model_info']['name']) || empty($decoded['model_info']['name'])) {
            $decoded['model_info']['name'] = $this->model;
        }
        
        return $decoded;
    }

    private function callGeminiForScoring(array $artifact, array $config): array
    {
        $prompt = $this->buildScoringPrompt($artifact, $config);
        
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/' . $this->model . ':generateContent?key=' . $this->apiKey, [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0,
                'topK' => 1,
                'topP' => 1,
                'maxOutputTokens' => 2000,
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Gemini API error: ' . $response->status() . ' - ' . $response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        // Log the raw response for debugging
        Log::info('Gemini raw response (scoring)', [
            'session_id' => $artifact['session_id'] ?? null,
            'raw_text' => $text,
            'response_length' => strlen($text)
        ]);
        
        // Try to parse JSON
        $decoded = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('JSON decode failed, attempting repair', [
                'session_id' => $artifact['session_id'] ?? null,
                'json_error' => json_last_error_msg(),
                'text_preview' => substr($text, 0, 200)
            ]);
            
            // Attempt repair
            $repaired = $this->repairJsonResponse($text, $artifact, [], $config);
            if ($repaired) {
                Log::info('JSON repair successful', ['session_id' => $artifact['session_id'] ?? null]);
                return $repaired;
            }
            
            Log::error('JSON repair failed', [
                'session_id' => $artifact['session_id'] ?? null,
                'original_text' => $text
            ]);
            throw new \Exception('Invalid JSON response from AI');
        }
        
        // Validate schema
        if (!$this->validateAssessmentSchema($decoded)) {
            throw new \Exception('Invalid assessment schema from AI');
        }
        
        // Ensure model name is set
        if (!isset($decoded['model_info']['name']) || empty($decoded['model_info']['name'])) {
            $decoded['model_info']['name'] = $this->model;
        }
        
        return $decoded;
    }

    private function callGemini(array $artifact, array $computedScores, array $config): array
    {
        $prompt = $this->buildAssessmentPrompt($artifact, $computedScores, $config);
        
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/' . $this->model . ':generateContent?key=' . $this->apiKey, [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0,
                'topK' => 1,
                'topP' => 1,
                'maxOutputTokens' => 1500,
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Gemini API error: ' . $response->status() . ' - ' . $response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        // Log the raw response for debugging
        Log::info('Gemini raw response', [
            'session_id' => $artifact['session_id'] ?? null,
            'raw_text' => $text,
            'response_length' => strlen($text)
        ]);
        
        // Try to parse JSON
        $decoded = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('JSON decode failed, attempting repair', [
                'session_id' => $artifact['session_id'] ?? null,
                'json_error' => json_last_error_msg(),
                'text_preview' => substr($text, 0, 200)
            ]);
            
            // Attempt repair
            $repaired = $this->repairJsonResponse($text, $artifact, $computedScores, $config);
            if ($repaired) {
                Log::info('JSON repair successful', ['session_id' => $artifact['session_id'] ?? null]);
                return $repaired;
            }
            
            Log::error('JSON repair failed', [
                'session_id' => $artifact['session_id'] ?? null,
                'original_text' => $text
            ]);
            throw new \Exception('Invalid JSON response from AI');
        }
        
        // Validate schema
        if (!$this->validateAssessmentSchema($decoded)) {
            throw new \Exception('Invalid assessment schema from AI');
        }
        
        // Ensure model name is set
        if (!isset($decoded['model_info']['name']) || empty($decoded['model_info']['name'])) {
            $decoded['model_info']['name'] = $this->model;
        }
        
        return $decoded;
    }

    private function repairJsonResponse(string $text, array $artifact, array $computedScores, array $config): ?array
    {
        try {
            $sessionId = $artifact['session_id'] ?? null;
            $original = $text;
            
            // Try to repair common JSON issues
            $cleaned = trim($text);
            
            // Remove markdown code blocks (various patterns)
            $patterns = [
                '/^```json\s*/',
                '/^```\s*/',
                '/\s*```$/',
                '/^```json\n/',
                '/\n```$/',
            ];
            
            foreach ($patterns as $pattern) {
                $cleaned = preg_replace($pattern, '', $cleaned);
            }
            
            Log::info('JSON repair attempt', [
                'session_id' => $sessionId,
                'original_length' => strlen($original),
                'cleaned_length' => strlen($cleaned),
                'cleaned_preview' => substr($cleaned, 0, 200)
            ]);
            
            $decoded = json_decode($cleaned, true);
            if (json_last_error() === JSON_ERROR_NONE && $this->validateAssessmentSchema($decoded)) {
                Log::info('JSON repair successful', ['session_id' => $sessionId]);
                return $decoded;
            }
            
            // Try extracting JSON from within text
            $matches = [];
            if (preg_match('/\{.*\}/s', $cleaned, $matches)) {
                $extracted = $matches[0];
                $decoded = json_decode($extracted, true);
                if (json_last_error() === JSON_ERROR_NONE && $this->validateAssessmentSchema($decoded)) {
                    Log::info('JSON extraction successful', ['session_id' => $sessionId]);
                    return $decoded;
                }
            }
            
            Log::warning('JSON repair failed', [
                'session_id' => $sessionId,
                'json_error' => json_last_error_msg(),
                'cleaned_text' => $cleaned
            ]);
            
            // If repair fails, return fallback
            return null;
        } catch (\Exception $e) {
            Log::error('JSON repair exception', [
                'session_id' => $artifact['session_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function validateAssessmentSchema(array $data): bool
    {
        $required = ['rubric_version', 'criteria', 'overall_comment', 'red_flags', 'model_info'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        if (!is_array($data['criteria'])) {
            return false;
        }
        
        foreach ($data['criteria'] as $criterion) {
            $requiredFields = ['key', 'score', 'max', 'justification', 'citations'];
            foreach ($requiredFields as $field) {
                if (!isset($criterion[$field])) {
                    return false;
                }
            }
        }
        
        return true;
    }

    private function buildSessionScoringPrompt(array $artifact, OsceSession $session): string
    {
        $artifactJson = json_encode($artifact, JSON_PRETTY_PRINT);
        $caseInfo = $session->osceCase;
        
        return <<<PROMPT
You are an experienced physician examiner conducting an OSCE assessment. You must analyze the complete OSCE session data and provide detailed area-by-area assessment with AI-determined scoring based on medical education best practices.

CRITICAL: YOU MUST ANALYZE EACH CLINICAL AREA SEPARATELY AND ASSIGN SCORES BASED ON EVIDENCE.

Your Role:
- Expert medical educator and examiner
- Focus on clinical competency and patient safety
- Consider the specific case context and learning objectives
- Provide detailed educational feedback with specific evidence and citations

Rules:
- Output MUST be a single JSON object and nothing else
- Analyze EACH clinical area separately with detailed justification
- Assign scores based on evidence from the session data
- Provide extensive citations referencing specific actions, messages, tests, examinations
- Use professional language with comprehensive analysis and specific examples
- Be conservative: do not infer beyond the artifact; flag unsafe or missing steps

Session Data to Analyze:
{$artifactJson}

Case Context:
- Case: {$caseInfo->title}
- Chief Complaint: {$caseInfo->chief_complaint}
- Duration: {$caseInfo->duration_minutes} minutes
- Learning Objectives: Consider what this case aims to teach

DETAILED AREA ASSESSMENT - YOU MUST SCORE EACH AREA:

HISTORY-TAKING (0-20 points): Analyze detailed_analysis.communication.conversation_flow and case.key_history_points
- Score based on: coverage of key history points, question types, systematic approach, thoroughness
- Use transcript messages to assess completeness and clinical reasoning
- Quote specific user messages that demonstrate good/poor history taking
- Example scoring: 0-8 = poor/no history, 9-12 = basic history, 13-16 = good history, 17-20 = excellent comprehensive history

PHYSICAL EXAMINATION (0-15 points): Analyze detailed_analysis.examinations data
- Score based on: critical examinations performed vs missed, systematic approach, findings documentation
- Use examination_timeline and critical_examinations_status for evidence
- Reference specific examinations performed with timestamps and findings
- Example scoring: 0-6 = no/minimal exam, 7-9 = partial exam, 10-12 = good exam, 13-15 = comprehensive exam

INVESTIGATIONS (0-20 points): Analyze detailed_analysis.tests data
- Score based on: appropriate tests ordered, required tests completion, cost management, contraindicated tests avoided
- Use test_ordering_timeline, required_tests_status, appropriate_tests_status for detailed analysis
- Reference specific test orders with costs and timing
- Example scoring: 0-8 = poor test selection, 9-12 = adequate, 13-16 = good, 17-20 = excellent

CLINICAL REASONING & DIAGNOSIS (0-20 points): Analyze overall clinical reasoning from transcript and actions
- Score based on: logical thinking, differential diagnosis consideration, integration of findings, diagnostic accuracy
- Use conversation flow and clinical decision patterns
- Example scoring: 0-8 = no reasoning, 9-12 = basic, 13-16 = good, 17-20 = excellent

MANAGEMENT PLAN (0-15 points): Analyze management discussion in transcript and clinical actions
- Score based on: appropriate treatment plans, safety considerations, follow-up, therapeutic reasoning
- Reference specific management discussions or actions
- Example scoring: 0-6 = no management, 7-9 = basic, 10-12 = appropriate, 13-15 = comprehensive

COMMUNICATION & PROFESSIONALISM (0-5 points): Analyze detailed_analysis.communication data
- Score based on: question types, empathy, professionalism, patient interaction quality, clarity
- Use question_patterns and conversation_flow for evidence
- Quote specific messages showing good/poor communication
- Example scoring: 0-2 = poor communication, 3 = adequate, 4 = good, 5 = excellent

TIME MANAGEMENT & SAFETY (0-5 points): Analyze timing and safety considerations
- Score based on: time efficiency, safe practices, completion within timeframe, prioritization
- Use metrics data for timing analysis and identify any safety concerns
- Example scoring: 0-2 = unsafe/poor time, 3 = adequate, 4 = good, 5 = excellent

Return JSON matching this exact schema:

{
  "total_score": number, // Sum of all area scores
  "max_possible_score": 100,
  "assessment_type": "detailed_clinical_areas_assessment",
  "clinical_areas": [
    {
      "area": "History-Taking",
      "key": "history",
      "score": number, // 0-20 - YOU MUST ASSIGN BASED ON EVIDENCE
      "max_score": 20,
      "justification": string, // COMPREHENSIVE analysis (300+ words) with extensive quotes and specific evidence
      "citations": string[], // Specific refs like ["msg#12: patient asked about chest pain onset","msg#15: systematic cardiovascular history"]
      "strengths": string[], // Specific strengths in this area
      "areas_for_improvement": string[] // Specific improvements needed
    },
    {
      "area": "Physical Examination", 
      "key": "examination",
      "score": number, // 0-15 - YOU MUST ASSIGN BASED ON EVIDENCE
      "max_score": 15,
      "justification": string, // COMPREHENSIVE analysis with examination details, findings, missing exams
      "citations": string[], // Refs like ["exam#3: cardiac auscultation at 14:23","exam#5: missed abdominal examination"]
      "strengths": string[],
      "areas_for_improvement": string[]
    },
    {
      "area": "Investigations",
      "key": "investigations", 
      "score": number, // 0-20 - YOU MUST ASSIGN BASED ON EVIDENCE
      "max_score": 20,
      "justification": string, // COMPREHENSIVE analysis with test details, costs, appropriateness, missing tests
      "citations": string[], // Refs like ["test#2: ECG ordered at 14:15 cost $25","test#4: inappropriate CT Brain $450"]
      "strengths": string[],
      "areas_for_improvement": string[]
    },
    {
      "area": "Clinical Reasoning & Diagnosis",
      "key": "diagnosis",
      "score": number, // 0-20 - YOU MUST ASSIGN BASED ON EVIDENCE  
      "max_score": 20,
      "justification": string, // COMPREHENSIVE analysis of reasoning process and diagnostic thinking
      "citations": string[], // Refs to reasoning patterns in messages
      "strengths": string[],
      "areas_for_improvement": string[]
    },
    {
      "area": "Management Plan",
      "key": "management",
      "score": number, // 0-15 - YOU MUST ASSIGN BASED ON EVIDENCE
      "max_score": 15, 
      "justification": string, // COMPREHENSIVE analysis of management decisions
      "citations": string[], // Refs to management discussions
      "strengths": string[],
      "areas_for_improvement": string[]
    },
    {
      "area": "Communication & Professionalism",
      "key": "communication",
      "score": number, // 0-5 - YOU MUST ASSIGN BASED ON EVIDENCE
      "max_score": 5,
      "justification": string, // COMPREHENSIVE analysis of communication quality
      "citations": string[], // Refs to specific messages showing communication skills
      "strengths": string[],
      "areas_for_improvement": string[]
    },
    {
      "area": "Time Management & Safety", 
      "key": "safety",
      "score": number, // 0-5 - YOU MUST ASSIGN BASED ON EVIDENCE
      "max_score": 5,
      "justification": string, // COMPREHENSIVE analysis of timing and safety
      "citations": string[], // Refs to timing data and safety considerations
      "strengths": string[],
      "areas_for_improvement": string[]
    }
  ],
  "overall_feedback": string, // Comprehensive summary (200-300 words) integrating all areas
  "safety_concerns": string[], // Critical safety issues with specific evidence
  "recommendations": string[], // Specific learning recommendations based on all areas
  "model_info": {
    "name": string,
    "temperature": number,
    "assessment_approach": "detailed_areas_analysis"
  }
}

COMPREHENSIVE ANALYSIS REQUIREMENTS:

INVESTIGATIONS Analysis:
- Use detailed_analysis.tests.test_ordering_timeline to describe EXACT tests ordered with timestamps and costs
- Reference detailed_analysis.tests.required_tests_status to identify MISSING required tests by name
- Reference detailed_analysis.tests.appropriate_tests_status to identify which appropriate tests were/weren't ordered
- Use detailed_analysis.tests.contraindicated_tests_ordered to identify inappropriate test orders
- ASSIGN SCORE based on completeness, appropriateness, and cost management

PHYSICAL EXAMINATION Analysis:
- Use detailed_analysis.examinations.examination_timeline to describe EXACT examinations performed with findings
- Reference detailed_analysis.examinations.critical_examinations_status to identify MISSING critical exams by name
- Use detailed_analysis.examinations.body_systems_examined to show systematic approach
- ASSIGN SCORE based on completeness of critical examinations and systematic approach

HISTORY-TAKING Analysis:
- Use detailed_analysis.communication.conversation_flow to quote SPECIFIC user messages
- Reference detailed_analysis.communication.question_patterns to analyze question types and approach
- Use transcript data to identify covered vs missed history points from case.key_history_points
- ASSIGN SCORE based on thoroughness, systematic approach, and clinical relevance

COMMUNICATION Analysis:
- Use detailed_analysis.communication.average_message_length and question patterns for assessment
- Quote specific messages showing empathy, professionalism, clarity
- ASSIGN SCORE based on communication quality, professionalism, and patient-centered approach

Important Guidelines:
- YOU MUST ASSIGN SPECIFIC SCORES - do not use placeholder scores
- Include EXTENSIVE quotes and evidence in ALL justifications (minimum 300 words per area)
- Reference ALL relevant user actions, messages, test orders, and examination findings
- BE STRICT in scoring - only give high scores when evidence clearly supports it
- Provide detailed citations for every claim made
- Output ONLY the JSON object

SCORING MUST BE EVIDENCE-BASED:
- Only award points for actions/behaviors that are clearly demonstrated in the data
- Deduct points for missed critical elements, unsafe practices, or poor clinical reasoning
- Consider case-specific requirements and learning objectives
- Balance thoroughness with efficiency and appropriateness
PROMPT;
    }

    private function validateSessionAssessmentSchema(array $data): bool
    {
        // Check for detailed areas assessment format
        if (isset($data['clinical_areas']) && is_array($data['clinical_areas'])) {
            return $this->validateDetailedAreasAssessment($data);
        }
        
        // Check for legacy holistic assessment format
        $required = ['total_score', 'max_possible_score', 'assessment_type', 'strengths', 'areas_for_improvement', 
                    'clinical_reasoning_analysis', 'safety_concerns', 'overall_feedback', 'score_justification', 
                    'recommendations', 'model_info'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                Log::error('Missing required field in session assessment', ['field' => $field]);
                return false;
            }
        }
        
        // Validate score is numeric and within range
        if (!is_numeric($data['total_score']) || $data['total_score'] < 0 || $data['total_score'] > $data['max_possible_score']) {
            Log::error('Invalid score in session assessment', ['score' => $data['total_score']]);
            return false;
        }
        
        return true;
    }

    private function validateDetailedAreasAssessment(array $data): bool
    {
        $required = ['total_score', 'max_possible_score', 'assessment_type', 'clinical_areas', 
                    'overall_feedback', 'safety_concerns', 'recommendations', 'model_info'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                Log::error('Missing required field in detailed areas assessment', ['field' => $field]);
                return false;
            }
        }
        
        // Validate score is numeric and within range
        if (!is_numeric($data['total_score']) || $data['total_score'] < 0 || $data['total_score'] > $data['max_possible_score']) {
            Log::error('Invalid total score in detailed areas assessment', ['score' => $data['total_score']]);
            return false;
        }
        
        // Validate clinical areas
        if (!is_array($data['clinical_areas']) || empty($data['clinical_areas'])) {
            Log::error('Clinical areas must be a non-empty array');
            return false;
        }
        
        foreach ($data['clinical_areas'] as $index => $area) {
            $requiredAreaFields = ['area', 'key', 'score', 'max_score', 'justification', 'citations', 'strengths', 'areas_for_improvement'];
            
            foreach ($requiredAreaFields as $field) {
                if (!isset($area[$field])) {
                    Log::error('Missing required field in clinical area', ['area_index' => $index, 'field' => $field]);
                    return false;
                }
            }
            
            // Validate area score
            if (!is_numeric($area['score']) || $area['score'] < 0 || $area['score'] > $area['max_score']) {
                Log::error('Invalid area score', ['area' => $area['area'], 'score' => $area['score']]);
                return false;
            }
            
            // Validate citations and strengths are arrays
            if (!is_array($area['citations']) || !is_array($area['strengths']) || !is_array($area['areas_for_improvement'])) {
                Log::error('Citations, strengths, and areas_for_improvement must be arrays', ['area' => $area['area']]);
                return false;
            }
        }
        
        return true;
    }

    private function repairSessionJsonResponse(string $text, array $artifact): ?array
    {
        try {
            $sessionId = $artifact['session_id'] ?? null;
            $original = $text;
            
            // Try to repair common JSON issues
            $cleaned = trim($text);
            
            // Remove markdown code blocks
            $patterns = [
                '/^```json\s*/',
                '/^```\s*/',
                '/\s*```$/',
                '/^```json\n/',
                '/\n```$/',
            ];
            
            foreach ($patterns as $pattern) {
                $cleaned = preg_replace($pattern, '', $cleaned);
            }
            
            Log::info('Session JSON repair attempt', [
                'session_id' => $sessionId,
                'original_length' => strlen($original),
                'cleaned_length' => strlen($cleaned),
                'cleaned_preview' => substr($cleaned, 0, 200)
            ]);
            
            $decoded = json_decode($cleaned, true);
            if (json_last_error() === JSON_ERROR_NONE && $this->validateSessionAssessmentSchema($decoded)) {
                Log::info('Session JSON repair successful', ['session_id' => $sessionId]);
                return $decoded;
            }
            
            // Try extracting JSON from within text
            $matches = [];
            if (preg_match('/\{.*\}/s', $cleaned, $matches)) {
                $extracted = $matches[0];
                $decoded = json_decode($extracted, true);
                if (json_last_error() === JSON_ERROR_NONE && $this->validateSessionAssessmentSchema($decoded)) {
                    Log::info('Session JSON extraction successful', ['session_id' => $sessionId]);
                    return $decoded;
                }
            }
            
            Log::warning('Session JSON repair failed', [
                'session_id' => $sessionId,
                'json_error' => json_last_error_msg(),
                'cleaned_text' => substr($cleaned, 0, 500)
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Session JSON repair exception', [
                'session_id' => $artifact['session_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function buildScoringPrompt(array $artifact, array $config): string
    {
        $artifactJson = json_encode($artifact, JSON_PRETTY_PRINT);
        $rubricJson = json_encode($config, JSON_PRETTY_PRINT);
        $rubricVersion = $config['rubric_version'];
        
        return <<<PROMPT
You are an experienced physician examiner conducting an OSCE assessment. You must analyze the session data and assign scores based on the rubric criteria.

CRITICAL: YOU MUST SCORE EACH CRITERION BASED ON THE EVIDENCE PROVIDED IN THE ARTIFACT DATA.

Rules:
- Output MUST be a single JSON object and nothing else.
- Analyze the detailed_analysis data to assign appropriate scores for each criterion.
- Provide comprehensive justifications with specific evidence from the session.
- Quote actual user messages, test orders, and examination findings extensively.
- Be conservative: do not infer beyond the artifact; flag unsafe or missing steps if applicable.
- Use professional language with comprehensive analysis and specific examples.

Artifact with Detailed Analysis:
{$artifactJson}

Rubric Configuration (version {$rubricVersion}):
{$rubricJson}

SCORING INSTRUCTIONS - YOU MUST ASSIGN SCORES:

For each criterion, analyze the evidence and assign a score between 0 and the maximum:

HISTORY-TAKING: Analyze detailed_analysis.communication.conversation_flow and case.key_history_points
- Score based on: coverage of key history points, question types, systematic approach
- Use transcript messages to assess thoroughness
- Example scoring: 0-2 = poor/no history, 3-5 = basic history, 6-8 = good history, 9-10 = excellent comprehensive history

PHYSICAL EXAMINATION: Analyze detailed_analysis.examinations data
- Score based on: critical examinations performed vs missed, systematic approach, findings documentation
- Use examination_timeline and critical_examinations_status
- Example scoring: 0-3 = no/minimal exam, 4-7 = partial exam, 8-12 = good exam, 13-15 = comprehensive exam

INVESTIGATIONS: Analyze detailed_analysis.tests data
- Score based on: appropriate tests ordered, required tests completion, cost management, contraindicated tests avoided
- Use test_ordering_timeline, required_tests_status, appropriate_tests_status
- Example scoring: 0-5 = poor test selection, 6-10 = adequate, 11-15 = good, 16-20 = excellent

DIAGNOSIS & REASONING: Analyze overall clinical reasoning from transcript and actions
- Score based on: logical thinking, differential diagnosis consideration, integration of findings
- Example scoring: 0-2 = no reasoning, 3-5 = basic, 6-8 = good, 9-10 = excellent

MANAGEMENT: Analyze management discussion in transcript
- Score based on: appropriate treatment plans, safety considerations, follow-up
- Example scoring: 0-2 = no management, 3-5 = basic, 6-8 = appropriate, 9-10 = comprehensive

COMMUNICATION: Analyze detailed_analysis.communication data
- Score based on: question types, empathy, professionalism, patient interaction quality
- Use question_patterns and conversation_flow
- Example scoring: 0-2 = poor communication, 3-5 = adequate, 6-8 = good, 9-10 = excellent

SAFETY/TIME MANAGEMENT: Analyze timing and safety considerations
- Score based on: time efficiency, safe practices, completion within timeframe
- Use metrics data for timing analysis
- Example scoring: 0-2 = unsafe/poor time, 3-5 = adequate, 6-8 = good, 9-10 = excellent

Task:
Return strict JSON matching this schema:

type Assessment = {
  rubric_version: string;
  criteria: Array<{
    key: 'history'|'exam'|'investigations'|'diagnosis'|'management'|'communication'|'safety';
    score: number; // YOU MUST ASSIGN BASED ON EVIDENCE ANALYSIS
    max: number; // Use from rubric config
    justification: string; // COMPREHENSIVE detailed assessment with extensive quotes and analysis
    citations: string[]; // Brief refs like ["msg#12","test:CBC"]
  }>;
  overall_comment: string; // ≤ 150 words; comprehensive actionable feedback with specific examples
  red_flags: string[]; // Critical issues with specific evidence and context
  model_info: { name: string; temperature: number; };
  detailed_assessment: string; // FULL comprehensive analysis of the entire session with wrapped text formatting
}

COMPREHENSIVE SCORING REQUIREMENTS:

INVESTIGATIONS Analysis:
- Use detailed_analysis.tests.test_ordering_timeline to describe EXACT tests ordered with timestamps and costs
- Reference detailed_analysis.tests.required_tests_status to identify MISSING required tests by name
- Reference detailed_analysis.tests.appropriate_tests_status to identify which appropriate tests were/weren't ordered
- Use detailed_analysis.tests.contraindicated_tests_ordered to identify inappropriate test orders
- ASSIGN SCORE based on completeness, appropriateness, and cost management

PHYSICAL EXAMINATION Analysis:
- Use detailed_analysis.examinations.examination_timeline to describe EXACT examinations performed with findings
- Reference detailed_analysis.examinations.critical_examinations_status to identify MISSING critical exams
- Use detailed_analysis.examinations.body_systems_examined to show systematic approach
- ASSIGN SCORE based on completeness of critical examinations and systematic approach

HISTORY-TAKING Analysis:
- Use detailed_analysis.communication.conversation_flow to quote SPECIFIC user messages
- Reference detailed_analysis.communication.question_patterns to analyze question types
- Use transcript data to identify covered vs missed history points from case.key_history_points
- ASSIGN SCORE based on thoroughness and systematic history taking

COMMUNICATION Analysis:
- Use detailed_analysis.communication.average_message_length and question patterns
- Quote empathy/professionalism from actual messages
- ASSIGN SCORE based on communication quality and professionalism

Important:
- YOU MUST ASSIGN SCORES - do not use placeholder scores
- Include EXTENSIVE quotes and evidence in justifications
- Reference ALL user actions, messages, test orders, and examination findings
- Provide comprehensive analysis in detailed_assessment field
- BE STRICT in scoring - only give high scores when evidence clearly supports it
- Output ONLY the JSON object
PROMPT;
    }

    private function buildAssessmentPrompt(array $artifact, array $computedScores, array $config): string
    {
        $artifactJson = json_encode($artifact, JSON_PRETTY_PRINT);
        $rubricJson = json_encode($config, JSON_PRETTY_PRINT);
        $rubricVersion = $config['rubric_version'];
        
        return <<<PROMPT
You are an experienced physician examiner conducting an OSCE assessment. Provide comprehensive, evidence-based feedback with full detailed analysis.

Rules:
- Output MUST be a single JSON object and nothing else.
- Provide COMPREHENSIVE justifications with extensive evidence from the session.
- Quote actual user messages, test orders, and examination findings extensively.
- Be conservative: do not infer beyond the artifact; flag unsafe or missing steps if applicable.
- Use professional language with comprehensive analysis and specific examples.

Artifact:
{$artifactJson}

Rubric (version {$rubricVersion}):
{$rubricJson}

Task:
Return strict JSON matching this schema:

type Assessment = {
  rubric_version: string;
  criteria: Array<{
    key: 'history'|'exam'|'investigations'|'diagnosis'|'management'|'communication'|'safety';
    score: number; // Use computed_scores from artifact
    max: number;
    justification: string; // COMPREHENSIVE detailed assessment with extensive quotes and analysis
    citations: string[]; // Brief refs like ["msg#12","test:CBC"]
  }>;
  overall_comment: string; // ≤ 150 words; comprehensive actionable feedback with specific examples
  red_flags: string[]; // Critical issues with specific evidence and context
  model_info: { name: string; temperature: number; };
  detailed_assessment: string; // FULL comprehensive analysis of the entire session with wrapped text formatting
}

Comprehensive Justification Requirements - USE THE DETAILED_ANALYSIS DATA:

INVESTIGATIONS Analysis:
- Use detailed_analysis.tests.test_ordering_timeline to describe EXACT tests ordered with timestamps and costs
- Reference detailed_analysis.tests.required_tests_status to identify MISSING required tests by name
- Reference detailed_analysis.tests.appropriate_tests_status to identify which appropriate tests were/weren't ordered
- Use detailed_analysis.tests.contraindicated_tests_ordered to identify inappropriate test orders
- Example: "Student ordered Troponin ($45) at 08:23 and ECG ($25) at 08:31, appropriately targeting cardiac causes. However, MISSED required CBC and failed to order recommended D-dimer. Total spend $70/$500 budget shows good cost management."

PHYSICAL EXAMINATION Analysis:
- Use detailed_analysis.examinations.examination_timeline to describe EXACT examinations performed with findings
- Reference detailed_analysis.examinations.critical_examinations_status to identify MISSING critical exams
- Use detailed_analysis.examinations.body_systems_examined to show systematic approach
- Example: "Performed cardiac auscultation with finding 'RRR, no murmurs' at 08:45, and respiratory examination 'clear lung sounds bilaterally' at 08:47. However, MISSED critical cardiovascular examination (blood pressure, JVP, peripheral pulses) and abdominal examination entirely."

HISTORY-TAKING Analysis:
- Use detailed_analysis.communication.conversation_flow to quote SPECIFIC user messages
- Reference detailed_analysis.communication.question_patterns to analyze question types
- Use transcript data to identify covered vs missed history points
- Example: "Asked systematic questions: 'Tell me about your chest pain' (msg#1), 'When did this start?' (msg#3), 'Does anything make it worse?' (msg#7). Used 8 open-ended vs 2 closed questions. MISSED family history questions and smoking status entirely."

COMMUNICATION Analysis:
- Use detailed_analysis.communication.average_message_length and question patterns
- Quote empathy/professionalism from actual messages
- Example: "Demonstrated empathy with 'I understand this must be concerning' (msg#15). Average message length 45 characters suggests thoughtful responses. However, could have provided more reassurance during examination phases."

Detailed Assessment Guidelines:
- Provide a comprehensive narrative analysis of the entire OSCE session
- Include chronological flow of the session with timestamps
- Analyze decision-making process and clinical reasoning
- Discuss strengths and areas for improvement with specific examples
- Format with proper paragraph breaks for readability
- Include statistical analysis where relevant (question counts, timing, costs)

Important:
- Use the provided `computed_scores` inside the artifact for `criteria[i].score`. Do NOT invent scores.
- Include EXTENSIVE quotes and evidence in justifications.
- Reference ALL user actions, messages, test orders, and examination findings.
- Provide comprehensive analysis in detailed_assessment field.
- Output ONLY the JSON object.
PROMPT;
    }

    private function getFallbackAssessment(array $computedScores, array $config): array
    {
        $criteria = [];
        foreach ($computedScores as $score) {
            $criteria[] = [
                'key' => $score['key'],
                'score' => $score['score'],
                'max' => $score['max'],
                'justification' => 'Assessment based on rubric scoring. AI analysis unavailable.',
                'citations' => [],
            ];
        }

        return [
            'rubric_version' => $config['rubric_version'],
            'criteria' => $criteria,
            'overall_comment' => 'Assessment completed using deterministic rubric scoring. AI commentary unavailable due to system constraints.',
            'red_flags' => [],
            'model_info' => [
                'name' => 'rubric-only',
                'temperature' => 0,
                'status' => 'ai_unavailable',
            ],
        ];
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    private function categorizeBodySystem(string $examinationType, string $bodyPart): string
    {
        $combined = strtolower($examinationType . ' ' . $bodyPart);
        
        if (stripos($combined, 'cardiac') !== false || stripos($combined, 'heart') !== false || 
            stripos($combined, 'chest') !== false || stripos($combined, 'cardiovascular') !== false) {
            return 'Cardiovascular';
        }
        
        if (stripos($combined, 'lung') !== false || stripos($combined, 'respiratory') !== false || 
            stripos($combined, 'breath') !== false || stripos($combined, 'pulmonary') !== false) {
            return 'Respiratory';
        }
        
        if (stripos($combined, 'abdomen') !== false || stripos($combined, 'abdominal') !== false || 
            stripos($combined, 'gastrointestinal') !== false || stripos($combined, 'gi') !== false) {
            return 'Gastrointestinal';
        }
        
        if (stripos($combined, 'neuro') !== false || stripos($combined, 'neurological') !== false || 
            stripos($combined, 'reflex') !== false || stripos($combined, 'cranial') !== false) {
            return 'Neurological';
        }
        
        if (stripos($combined, 'musculoskeletal') !== false || stripos($combined, 'joint') !== false || 
            stripos($combined, 'muscle') !== false || stripos($combined, 'bone') !== false) {
            return 'Musculoskeletal';
        }
        
        return 'Other';
    }

    private function categorizeQuestionType(string $text): string
    {
        $text = strtolower($text);
        
        if (preg_match('/\b(what|how|when|where|which|why)\b/', $text)) {
            return 'Open-ended';
        }
        
        if (preg_match('/\b(is|are|do|does|did|can|could|would|will|have|has)\b.*\?/', $text)) {
            return 'Closed-ended';
        }
        
        if (preg_match('/\b(tell me|describe|explain|can you)\b/', $text)) {
            return 'Exploratory';
        }
        
        if (preg_match('/\b(pain|hurt|feel|symptom|problem|issue)\b/', $text)) {
            return 'Symptom-focused';
        }
        
        if (preg_match('/\b(history|family|medication|allergy|smoke|drink)\b/', $text)) {
            return 'History-taking';
        }
        
        return 'General';
    }
}