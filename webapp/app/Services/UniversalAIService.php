<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Universal AI service that routes between different AI providers
 * Currently supports Gemini and OpenAI Azure
 */
class UniversalAIService
{
    private string $provider;
    private $aiService;

    public function __construct(string $provider = null)
    {
        // Allow explicit provider override for testing
        $this->provider = $provider ?? config('services.ai.provider', 'gemini');

        switch ($this->provider) {
            case 'openai':
                $this->aiService = new OpenAIAzureService();
                break;
            case 'gemini':
            default:
                $this->aiService = new GeminiService();
                break;
        }

        Log::info("UniversalAIService initialized with provider: {$this->provider}" . ($provider ? " (explicit)" : " (config)"));
    }

    /**
     * Generic JSON generation helper using a provided JSON schema.
     * Routes to the appropriate AI service.
     */
    public function generateJson(array $schema, string $prompt, array $options = []): array
    {
        return $this->aiService->generateJson($schema, $prompt, $options);
    }

    /**
     * Evaluate medical rationale with or without web search grounding
     * depending on the provider capabilities
     */
    public function evaluateWithGrounding(string $systemPrompt, string $userRationale, string $context = ''): array
    {
        return $this->aiService->evaluateWithGrounding($systemPrompt, $userRationale, $context);
    }

    /**
     * Test API connection and configuration
     */
    public function testConnection(): array
    {
        $result = $this->aiService->testConnection();
        $result['provider'] = $this->provider;
        return $result;
    }

    /**
     * Get current provider name
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Get current AI service instance
     */
    public function getAIService()
    {
        return $this->aiService;
    }

    /**
     * Check if current provider supports web search grounding
     */
    public function supportsGrounding(): bool
    {
        return $this->provider === 'gemini';
    }

    /**
     * Generate a chat response for patient interaction
     * This method adapts the interface for patient chat
     */
    public function generateChatResponse(string $systemPrompt, array $messages, array $options = []): array
    {
        $requestId = uniqid('req_');
        $startTime = microtime(true);

        Log::info("UniversalAIService chat request started", [
            'request_id' => $requestId,
            'provider' => $this->provider,
            'messages_count' => count($messages)
        ]);

        try {
            if ($this->provider === 'openai') {
                // For OpenAI Azure, use the client directly for chat completions
                $formattedMessages = [];
                $formattedMessages[] = ['role' => 'system', 'content' => $systemPrompt];

                foreach ($messages as $message) {
                    $role = $message['sender'] === 'ai_patient' ? 'assistant' : 'user';
                    $formattedMessages[] = ['role' => $role, 'content' => $message['message']];
                }

                try {
                    $response = $this->aiService->client->chat()->create([
                        'model' => $this->aiService->deployment,
                        'messages' => $formattedMessages,
                        'temperature' => $options['temperature'] ?? 0.7,
                        'max_tokens' => $options['max_tokens'] ?? 1024,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('OpenAI Azure chat request failed', [
                        'request_id' => $requestId,
                        'deployment' => $this->aiService->deployment ?? null,
                        'error' => $e->getMessage(),
                        'exception_class' => get_class($e),
                        'code' => $e->getCode(),
                        'provider_error' => $this->extractProviderError($e),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    throw $e;
                }

                $responseTime = microtime(true) - $startTime;
                $content = $response->choices[0]->message->content;

                Log::info("OpenAI Azure response successful", [
                    'request_id' => $requestId,
                    'model' => $this->aiService->deployment,
                    'response_time' => round($responseTime, 3),
                    'content_length' => strlen($content)
                ]);

                return [
                    'content' => $content,
                    'metadata' => [
                        'provider' => 'openai',
                        'model' => $this->aiService->deployment,
                        'request_id' => $requestId,
                        'response_time' => $responseTime,
                        'is_fallback' => false,
                        'usage' => [
                            'prompt_tokens' => $response->usage->promptTokens ?? 0,
                            'completion_tokens' => $response->usage->completionTokens ?? 0,
                            'total_tokens' => $response->usage->totalTokens ?? 0,
                        ]
                    ]
                ];

            } else {
                // For Gemini, use a direct text generation approach instead of JSON schema
                $conversation = $systemPrompt . "\n\n";
                foreach ($messages as $message) {
                    $role = $message['sender'] === 'ai_patient' ? 'Pasien' : 'Dokter';
                    $conversation .= "{$role}: {$message['message']}\n";
                }
                $conversation .= "Pasien: ";

                // Use Gemini's direct API instead of JSON schema approach
                $response = $this->callGeminiDirectly($conversation, $options);
                $responseTime = microtime(true) - $startTime;
                $content = $response['content'] ?? '';

                if (empty($content) || ! ($response['success'] ?? false)) {
                    Log::error('Gemini chat response fallback engaged', [
                        'request_id' => $requestId,
                        'provider' => $this->provider,
                        'error' => $response['error'] ?? null,
                        'status' => $response['status'] ?? null,
                        'raw_response' => $response['raw_response'] ?? null,
                    ]);
                    $content = 'I apologize, but I\'m having trouble responding right now.';
                }

                Log::info("Gemini response successful", [
                    'request_id' => $requestId,
                    'model' => config('services.gemini.model', 'gemini-1.5-flash'),
                    'response_time' => round($responseTime, 3),
                    'content_length' => strlen($content),
                    'api_success' => $response['success'] ?? false
                ]);

                return [
                    'content' => $content,
                    'metadata' => [
                        'provider' => 'gemini',
                        'model' => config('services.gemini.model', 'gemini-1.5-flash'),
                        'request_id' => $requestId,
                        'response_time' => $responseTime,
                        'is_fallback' => empty($content) || !($response['success'] ?? false),
                        'error' => $response['error'] ?? null,
                        'status' => $response['status'] ?? null,
                        'raw_response' => $response['raw_response'] ?? null,
                        'api_response' => $response
                    ]
                ];
            }
        } catch (\Throwable $e) {
            $responseTime = microtime(true) - $startTime;

            Log::error('UniversalAIService chat response error', [
                'request_id' => $requestId,
                'provider' => $this->provider,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'code' => $e->getCode(),
                'provider_error' => $this->extractProviderError($e),
                'trace' => $e->getTraceAsString(),
                'response_time' => round($responseTime, 3)
            ]);

            return [
                'content' => 'I apologize, but I\'m having trouble responding right now. Please try again.',
                'metadata' => [
                    'provider' => $this->provider,
                    'request_id' => $requestId,
                    'response_time' => $responseTime,
                    'is_fallback' => true,
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Call Gemini API directly for chat completions (bypassing JSON schema issues)
     */
    private function callGeminiDirectly(string $prompt, array $options = []): array
    {
        try {
            $apiKey = config('services.gemini.api_key');
            $model = config('services.gemini.model', 'gemini-1.5-flash');
            $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

            $requestBody = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => $options['temperature'] ?? 0.7,
                    'maxOutputTokens' => 1024, // Allow longer patient replies before truncation
                    'topP' => 0.95,
                    'topK' => 40,
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_ONLY_HIGH',
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_ONLY_HIGH',
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_ONLY_HIGH',
                    ],
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_ONLY_HIGH',
                    ],
                ],
            ];

            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$baseUrl}/{$model}:generateContent?key={$apiKey}", $requestBody);

            if (! $response->successful()) {
            Log::error('Gemini chat HTTP request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['success' => false, 'content' => '', 'error' => $response->body(), 'status' => $response->status()];
            }

            $data = $response->json();

            // Check for safety issues or blocked content
            if (isset($data['candidates'][0]['finishReason']) &&
                in_array($data['candidates'][0]['finishReason'], ['SAFETY', 'RECITATION'])) {
                Log::warning('Gemini chat blocked by safety filters', [
                    'finish_reason' => $data['candidates'][0]['finishReason'],
                ]);

                return ['success' => false, 'content' => '', 'error' => 'Content blocked by safety filters', 'status' => $response->status()];
            }

            // Extract content properly
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // If content is empty, check finish reason
            if (empty($content)) {
                $finishReason = $data['candidates'][0]['finishReason'] ?? 'UNKNOWN';
                Log::warning('Gemini chat returned empty content', [
                    'finish_reason' => $finishReason,
                ]);

                return ['success' => false, 'content' => '', 'error' => "No content generated. Finish reason: {$finishReason}", 'status' => $response->status()];
            }

            return ['success' => true, 'content' => trim($content), 'raw_response' => $data];

        } catch (\Throwable $e) {
            Log::error('Gemini chat call exception', [
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'content' => '', 'error' => $e->getMessage()];
        }
    }

    /**
     * Attempt to extract structured error details from provider SDK exceptions.
     */
    private function extractProviderError(\Throwable $exception): ?array
    {
        $response = null;

        if (method_exists($exception, 'getResponse')) {
            try {
                $response = $exception->getResponse();
            } catch (\Throwable $inner) {
                $response = null;
            }
        } elseif (property_exists($exception, 'response')) {
            $response = $exception->response;
        }

        if (! $response) {
            return null;
        }

        $status = null;
        if (is_object($response) && method_exists($response, 'getStatusCode')) {
            $status = $response->getStatusCode();
        } elseif (is_array($response) && isset($response['status'])) {
            $status = $response['status'];
        } elseif (is_object($response) && property_exists($response, 'status')) {
            $status = $response->status;
        }

        $bodyContent = null;
        if (is_object($response) && method_exists($response, 'getBody')) {
            try {
                $bodyContent = (string) $response->getBody();
            } catch (\Throwable $inner) {
                $bodyContent = null;
            }
        } elseif (is_array($response) && isset($response['body'])) {
            $body = $response['body'];
            $bodyContent = is_string($body) ? $body : json_encode($body);
        } elseif (is_object($response) && property_exists($response, 'body')) {
            $body = $response->body;
            $bodyContent = is_string($body) ? $body : json_encode($body);
        }

        if ($bodyContent === null) {
            return ['status' => $status];
        }

        $decoded = json_decode($bodyContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $decoded = null;
        }

        return [
            'status' => $status,
            'body' => $bodyContent,
            'decoded' => $decoded,
        ];
    }
}
