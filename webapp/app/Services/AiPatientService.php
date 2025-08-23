<?php

namespace App\Services;

use App\Models\OsceCase;
use App\Models\OsceSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiPatientService
{
    private string $apiKey;

    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    public function generatePatientResponse(OsceSession $session, string $userMessage, array $chatHistory = []): string
    {
        try {
            $osceCase = $session->osceCase;
            $patientContext = $this->buildPatientContext($osceCase, $session, $chatHistory);

            $prompt = $this->buildPrompt($userMessage, $patientContext, $chatHistory);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'?key='.$this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 500,
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
                    ],
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'I am not feeling well today.';
            }

            Log::error('Gemini API error', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return $this->getFallbackResponse($userMessage, $patientContext);

        } catch (\Exception $e) {
            Log::error('AI Patient Service error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->getFallbackResponse($userMessage, $patientContext ?? []);
        }
    }

    private function buildPatientContext(OsceCase $osceCase, OsceSession $session, array $chatHistory): array
    {
        $context = $osceCase->getAiPatientContext();

        // Add session-specific context
        $context['session_duration'] = $session->started_at ? now()->diffInMinutes($session->started_at) : 0;
        $context['chat_history_length'] = count($chatHistory);

        return $context;
    }

    private function buildPrompt(string $userMessage, array $patientContext, array $chatHistory): string
    {
        $prompt = 'You are an AI patient in a medical OSCE (Objective Structured Clinical Examination) training session. ';
        $prompt .= "Respond as the patient would in a realistic medical consultation.\n\n";

        // Add patient profile and context
        if (! empty($patientContext['profile'])) {
            $prompt .= "Patient Profile: {$patientContext['profile']}\n\n";
        }

        if (! empty($patientContext['symptoms'])) {
            $prompt .= 'Current Symptoms: '.implode(', ', $patientContext['symptoms'])."\n\n";
        }

        if (! empty($patientContext['vitals'])) {
            $prompt .= 'Vital Signs: '.json_encode($patientContext['vitals'])."\n\n";
        }

        if (! empty($patientContext['instructions'])) {
            $prompt .= "Behavioral Instructions: {$patientContext['instructions']}\n\n";
        }

        // Add recent chat context (last 5 messages)
        if (! empty($chatHistory)) {
            $prompt .= "Recent Conversation:\n";
            $recentMessages = array_slice($chatHistory, -5);
            foreach ($recentMessages as $message) {
                $role = $message['sender_type'] === 'user' ? 'Doctor' : 'Patient';
                $prompt .= "{$role}: {$message['message']}\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Doctor's Question: {$userMessage}\n\n";
        $prompt .= 'Patient Response (be realistic, medical, and in character):';

        return $prompt;
    }

    private function getFallbackResponse(string $userMessage, array $patientContext): string
    {
        // Simple fallback responses based on common medical questions
        $fallbackResponses = [
            'pain' => 'I\'m experiencing some discomfort, yes.',
            'symptoms' => 'I\'ve been feeling unwell for a few days now.',
            'medication' => 'I\'m not currently taking any medications.',
            'history' => 'I don\'t have any significant medical history.',
            'allergies' => 'I don\'t have any known allergies.',
            'family' => 'I\'m not aware of any family medical conditions.',
        ];

        foreach ($fallbackResponses as $keyword => $response) {
            if (stripos($userMessage, $keyword) !== false) {
                return $response;
            }
        }

        return 'I\'m not sure how to answer that. Could you rephrase your question?';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }
}
