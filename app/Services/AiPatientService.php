<?php

namespace App\Services;

use App\Models\OsceCase;
use App\Models\OsceSession;
use Illuminate\Support\Facades\Log;

class AiPatientService
{
    private UniversalAIService $aiService;

    public function __construct()
    {
        $this->aiService = new UniversalAIService();
    }

    public function generatePatientResponse(OsceSession $session, string $userMessage, array $chatHistory = []): string
    {
        try {
            $osceCase = $session->osceCase;
            $patientContext = $this->buildPatientContext($osceCase, $session, $chatHistory);

            $systemPrompt = $this->buildSystemPrompt($patientContext);

            // Format chat history for AI service
            $formattedMessages = [];
            foreach ($chatHistory as $message) {
                $formattedMessages[] = [
                    'sender' => $message['sender_type'] === 'user' ? 'user' : 'ai_patient',
                    'message' => $message['message']
                ];
            }

            // Add the current user message
            $formattedMessages[] = [
                'sender' => 'user',
                'message' => $userMessage
            ];

            $options = [
                'temperature' => 0.7,
                'max_tokens' => 120,
            ];

            $response = $this->aiService->generateChatResponse($systemPrompt, $formattedMessages, $options);

            // Log the AI response metadata for debugging
            Log::info('AI Patient Service response', [
                'provider' => $response['metadata']['provider'],
                'model' => $response['metadata']['model'],
                'is_fallback' => $response['metadata']['is_fallback'],
                'response_time' => $response['metadata']['response_time'],
                'request_id' => $response['metadata']['request_id'],
            ]);

            return $response['content'] ?: $this->getFallbackResponse($userMessage, $patientContext);

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

    private function buildSystemPrompt(array $patientContext): string
    {
        $prompt = '';
        $prompt .= "You are acting as a simulated patient for clinical OSCE training.\n";
        $prompt .= "Rules (follow exactly):\n";
        $prompt .= "- Respond briefly: 1–2 sentences only.\n";
        $prompt .= "- Language: default Bahasa Indonesia; if the doctor's message is in English, answer in English. If the doctor mixes languages or it's unclear, you may reply bilingually (Indonesian first, then English in parentheses).\n";
        $prompt .= "- Stay strictly in the patient role (not a doctor or explainer).\n";
        $prompt .= "- Share only what a real patient would naturally know based on symptoms/history.\n";
        $prompt .= "- If a question is not relevant or you genuinely wouldn't know, politely say you don't know.\n";
        $prompt .= "- Optional: include one short behavioral cue in parentheses when relevant, e.g., (batuk), (memegang perut), (coughs), (holds abdomen).\n";
        $prompt .= "- Do NOT give medical explanations or diagnoses. No bullet lists, no headings—just a natural patient reply.\n\n";

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

        $prompt .= 'Respond as this patient would, following all the rules above.';

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
        try {
            $result = $this->aiService->testConnection();
            return $result['success'];
        } catch (\Exception $e) {
            return false;
        }
    }
}
