<?php

namespace App\Services;

use App\Models\AiAssessmentAreaResult;
use App\Models\AiAssessmentAspectResult;
use App\Models\OsceSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MultiPromptAreaAssessor
{
    private ?string $apiKey;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    private string $model;
    private AiAssessorService $aiAssessorService;
    private AssessmentPromptManager $promptManager;

    public function __construct(AiAssessorService $aiAssessorService, AssessmentPromptManager $promptManager)
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
        $this->aiAssessorService = $aiAssessorService;
        $this->promptManager = $promptManager;
    }

    /**
     * Assess a clinical area using multi-prompt approach
     */
    public function assessArea(OsceSession $session, string $area, AiAssessmentAreaResult $areaResult): array
    {
        Log::info('MultiPromptAreaAssessor starting', [
            'session_id' => $session->id,
            'area' => $area,
            'area_result_id' => $areaResult->id
        ]);

        // Load session with relationships
        $session->load([
            'osceCase',
            'chatMessages',
            'orderedTests.medicalTest',
            'examinations',
        ]);

        // Build artifact for this specific area
        $artifact = $this->aiAssessorService->buildArtifact($session);
        
        // Get aspects to assess for this clinical area
        $aspects = $this->promptManager->getAspectsForClinicalArea($area);
        
        $aspectResults = [];
        $totalScore = 0;
        $totalMaxScore = 0;
        
        foreach ($aspects as $aspect) {
            Log::info('Assessing aspect', [
                'session_id' => $session->id,
                'area' => $area,
                'aspect' => $aspect
            ]);
            
            $aspectResult = $this->assessAspect($session, $area, $aspect, $artifact);
            
            if ($aspectResult['success']) {
                $aspectResults[$aspect] = $aspectResult['data'];
                $totalScore += $aspectResult['data']['score'];
                $totalMaxScore += $aspectResult['data']['max_score'];
                
                // Save aspect result to database
                $this->saveAspectResult($areaResult, $aspectResult['data']);
            } else {
                Log::warning('Aspect assessment failed', [
                    'session_id' => $session->id,
                    'area' => $area,
                    'aspect' => $aspect,
                    'error' => $aspectResult['error'] ?? 'Unknown error'
                ]);
                
                // Use fallback scoring for this aspect
                $fallbackResult = $this->getFallbackAspectScore($area, $aspect);
                $aspectResults[$aspect] = $fallbackResult;
                $totalScore += $fallbackResult['score'];
                $totalMaxScore += $fallbackResult['max_score'];
                
                $this->saveAspectResult($areaResult, $fallbackResult, true);
            }
        }
        
        // Calculate overall score and performance level
        $overallResult = $this->promptManager->calculateOverallScore($aspectResults);
        
        // Generate detailed feedback
        $detailedFeedback = $this->promptManager->generateDetailedFeedback($aspectResults);
        
        // Update the area result with comprehensive data
        $updateData = [
            'status' => 'completed',
            'score' => $overallResult['total_score'],
            'max_score' => $overallResult['max_score'],
            'justification' => $detailedFeedback,
            'aspect_breakdown' => $aspectResults,
            'overall_performance_level' => $overallResult['performance_level'],
            'detailed_feedback' => $detailedFeedback,
            'acceptable_threshold' => round($overallResult['max_score'] * 0.6),
            'good_threshold' => round($overallResult['max_score'] * 0.8),
            'telemetry' => [
                'assessment_method' => 'multi_prompt',
                'aspects_assessed' => count($aspects),
                'aspects_completed' => count($aspectResults),
                'overall_percentage' => $overallResult['percentage'],
                'aspects_at_good' => $overallResult['aspects_at_good'],
                'aspects_at_acceptable' => $overallResult['aspects_at_acceptable']
            ]
        ];
        
        $areaResult->update($updateData);
        
        Log::info('MultiPromptAreaAssessor completed', [
            'session_id' => $session->id,
            'area' => $area,
            'total_score' => $overallResult['total_score'],
            'max_score' => $overallResult['max_score'],
            'performance_level' => $overallResult['performance_level']
        ]);
        
        return [
            'status' => 'completed',
            'score' => $overallResult['total_score'],
            'performance_level' => $overallResult['performance_level']
        ];
    }
    
    /**
     * Assess a single aspect using AI
     */
    private function assessAspect(OsceSession $session, string $area, string $aspect, array $artifact): array
    {
        $maxRetries = 2;
        $attempt = 0;
        
        // Build the aspect-specific prompt
        $promptData = $this->promptManager->buildAspectPrompt($session, $area, $aspect, $artifact);
        
        while ($attempt < $maxRetries) {
            $attempt++;
            
            try {
                // Make API call for this specific aspect
                $response = $this->callGeminiForAspect($promptData);
                
                // Apply JSON validation
                $jsonResult = $this->validateAspectResponse($response, $area, $aspect);
                
                if ($jsonResult['success']) {
                    return [
                        'success' => true,
                        'data' => $jsonResult['data'],
                        'attempts' => $attempt,
                        'was_repaired' => $jsonResult['was_repaired'] ?? false
                    ];
                }
                
                // If validation failed, retry if attempts remain
                if ($attempt < $maxRetries) {
                    Log::warning('Aspect validation failed, retrying', [
                        'session_id' => $session->id,
                        'area' => $area,
                        'aspect' => $aspect,
                        'attempt' => $attempt,
                        'error' => $jsonResult['error'] ?? 'Unknown error'
                    ]);
                    continue;
                }
                
            } catch (Exception $e) {
                Log::warning('Aspect assessment attempt failed', [
                    'session_id' => $session->id,
                    'area' => $area,
                    'aspect' => $aspect,
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
                
                if ($attempt >= $maxRetries) {
                    break;
                }
            }
        }
        
        return [
            'success' => false,
            'error' => 'All retry attempts failed'
        ];
    }
    
    /**
     * Call Gemini API for a specific aspect
     */
    private function callGeminiForAspect(array $promptData): array
    {
        $response = Http::timeout(60)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . '/' . $this->model . ':generateContent?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $promptData['prompt']
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.0,
                    'topK' => 1,
                    'topP' => 1,
                    'maxOutputTokens' => 800,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $promptData['schema'],
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
     * Validate aspect response JSON
     */
    private function validateAspectResponse(array $response, string $area, string $aspect): array
    {
        $text = $response['text'];
        $wasRepaired = false;
        
        // Step 1: Validate JSON syntax
        if (!json_validate($text)) {
            // Attempt repair
            $repairedText = $this->repairJson($text);
            if ($repairedText && json_validate($repairedText)) {
                $text = $repairedText;
                $wasRepaired = true;
            } else {
                return [
                    'success' => false,
                    'error' => 'JSON validation failed and repair unsuccessful'
                ];
            }
        }
        
        // Step 2: Decode JSON
        $decoded = json_decode($text, true);
        if ($decoded === null) {
            return [
                'success' => false,
                'error' => 'JSON decode failed'
            ];
        }
        
        // Step 3: Validate required fields
        $required = ['aspect', 'clinical_area', 'score', 'max_score', 'performance_level', 'feedback'];
        foreach ($required as $field) {
            if (!isset($decoded[$field])) {
                return [
                    'success' => false,
                    'error' => "Missing required field: {$field}"
                ];
            }
        }
        
        // Step 4: Validate data types and ranges
        if (!is_int($decoded['score']) || $decoded['score'] < 0) {
            return [
                'success' => false,
                'error' => 'Invalid score value'
            ];
        }
        
        if (!in_array($decoded['performance_level'], ['acceptable', 'good', 'needs_improvement'])) {
            return [
                'success' => false,
                'error' => 'Invalid performance level'
            ];
        }
        
        return [
            'success' => true,
            'data' => $decoded,
            'was_repaired' => $wasRepaired
        ];
    }
    
    /**
     * Save aspect result to database
     */
    private function saveAspectResult(AiAssessmentAreaResult $areaResult, array $aspectData, bool $isFallback = false): void
    {
        AiAssessmentAspectResult::create([
            'ai_assessment_area_result_id' => $areaResult->id,
            'aspect' => $aspectData['aspect'],
            'score' => $aspectData['score'],
            'max_score' => $aspectData['max_score'],
            'performance_level' => $aspectData['performance_level'],
            'feedback' => $aspectData['feedback'],
            'citations' => json_encode($aspectData['citations'] ?? []),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    
    /**
     * Get fallback score for an aspect
     */
    private function getFallbackAspectScore(string $area, string $aspect): array
    {
        // Define default fallback scores for each aspect
        $fallbackScores = [
            'history' => [
                'systematic_approach' => ['score' => 4, 'max_score' => 7, 'performance_level' => 'acceptable'],
                'question_quality' => ['score' => 3, 'max_score' => 6, 'performance_level' => 'acceptable'],
                'thoroughness' => ['score' => 4, 'max_score' => 7, 'performance_level' => 'acceptable']
            ],
            'exam' => [
                'technique' => ['score' => 3, 'max_score' => 5, 'performance_level' => 'acceptable'],
                'systematic_approach' => ['score' => 3, 'max_score' => 5, 'performance_level' => 'acceptable'],
                'critical_exams' => ['score' => 3, 'max_score' => 5, 'performance_level' => 'acceptable']
            ],
            // Add fallback scores for other areas...
        ];
        
        return $fallbackScores[$area][$aspect] ?? [
            'score' => round(($aspectData['max_score'] ?? 5) * 0.6),
            'max_score' => $aspectData['max_score'] ?? 5,
            'performance_level' => 'acceptable',
            'feedback' => 'AI assessment unavailable - using default score',
            'citations' => []
        ];
    }
    
    /**
     * Repair malformed JSON (simplified version)
     */
    private function repairJson(string $text): ?string
    {
        try {
            // Remove markdown code fences
            $cleaned = preg_replace('/```[a-zA-Z]*\s*/i', '', $text);
            $cleaned = str_replace('```', '', $cleaned);
            
            // Extract JSON if embedded in text
            $firstBrace = strpos($cleaned, '{');
            if ($firstBrace !== false) {
                $cleaned = substr($cleaned, $firstBrace);
            }
            
            // Remove trailing commas
            $cleaned = preg_replace('/,\s*}/', '}', $cleaned);
            $cleaned = preg_replace('/,\s*]/', ']', $cleaned);
            
            // Ensure proper closing
            $open = substr_count($cleaned, '{');
            $close = substr_count($cleaned, '}');
            if ($open > $close) {
                $cleaned .= str_repeat('}', $open - $close);
            }
            
            return json_validate($cleaned) ? $cleaned : null;
        } catch (Exception $e) {
            Log::error('JSON repair failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}