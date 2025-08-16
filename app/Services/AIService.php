<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private string $apiUrl;
    private string $apiKey;
    private string $model;
    private int $maxRetries;
    private int $timeout;

    public function __construct()
    {
        $this->apiUrl = config('medical_training.ai.api_url');
        $this->apiKey = config('medical_training.ai.api_key');
        $this->model = config('medical_training.ai.model');
        $this->maxRetries = config('medical_training.ai.max_retries', 3);
        $this->timeout = config('medical_training.ai.timeout', 30);
    }

    /**
     * Make a chat completion request to the AI API
     */
    public function chatCompletion(array $messages, string $sessionId = null): array
    {
        $startTime = microtime(true);
        
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => "Bearer {$this->apiKey}",
                        'Content-Type' => 'application/json',
                        'HTTP-Referer' => config('app.url'),
                        'X-Title' => config('app.name', 'Medical Training System'),
                    ])
                    ->post($this->apiUrl, [
                        'model' => $this->model,
                        'messages' => $messages
                    ]);

                $responseTime = round((microtime(true) - $startTime) * 1000);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Log successful API call
                    if ($sessionId) {
                        SystemLog::logApiCall(
                            $sessionId,
                            'AI chat completion successful',
                            [
                                'model' => $this->model,
                                'message_count' => count($messages),
                                'attempt' => $attempt,
                                'response_time_ms' => $responseTime
                            ],
                            $responseTime
                        );
                    }

                    return [
                        'success' => true,
                        'content' => $data['choices'][0]['message']['content'] ?? '',
                        'usage' => $data['usage'] ?? null,
                        'response_time_ms' => $responseTime
                    ];
                }

                // Handle HTTP errors
                $this->handleApiError($response, $attempt, $sessionId);

            } catch (\Exception $e) {
                $this->handleException($e, $attempt, $sessionId);
            }

            // Wait before retrying (exponential backoff)
            if ($attempt < $this->maxRetries) {
                $delay = min(1000 * pow(2, $attempt - 1), 10000);
                usleep($delay * 1000); // Convert to microseconds
            }
        }

        // All retries failed
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
        if ($sessionId) {
            SystemLog::logError(
                $sessionId,
                'AI API',
                'All retry attempts failed',
                [
                    'max_retries' => $this->maxRetries,
                    'total_time_ms' => $responseTime
                ]
            );
        }

        return [
            'success' => false,
            'error' => 'Failed to get AI response after ' . $this->maxRetries . ' attempts',
            'response_time_ms' => $responseTime
        ];
    }

    /**
     * Summarize chat messages
     */
    public function summarizeMessages(array $messages, string $sessionId = null): array
    {
        $conversationText = collect($messages)->map(function ($msg) {
            return "{$msg['role']}: {$msg['content']}";
        })->join("\n");

        $summaryPrompt = [
            [
                'role' => 'system',
                'content' => 'Ringkas percakapan berikut dalam 2-3 kalimat, fokus pada poin-poin penting dan konteks yang relevan:'
            ],
            [
                'role' => 'user',
                'content' => $conversationText
            ]
        ];

        $result = $this->chatCompletion($summaryPrompt, $sessionId);
        
        if (!$result['success']) {
            return [
                'success' => false,
                'summary' => 'Percakapan sebelumnya membahas berbagai topik.'
            ];
        }

        return [
            'success' => true,
            'summary' => $result['content']
        ];
    }

    /**
     * Generate OSCE patient response
     */
    public function generatePatientResponse(
        array $caseData,
        array $conversationHistory,
        string $userInput,
        array $checklistProgress,
        string $sessionId = null
    ): array {
        $systemPrompt = $this->buildOSCESystemPrompt($caseData, $checklistProgress);
        
        $messages = [$systemPrompt];
        
        // Add conversation history
        foreach ($conversationHistory as $entry) {
            $messages[] = [
                'role' => $entry['role'],
                'content' => $entry['content']
            ];
        }
        
        // Add current user input
        $messages[] = [
            'role' => 'user',
            'content' => $userInput
        ];

        return $this->chatCompletion($messages, $sessionId);
    }

    /**
     * Generate OSCE feedback and scoring
     */
    public function generateOSCEFeedback(
        array $caseData,
        array $conversationHistory,
        array $checklistProgress,
        string $sessionId = null
    ): array {
        $feedbackPrompt = $this->buildFeedbackPrompt($caseData, $conversationHistory, $checklistProgress);
        
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a medical education expert. Analyze the OSCE session and provide detailed feedback and scoring.'
            ],
            [
                'role' => 'user',
                'content' => $feedbackPrompt
            ]
        ];

        return $this->chatCompletion($messages, $sessionId);
    }

    /**
     * Health check for AI service
     */
    public function healthCheck(): array
    {
        try {
            $testMessages = [
                [
                    'role' => 'user',
                    'content' => 'Hello, this is a health check. Please respond briefly.'
                ]
            ];

            $result = $this->chatCompletion($testMessages);
            
            return [
                'status' => $result['success'] ? 'healthy' : 'unhealthy',
                'response_time_ms' => $result['response_time_ms'] ?? null,
                'error' => $result['error'] ?? null
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    private function handleApiError(Response $response, int $attempt, string $sessionId = null): void
    {
        $status = $response->status();
        $body = $response->body();

        if ($sessionId) {
            SystemLog::logError(
                $sessionId,
                'AI API',
                "HTTP Error {$status} on attempt {$attempt}",
                [
                    'status' => $status,
                    'response_body' => $body,
                    'attempt' => $attempt
                ]
            );
        }

        // Don't retry on client errors (except rate limiting)
        if ($status >= 400 && $status < 500 && $status !== 429) {
            throw new \Exception("Client error {$status}: " . $body);
        }
    }

    private function handleException(\Exception $e, int $attempt, string $sessionId = null): void
    {
        if ($sessionId) {
            SystemLog::logError(
                $sessionId,
                'AI API',
                "Exception on attempt {$attempt}: " . $e->getMessage(),
                [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'attempt' => $attempt
                ]
            );
        }

        Log::error('AI Service Exception', [
            'message' => $e->getMessage(),
            'attempt' => $attempt,
            'session_id' => $sessionId
        ]);

        // Re-throw on last attempt
        if ($attempt >= $this->maxRetries) {
            throw $e;
        }
    }

    private function buildOSCESystemPrompt(array $caseData, array $checklistProgress): array
    {
        $patientInfo = $caseData['patient'] ?? [];
        $completedItems = $this->getCompletedChecklistItems($checklistProgress);
        
        $prompt = "You are simulating a patient for medical training. Here are your details:\n\n";
        $prompt .= "Patient Information:\n";
        $prompt .= "- Name: " . ($patientInfo['name'] ?? 'Patient') . "\n";
        $prompt .= "- Age: " . ($patientInfo['age'] ?? 'Unknown') . "\n";
        $prompt .= "- Chief Complaint: " . ($patientInfo['chief_complaint'] ?? 'Not specified') . "\n";
        $prompt .= "- History: " . ($patientInfo['history'] ?? 'Not specified') . "\n\n";
        
        if (!empty($completedItems)) {
            $prompt .= "Information already revealed:\n";
            foreach ($completedItems as $item) {
                $prompt .= "- $item\n";
            }
            $prompt .= "\n";
        }
        
        $prompt .= "Instructions:\n";
        $prompt .= "- Respond as this patient would in Indonesian\n";
        $prompt .= "- Only reveal information when asked directly\n";
        $prompt .= "- Be realistic and consistent with the case\n";
        $prompt .= "- Show appropriate emotion and concern\n";
        $prompt .= "- Don't provide medical knowledge the patient wouldn't have\n";

        return [
            'role' => 'system',
            'content' => $prompt
        ];
    }

    private function buildFeedbackPrompt(array $caseData, array $conversationHistory, array $checklistProgress): string
    {
        $prompt = "Analyze this OSCE session and provide feedback:\n\n";
        $prompt .= "Case: " . ($caseData['title'] ?? 'Unknown') . "\n";
        $prompt .= "Expected checklist items: " . json_encode($caseData['checklist'] ?? []) . "\n\n";
        $prompt .= "Student performance:\n";
        $prompt .= json_encode($checklistProgress) . "\n\n";
        $prompt .= "Conversation log:\n";
        
        foreach ($conversationHistory as $entry) {
            $prompt .= "{$entry['role']}: {$entry['content']}\n";
        }
        
        $prompt .= "\nProvide:\n";
        $prompt .= "1. Overall score (0-100)\n";
        $prompt .= "2. Strengths\n";
        $prompt .= "3. Areas for improvement\n";
        $prompt .= "4. Specific feedback on clinical approach\n";
        
        return $prompt;
    }

    private function getCompletedChecklistItems(array $checklistProgress): array
    {
        $completed = [];
        
        foreach ($checklistProgress as $category => $items) {
            foreach ($items as $item => $isCompleted) {
                if ($isCompleted) {
                    $completed[] = "$category: $item";
                }
            }
        }
        
        return $completed;
    }
}