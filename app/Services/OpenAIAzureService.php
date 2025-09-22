<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Log;

/**
 * Service for interacting with OpenAI Azure API.
 * Provides medical evaluation using GPT models.
 */
class OpenAIAzureService
{
    private string $apiKey;
    private string $endpoint;
    public string $deployment;
    private int $timeout;
    public $client;

    public function __construct()
    {
        $this->apiKey = config('services.openai_azure.api_key');
        $this->endpoint = config('services.openai_azure.endpoint');
        $this->deployment = config('services.openai_azure.deployment', 'gpt-4.1-nano');
        $this->timeout = config('services.openai_azure.timeout', 30);

        if (empty($this->apiKey) || empty($this->endpoint)) {
            throw new \Exception('OpenAI Azure API key and endpoint not configured. Please set OPENAI_AZURE_API_KEY and OPENAI_AZURE_ENDPOINT in your .env file.');
        }

        $this->client = OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withBaseUri($this->endpoint)
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => $this->timeout]))
            ->make();
    }

    /**
     * Generic JSON generation helper using a provided JSON schema.
     * Returns the decoded JSON on success or an empty array on failure.
     */
    public function generateJson(array $schema, string $prompt, array $options = []): array
    {
        try {
            $systemMessage = "You are a medical expert AI. Respond with valid JSON according to the provided schema. " .
                           "Schema: " . json_encode($schema);

            $response = $this->client->chat()->create([
                'model' => $this->deployment,
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $options['temperature'] ?? 0.3,
                'max_tokens' => $options['maxOutputTokens'] ?? 1024,
            ]);

            $content = $response->choices[0]->message->content;
            $json = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('OpenAI Azure generateJson invalid JSON', ['content' => $content]);
                return [];
            }

            return is_array($json) ? $json : [];
        } catch (\Throwable $e) {
            Log::error('OpenAI Azure generateJson exception', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Evaluate medical rationale (no web search grounding available in Azure OpenAI)
     */
    public function evaluateWithGrounding(string $systemPrompt, string $userRationale, string $context = ''): array
    {
        try {
            $systemMessage = 'You are a strict, objective hospital consultant evaluating clinical reasoning. ' .
                           "System Context: {$systemPrompt}\n\n" .
                           "Additional Context: {$context}\n\n" .
                           'Evaluate the user\'s rationale using evidence-based medicine. ' .
                           'Respond with a structured JSON evaluation. ' .
                           'JSON Schema: ' . json_encode($this->getEvaluationSchema());

            $response = $this->client->chat()->create([
                'model' => $this->deployment,
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => "User's Rationale to Evaluate: \"{$userRationale}\""],
                ],
                'temperature' => 0.1, // Low temperature for medical accuracy
                'max_tokens' => 2048,
            ]);

            $content = $response->choices[0]->message->content;
            $evaluation = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('OpenAI Azure evaluation invalid JSON, using fallback', ['content' => $content]);
                $evaluation = $this->getFallbackEvaluation();
            }

            return [
                'evaluation' => $evaluation,
                'grounding_metadata' => null, // Azure OpenAI doesn't support web search
                'model_used' => $this->deployment,
                'has_citations' => !empty($evaluation['citations']),
                'citation_count' => count($evaluation['citations'] ?? []),
                'raw_response' => $response->toArray(),
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI Azure evaluation exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->getFallbackResponse();
        }
    }

    /**
     * Get JSON schema for structured response (same as Gemini for compatibility)
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
            'model_used' => $this->deployment,
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
        try {
            $response = $this->client->chat()->create([
                'model' => $this->deployment,
                'messages' => [
                    ['role' => 'user', 'content' => 'Test connection: What is evidence-based medicine?'],
                ],
                'max_tokens' => 100,
            ]);

            return [
                'success' => true,
                'status_code' => 200,
                'model' => $this->deployment,
                'response_length' => strlen($response->choices[0]->message->content),
                'error' => null,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'model' => $this->deployment,
            ];
        }
    }
}