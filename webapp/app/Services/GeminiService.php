<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for interacting with Google Gemini API with web search grounding.
 * Provides evidence-based medical evaluation with citations.
 */
class GeminiService
{
    private string $apiKey;

    private string $model;

    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-2.5-flash');

        if (empty($this->apiKey)) {
            throw new \Exception('Gemini API key not configured. Please set GEMINI_API_KEY in your .env file.');
        }
    }

    /**
     * Evaluate medical rationale with web search grounding
     */
    public function evaluateWithGrounding(string $systemPrompt, string $userRationale, string $context = ''): array
    {
        $requestBody = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $this->buildEvaluationPrompt($systemPrompt, $userRationale, $context),
                        ],
                    ],
                ],
            ],
            'tools' => [
                [
                    'google_search' => (object) [],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.1, // Low temperature for medical accuracy
                'topP' => 0.8,
                'maxOutputTokens' => 2048,
                // Enforce structured JSON to reduce fallback incidents
                'responseMimeType' => 'application/json',
                'responseSchema' => $this->getEvaluationSchema(),
            ],
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}", $requestBody);

            if (! $response->successful()) {
                Log::error('Gemini API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'request' => $requestBody,
                ]);

                return $this->getFallbackResponse();
            }

            return $this->parseGeminiResponse($response);

        } catch (\Exception $e) {
            Log::error('Gemini API exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->getFallbackResponse();
        }
    }

    /**
     * Build the evaluation prompt with structured instructions
     */
    private function buildEvaluationPrompt(string $systemPrompt, string $userRationale, string $context): string
    {
        return 'You are a strict, objective hospital consultant evaluating clinical reasoning. '.
               "For every factual claim, search for authoritative medical sources and provide citations.\n\n".
               "System Context: {$systemPrompt}\n\n".
               "Additional Context: {$context}\n\n".
               "User's Rationale to Evaluate: \"{$userRationale}\"\n\n".
               'Evaluate this rationale using evidence-based medicine. For every correctness judgment, '.
               'search for and cite authoritative sources (guidelines, textbooks, reviews). '.
               'Respond with a structured JSON evaluation including citations.';
    }

    /**
     * Get JSON schema for structured response
     */
    private function getEvaluationSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'user_rationale_summary' => [
                    'type' => 'string',
                    'description' => 'One sentence summary of user rationale',
                ],
                'verdict' => [
                    'type' => 'string',
                    'enum' => ['correct', 'partially_correct', 'incorrect'],
                    'description' => 'Overall verdict of the evaluation'
                ],
                'feedback_why' => [
                    'type' => 'string',
                    'description' => '1-2 sentences explaining the verdict with evidence',
                ],
                'score_breakdown' => [
                    'type' => 'object',
                    'properties' => [
                        'relevance' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'maximum' => 2,
                            'description' => 'Relevance score'
                        ],
                        'evidence_accuracy' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'maximum' => 3,
                            'description' => 'Evidence accuracy score'
                        ],
                        'completeness' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'maximum' => 2,
                            'description' => 'Completeness score'
                        ],
                        'safety' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'maximum' => 2,
                            'description' => 'Safety score'
                        ],
                        'prioritization' => [
                            'type' => 'integer',
                            'minimum' => 0,
                            'maximum' => 1,
                            'description' => 'Prioritization score'
                        ],
                    ],
                    'required' => ['relevance', 'evidence_accuracy', 'completeness', 'safety', 'prioritization'],
                    'description' => 'Detailed score breakdown'
                ],
                'total_score' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 10,
                    'description' => 'Total evaluation score'
                ],
                'citations' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => [
                                'type' => 'string',
                                'description' => 'Title of the cited source'
                            ],
                            'source' => [
                                'type' => 'string',
                                'description' => 'Source name or publication'
                            ],
                            'url' => [
                                'type' => 'string',
                                'description' => 'URL to the source'
                            ],
                            'excerpt' => [
                                'type' => 'string',
                                'description' => 'Relevant excerpt from the source'
                            ],
                        ],
                        'required' => ['title', 'source'],
                        'additionalProperties' => false
                    ],
                    'description' => 'List of citations with sources'
                ],
            ],
            'required' => ['user_rationale_summary', 'verdict', 'feedback_why', 'score_breakdown', 'total_score', 'citations'],
            'additionalProperties' => false
        ];
    }

    /**
     * Parse Gemini response and extract grounding metadata
     */
    private function parseGeminiResponse(Response $response): array
    {
        $data = $response->json();

        if (empty($data['candidates'][0]['content']['parts'][0]['text'])) {
            Log::warning('No content in Gemini response', ['response' => $data]);

            return $this->getFallbackResponse();
        }

        $contentText = $data['candidates'][0]['content']['parts'][0]['text'];

        // Parse JSON response
        $evaluation = json_decode($contentText, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Invalid JSON in Gemini response, using fallback', ['content' => $contentText]);
            $evaluation = $this->getFallbackEvaluation();
        }

        // Extract grounding metadata if available
        $groundingMetadata = $data['candidates'][0]['groundingMetadata'] ?? null;

        // If no citations in structured response, try to extract from grounding metadata
        if (empty($evaluation['citations']) && $groundingMetadata) {
            $evaluation['citations'] = $this->extractCitationsFromGrounding($groundingMetadata);
        }

        return [
            'evaluation' => $evaluation,
            'grounding_metadata' => $groundingMetadata,
            'model_used' => $this->model,
            'has_citations' => ! empty($evaluation['citations']),
            'citation_count' => count($evaluation['citations'] ?? []),
            'raw_response' => $data,
        ];
    }

    /**
     * Extract citations from grounding metadata
     */
    private function extractCitationsFromGrounding(array $groundingMetadata): array
    {
        $citations = [];

        // Extract from search queries and results
        if (isset($groundingMetadata['webSearchQueries'])) {
            foreach ($groundingMetadata['webSearchQueries'] as $index => $query) {
                $citations[] = [
                    'title' => $query,
                    'source' => 'Google Search',
                    'url' => "https://www.google.com/search?q=" . urlencode($query),
                    'excerpt' => "Search result for: {$query}"
                ];
            }
        }

        return $citations;
    }

    /**
     * Get fallback evaluation when JSON parsing fails
     */
    private function getFallbackEvaluation(): array
    {
        return [
            'user_rationale_summary' => 'Unable to parse AI response',
            'verdict' => 'partially_correct',
            'feedback_why' => 'Evaluation completed but response format was unexpected',
            'score_breakdown' => [
                'relevance' => 1,
                'evidence_accuracy' => 1,
                'completeness' => 1,
                'safety' => 1,
                'prioritization' => 0,
            ],
            'total_score' => 4,
            'citations' => [],
        ];
    }

    /**
     * Get fallback response when API fails
     */
    private function getFallbackResponse(): array
    {
        return [
            'evaluation' => [
                'user_rationale_summary' => 'Unable to evaluate due to API error',
                'verdict' => 'partially_correct',
                'feedback_why' => 'Evaluation unavailable - please review manually with clinical guidelines',
                'score_breakdown' => [
                    'relevance' => 1,
                    'evidence_accuracy' => 1,
                    'completeness' => 1,
                    'safety' => 1,
                    'prioritization' => 0,
                ],
                'total_score' => 4,
                'citations' => [],
            ],
            'grounding_metadata' => null,
            'model_used' => $this->model,
            'has_citations' => false,
            'citation_count' => 0,
            'is_fallback' => true,
        ];
    }

    /**
     * Test API connection and configuration
     */
    public function testConnection(): array
    {
        $testPrompt = 'Test connection: What is evidence-based medicine?';

        $requestBody = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $testPrompt],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}", $requestBody);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'model' => $this->model,
                'response_length' => strlen($response->body()),
                'error' => $response->successful() ? null : $response->body(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'model' => $this->model,
            ];
        }
    }
}
