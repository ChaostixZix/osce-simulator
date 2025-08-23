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
        
        // Compute deterministic rubric scores
        $computedScores = $this->computeScores($session, $config);
        $artifact['computed_scores'] = $computedScores;

        // Get AI assessment if available
        $assessorOutput = $this->getAssessment($artifact, $computedScores, $config);

        // Calculate totals
        $totalScore = array_sum(array_column($computedScores, 'score'));
        $maxScore = array_sum(array_column($config['criteria'], 'max'));

        // Persist results
        $session->update([
            'score' => $totalScore,
            'max_score' => $maxScore,
            'assessor_payload' => $artifact,
            'assessor_output' => $assessorOutput,
            'assessed_at' => now(),
            'assessor_model' => $this->model,
            'rubric_version' => $config['rubric_version'],
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

        return [
            'session_id' => $session->id,
            'rubric_version' => config('osce_scoring.rubric_version'),
            'case' => $caseContext,
            'transcript' => $recentMessages,
            'actions' => [
                'tests' => $tests,
                'examinations' => $examinations,
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
        $missedRequired = count($requiredTests) - array_intersect($requiredTests, $testNames);
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
                'maxOutputTokens' => 700,
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Gemini API error: ' . $response->status() . ' - ' . $response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        // Try to parse JSON
        $decoded = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Attempt repair
            $repaired = $this->repairJsonResponse($text, $artifact, $computedScores, $config);
            if ($repaired) {
                return $repaired;
            }
            throw new \Exception('Invalid JSON response from AI');
        }
        
        // Validate schema
        if (!$this->validateAssessmentSchema($decoded)) {
            throw new \Exception('Invalid assessment schema from AI');
        }
        
        return $decoded;
    }

    private function repairJsonResponse(string $text, array $artifact, array $computedScores, array $config): ?array
    {
        try {
            // Try to repair common JSON issues
            $cleaned = trim($text);
            $cleaned = preg_replace('/^```json\s*/', '', $cleaned);
            $cleaned = preg_replace('/\s*```$/', '', $cleaned);
            
            $decoded = json_decode($cleaned, true);
            if (json_last_error() === JSON_ERROR_NONE && $this->validateAssessmentSchema($decoded)) {
                return $decoded;
            }
            
            // If repair fails, return fallback
            return null;
        } catch (\Exception $e) {
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

    private function buildAssessmentPrompt(array $artifact, array $computedScores, array $config): string
    {
        $artifactJson = json_encode($artifact, JSON_PRETTY_PRINT);
        $rubricJson = json_encode($config, JSON_PRETTY_PRINT);
        $rubricVersion = $config['rubric_version'];
        
        return <<<PROMPT
You are an experienced physician examiner conducting an OSCE assessment. Produce concise, structured feedback.
Rules:
- Output MUST be a single JSON object and nothing else.
- Do NOT include chain-of-thought. Provide brief justifications with direct citations to the provided artifact only.
- Be conservative: do not infer beyond the artifact; flag unsafe or missing steps if applicable.

Artifact:
{$artifactJson}

Rubric (version {$rubricVersion}):
{$rubricJson}

Task:
Return strict JSON matching this TypeScript schema:

type Assessment = {
  rubric_version: string;
  criteria: Array<{
    key: 'history'|'exam'|'investigations'|'diagnosis'|'management'|'communication'|'safety';
    score: number; // 0..max from local rubric computation (provided in artifact under computed_scores)
    max: number;
    justification: string; // 1–3 sentences, cite artifact ids
    citations: string[]; // e.g., ["msg#12","lab:Troponin","exam:respiratory.auscultation"]
  }>;
  overall_comment: string; // ≤ 120 words; actionable; professional tone
  red_flags: string[]; // unsafe actions or critical misses
  model_info: { name: string; temperature: number; };
}

Important:
- Use the provided `computed_scores` inside the artifact for `criteria[i].score`. Do NOT invent scores.
- Justifications must reference `citations` from the artifact (message ids, test names, exam keys).
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
}