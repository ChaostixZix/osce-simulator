<?php

namespace App\Services;

use App\Models\AiAssessmentAreaResult;
use App\Models\OsceSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AreaAssessor
{
    private ?string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    private string $model;
    private AiAssessorService $aiAssessorService;

    public function __construct(AiAssessorService $aiAssessorService)
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
        $this->aiAssessorService = $aiAssessorService;
    }

    /**
     * Assess a specific clinical area for a session
     */
    public function assessArea(OsceSession $session, string $area, AiAssessmentAreaResult $areaResult): array
    {
        Log::info('AreaAssessor starting', [
            'session_id' => $session->id,
            'area' => $area,
            'area_result_id' => $areaResult->id
        ]);

        // Load session with relationships for artifact building
        $session->load([
            'osceCase',
            'chatMessages',
            'orderedTests.medicalTest',
            'examinations',
        ]);

        // Build artifact for this specific area
        $artifact = $this->aiAssessorService->buildArtifact($session);
        
        $maxRetries = 2;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;
            $areaResult->update(['attempts' => $attempt]);

            try {
                Log::info('AreaAssessor attempt', [
                    'session_id' => $session->id,
                    'area' => $area,
                    'attempt' => $attempt
                ]);

                // Make API call for this specific area
                $response = $this->callGeminiForArea($artifact, $area);

                // Apply JSON gate
                $jsonResult = $this->applyJsonGate($response, $area, $areaResult);
                
                if ($jsonResult['success']) {
                    // Successful AI result
                    $areaResult->update([
                        'status' => 'completed',
                        'score' => $jsonResult['data']['score'],
                        'justification' => $jsonResult['data']['justification'],
                        'raw_response' => $jsonResult['raw_response'],
                        'response_length' => $jsonResult['response_length'],
                        'was_repaired' => $jsonResult['was_repaired'],
                        'telemetry' => $this->generateAreaTelemetry($jsonResult, $attempt),
                    ]);

                    Log::info('AreaAssessor successful', [
                        'session_id' => $session->id,
                        'area' => $area,
                        'score' => $jsonResult['data']['score'],
                        'was_repaired' => $jsonResult['was_repaired']
                    ]);

                    return [
                        'status' => 'completed',
                        'score' => $jsonResult['data']['score'],
                        'was_repaired' => $jsonResult['was_repaired']
                    ];
                }

                // If not successful but not fatal, attach last error and try again
                if ($attempt < $maxRetries) {
                    if (!empty($jsonResult['error'] ?? null)) {
                        $areaResult->update([
                            'error_message' => (string) $jsonResult['error'],
                        ]);
                    }
                    Log::warning('AreaAssessor retrying', [
                        'session_id' => $session->id,
                        'area' => $area,
                        'attempt' => $attempt,
                        'error' => $jsonResult['error'] ?? 'Unknown error'
                    ]);
                    continue;
                }

            } catch (Exception $e) {
                Log::warning('AreaAssessor attempt failed', [
                    'session_id' => $session->id,
                    'area' => $area,
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);

                // Persist error to area result for frontend debugging
                $areaResult->update([
                    'error_message' => $e->getMessage(),
                ]);

                if ($attempt >= $maxRetries) {
                    break;
                }
            }
        }

        // All attempts failed - fall back to rubric
        Log::warning('AreaAssessor falling back to rubric', [
            'session_id' => $session->id,
            'area' => $area,
            'attempts' => $attempt
        ]);

        return $this->fallbackToRubric($session, $area, $areaResult);
    }

    /**
     * Call Gemini API for a specific clinical area
     */
    private function callGeminiForArea(array $artifact, string $area): array
    {
        $prompt = $this->buildAreaPrompt($artifact, $area);
        $schema = $this->getAreaSchema();

        $response = Http::timeout(120)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . '/' . $this->model . ':generateContent?key=' . $this->apiKey, [
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
                    'temperature' => 0.0,
                    'topK' => 1,
                    'topP' => 1,
                    'maxOutputTokens' => 1200,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $schema,
                ],
            ]);

        if (!$response->successful()) {
            throw new Exception('Gemini API error: ' . $response->status() . ' - ' . $response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return [
            'text' => $text,
            'response_length' => strlen($text),
            'status_code' => $response->status()
        ];
    }

    /**
     * Apply strict JSON validation with repair capability
     */
    private function applyJsonGate(array $response, string $area, AiAssessmentAreaResult $areaResult): array
    {
        $text = $response['text'];
        $wasRepaired = false;

        // Step 1: Validate syntax with json_validate()
        if (!json_validate($text)) {
            Log::info('JSON validation failed, attempting repair', [
                'area' => $area,
                'area_result_id' => $areaResult->id,
                'text_preview' => substr($text, 0, 200)
            ]);

            // Step 2: Attempt repair
            $repairedText = $this->repairJson($text);
            if ($repairedText && json_validate($repairedText)) {
                $text = $repairedText;
                $wasRepaired = true;
                Log::info('JSON repair successful', [
                    'area' => $area,
                    'area_result_id' => $areaResult->id
                ]);
            } else {
                Log::error('JSON repair failed', [
                    'area' => $area,
                    'area_result_id' => $areaResult->id,
                    'original_text' => substr($text, 0, 500)
                ]);
                return [
                    'success' => false,
                    'error' => 'JSON repair failed',
                    'raw_response' => $response
                ];
            }
        }

        // Step 3: Decode JSON
        $decoded = json_decode($text, true);
        if ($decoded === null) {
            return [
                'success' => false,
                'error' => 'JSON decode failed after repair',
                'raw_response' => $response
            ];
        }

        // Step 4: Validate against schema
        if (!$this->validateAreaSchema($decoded)) {
            Log::error('Schema validation failed', [
                'area' => $area,
                'area_result_id' => $areaResult->id,
                'decoded' => $decoded
            ]);
            return [
                'success' => false,
                'error' => 'Schema validation failed',
                'raw_response' => $response
            ];
        }

        // Step 5: Clamp scores if needed
        $maxScore = AiAssessmentAreaResult::CLINICAL_AREAS[$area]['max_score'];
        if ($decoded['score'] > $maxScore) {
            Log::warning('Score clamping applied', [
                'area' => $area,
                'original_score' => $decoded['score'],
                'max_score' => $maxScore
            ]);
            $decoded['score'] = $maxScore;
        }

        return [
            'success' => true,
            'data' => $decoded,
            'was_repaired' => $wasRepaired,
            'raw_response' => $response,
            'response_length' => $response['response_length']
        ];
    }

    /**
     * Repair malformed JSON
     */
    private function repairJson(string $text): ?string
    {
        try {
            // Clean up common issues
            $cleaned = trim($text ?? '');
            if ($cleaned === '') {
                return null;
            }

            // Remove BOM
            $cleaned = preg_replace('/^\xEF\xBB\xBF/', '', $cleaned);

            // Remove any markdown code fences anywhere (case-insensitive)
            // Example: ```json ... ``` or ``` ... ```
            $cleaned = preg_replace('/```[a-zA-Z]*\s*/i', '', $cleaned);
            $cleaned = str_replace('```', '', $cleaned);

            // If narrative text precedes the JSON, strip everything before first '{'
            $firstBrace = strpos($cleaned, '{');
            if ($firstBrace !== false) {
                $cleaned = substr($cleaned, $firstBrace);
            }

            // Remove trailing commas
            $cleaned = preg_replace('/,\s*}/', '}', $cleaned);
            $cleaned = preg_replace('/,\s*]/', ']', $cleaned);

            // If there is extra trailing text after the last closing brace, trim it
            $lastBrace = strrpos($cleaned, '}');
            if ($lastBrace !== false) {
                $candidate = substr($cleaned, 0, $lastBrace + 1);
                if (json_validate($candidate)) {
                    return $candidate;
                }
                $cleaned = $candidate;
            }

            // Try to fix incomplete JSON by ensuring proper closing
            $open = substr_count($cleaned, '{');
            $close = substr_count($cleaned, '}');
            if ($open > $close) {
                $cleaned .= str_repeat('}', $open - $close);
            }

            // One more pass removing accidental trailing commas
            $cleaned = preg_replace('/,\s*}/', '}', $cleaned);
            $cleaned = preg_replace('/,\s*]/', ']', $cleaned);

            return $cleaned;
        } catch (Exception $e) {
            Log::error('JSON repair exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fall back to rubric scoring for this area
     */
    private function fallbackToRubric(OsceSession $session, string $area, AiAssessmentAreaResult $areaResult): array
    {
        $config = config('osce_scoring');
        
        // Find the matching criterion in the rubric
        $rubricScore = 0;
        $maxScore = AiAssessmentAreaResult::CLINICAL_AREAS[$area]['max_score'];
        $justification = "AI assessment failed. Using rubric-based fallback scoring.";

        // Compute rubric score for this specific area
        if ($config && isset($config['criteria'])) {
            foreach ($config['criteria'] as $criterion) {
                if ($this->mapAreaToCriterion($area) === $criterion['key']) {
                    $rubricScore = $this->aiAssessorService->computeCriterionScore(
                        $criterion, 
                        $session, 
                        $session->osceCase, 
                        $config
                    );
                    break;
                }
            }
        }

        // Scale the score to match our area's max score
        if ($maxScore !== ($criterion['max'] ?? 10)) {
            $rubricScore = (int) round(($rubricScore / ($criterion['max'] ?? 10)) * $maxScore);
        }

        $areaResult->update([
            'status' => 'fallback',
            'score' => $rubricScore,
            'justification' => $justification,
            'telemetry' => [
                'fallback_reason' => 'AI assessment failed after all retries',
                'rubric_method' => 'computed',
                'original_rubric_score' => $rubricScore,
                'scaled_to_max' => $maxScore
            ],
            'error_message' => $areaResult->error_message ?? 'AI request failed; used rubric fallback',
        ]);

        return [
            'status' => 'fallback',
            'score' => $rubricScore
        ];
    }

    /**
     * Build prompt for a specific clinical area
     */
    private function buildAreaPrompt(array $artifact, string $area): string
    {
        $artifactJson = json_encode($artifact, JSON_PRETTY_PRINT);
        $areaConfig = AiAssessmentAreaResult::CLINICAL_AREAS[$area];
        
        $prompts = [
            'history' => $this->buildHistoryPrompt($artifactJson, $areaConfig),
            'exam' => $this->buildExamPrompt($artifactJson, $areaConfig),
            'investigations' => $this->buildInvestigationsPrompt($artifactJson, $areaConfig),
            'differential_diagnosis' => $this->buildDifferentialDiagnosisPrompt($artifactJson, $areaConfig),
            'management' => $this->buildManagementPrompt($artifactJson, $areaConfig),
        ];

        return $prompts[$area] ?? $this->buildGenericPrompt($artifactJson, $area, $areaConfig);
    }

    private function buildHistoryPrompt(string $artifactJson, array $config): string
    {
        return <<<PROMPT
You are an expert medical examiner assessing HISTORY-TAKING performance in an OSCE session.

Analyze the detailed session data and provide a focused assessment of the student's history-taking skills.

Session Data:
{$artifactJson}

Assessment Focus: HISTORY-TAKING ONLY
- Analyze detailed_analysis.communication.conversation_flow for specific user messages
- Evaluate systematic approach, question types, and thoroughness
- Check coverage of case.key_history_points
- Consider quality of questioning technique

Scoring Guidelines (0-{$config['max_score']} points):
- 0-8: Poor/minimal history taking
- 9-12: Basic history taking
- 13-16: Good systematic history
- 17-20: Excellent comprehensive history

You must return ONLY a JSON object with this exact structure:
{
  "score": <integer between 0 and {$config['max_score']}>,
  "max_score": {$config['max_score']},
  "justification": "<detailed analysis with specific quotes from user messages, maximum 1200 characters>",
  "outline": ["<concise bullet point of key analysis>", "<... 4-8 items total ...>"],
  "citations": ["msg#12", "test:ECG", "exam:cardiac auscultation"]
}

Be specific and quote actual user messages when possible. Focus only on history-taking performance.
PROMPT;
    }

    private function buildExamPrompt(string $artifactJson, array $config): string
    {
        return <<<PROMPT
You are an expert medical examiner assessing PHYSICAL EXAMINATION performance in an OSCE session.

Analyze the detailed session data and provide a focused assessment of the student's examination skills.

Session Data:
{$artifactJson}

Assessment Focus: PHYSICAL EXAMINATION ONLY
- Analyze detailed_analysis.examinations.examination_timeline for performed examinations
- Check detailed_analysis.examinations.critical_examinations_status for missing critical exams
- Evaluate systematic approach and thoroughness
- Review findings documentation and body systems examined

Scoring Guidelines (0-{$config['max_score']} points):
- 0-6: No/minimal examination
- 7-9: Partial examination
- 10-12: Good systematic examination
- 13-15: Comprehensive thorough examination

You must return ONLY a JSON object with this exact structure:
{
  "score": <integer between 0 and {$config['max_score']}>,
  "max_score": {$config['max_score']},
  "justification": "<detailed analysis with specific examination findings and missing elements, maximum 1200 characters>",
  "outline": ["<concise bullet point of key analysis>", "<... 4-8 items total ...>"],
  "citations": ["exam:cardiac auscultation", "exam:respiratory exam"]
}

Be specific about which examinations were performed and which critical ones were missed.
PROMPT;
    }

    private function buildInvestigationsPrompt(string $artifactJson, array $config): string
    {
        return <<<PROMPT
You are an expert medical examiner assessing INVESTIGATIONS performance in an OSCE session.

Analyze the detailed session data and provide a focused assessment of the student's investigation ordering.

Session Data:
{$artifactJson}

Assessment Focus: INVESTIGATIONS ONLY
- Analyze detailed_analysis.tests.test_ordering_timeline for ordered tests with costs and timing
- Check detailed_analysis.tests.required_tests_status for missing required tests
- Review detailed_analysis.tests.appropriate_tests_status for test appropriateness
- Evaluate detailed_analysis.tests.contraindicated_tests_ordered for inappropriate orders
- Consider cost management and budget efficiency

Scoring Guidelines (0-{$config['max_score']} points):
- 0-8: Poor test selection/management
- 9-12: Adequate investigation approach
- 13-16: Good appropriate testing
- 17-20: Excellent investigation strategy

You must return ONLY a JSON object with this exact structure:
{
  "score": <integer between 0 and {$config['max_score']}>,
  "max_score": {$config['max_score']},
  "justification": "<detailed analysis with specific tests ordered, costs, appropriateness, and missing elements, maximum 1200 characters>",
  "outline": ["<concise bullet point of key analysis>", "<... 4-8 items total ...>"],
  "citations": ["test:ECG", "test:CBC"]
}

Be specific about test names, costs, timing, and appropriateness.
PROMPT;
    }

    private function buildDifferentialDiagnosisPrompt(string $artifactJson, array $config): string
    {
        return <<<PROMPT
You are an expert medical examiner assessing DIFFERENTIAL DIAGNOSIS reasoning in an OSCE session.

Analyze the detailed session data and provide a focused assessment of the student's diagnostic reasoning.

Session Data:
{$artifactJson}

Assessment Focus: DIFFERENTIAL DIAGNOSIS ONLY
- Analyze conversation flow for diagnostic thinking and hypothesis generation
- Evaluate integration of history, examination, and investigation findings
- Consider systematic approach to differential diagnosis
- Assess clinical reasoning process and diagnostic accuracy

Scoring Guidelines (0-{$config['max_score']} points):
- 0-6: No/poor diagnostic reasoning
- 7-9: Basic diagnostic thinking
- 10-12: Good differential consideration
- 13-15: Excellent diagnostic reasoning

You must return ONLY a JSON object with this exact structure:
{
  "score": <integer between 0 and {$config['max_score']}>,
  "max_score": {$config['max_score']},
  "justification": "<detailed analysis of diagnostic reasoning process with evidence from session, maximum 1200 characters>",
  "outline": ["<concise bullet point of key analysis>", "<... 4-8 items total ...>"],
  "citations": ["msg#15", "test:Troponin"]
}

Focus specifically on diagnostic thinking and differential diagnosis consideration.
PROMPT;
    }

    private function buildManagementPrompt(string $artifactJson, array $config): string
    {
        return <<<PROMPT
You are an expert medical examiner assessing MANAGEMENT planning in an OSCE session.

Analyze the detailed session data and provide a focused assessment of the student's management approach.

Session Data:
{$artifactJson}

Assessment Focus: MANAGEMENT ONLY
- Analyze conversation for management planning and therapeutic discussions
- Evaluate treatment approach, safety considerations, and follow-up planning
- Consider appropriateness of proposed interventions
- Assess understanding of management principles

Scoring Guidelines (0-{$config['max_score']} points):
- 0-6: No/poor management planning
- 7-9: Basic management approach
- 10-12: Good management planning
- 13-15: Excellent comprehensive management

You must return ONLY a JSON object with this exact structure:
{
  "score": <integer between 0 and {$config['max_score']}>,
  "max_score": {$config['max_score']},
  "justification": "<detailed analysis of management approach with specific evidence, maximum 1200 characters>",
  "outline": ["<concise bullet point of key analysis>", "<... 4-8 items total ...>"],
  "citations": ["msg#22", "exam:neuro exam"]
}

Focus specifically on management planning and therapeutic reasoning.
PROMPT;
    }

    private function buildGenericPrompt(string $artifactJson, string $area, array $config): string
    {
        return <<<PROMPT
You are an expert medical examiner assessing {$area} performance in an OSCE session.

Session Data:
{$artifactJson}

You must return ONLY a JSON object with this exact structure:
{
  "score": <integer between 0 and {$config['max_score']}>,
  "max_score": {$config['max_score']},
  "justification": "<detailed analysis maximum 1200 characters>",
  "outline": ["<concise bullet point of key analysis>", "<...>"],
  "citations": ["msg#12", "test:ECG", "exam:cardiac auscultation"]
}

Be specific and evidence-based. Include brief outline and citations to session messages/tests/exams that justify the score.
PROMPT;
    }

    /**
     * Get JSON schema for area assessment
     */
    private function getAreaSchema(): array
    {
        // Allow richer fields to pass through so downstream UI can surface them
        return [
            'type' => 'object',
            'properties' => [
                'score' => ['type' => 'integer'],
                'max_score' => ['type' => 'integer'],
                'justification' => ['type' => 'string', 'maxLength' => 1200],
                // Optional but encouraged for richer UX
                'outline' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'citations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
            ],
            'required' => ['score', 'max_score', 'justification'],
        ];
    }

    /**
     * Validate area assessment schema
     */
    private function validateAreaSchema(array $data): bool
    {
        $required = ['score', 'max_score', 'justification'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        if (!is_int($data['score']) || !is_int($data['max_score'])) {
            return false;
        }

        if (!is_string($data['justification']) || strlen($data['justification']) > 1200) {
            return false;
        }

        return true;
    }

    /**
     * Map clinical area to rubric criterion key
     */
    private function mapAreaToCriterion(string $area): string
    {
        return match ($area) {
            'history' => 'history',
            'exam' => 'exam',
            'investigations' => 'investigations',
            'differential_diagnosis' => 'diagnosis',
            'management' => 'management',
            default => $area
        };
    }

    /**
     * Generate telemetry for area processing
     */
    private function generateAreaTelemetry(array $jsonResult, int $attempts): array
    {
        return [
            'attempts' => $attempts,
            'was_repaired' => $jsonResult['was_repaired'],
            'response_length' => $jsonResult['response_length'],
            'raw_response_preview' => substr($jsonResult['raw_response']['text'] ?? '', 0, 500),
            'processing_method' => 'ai_assessment',
            'json_gate_passed' => true,
        ];
    }
}
