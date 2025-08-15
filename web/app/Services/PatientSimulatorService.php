<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PatientSimulatorService
{
    protected $apiConfig;
    protected $currentCase;
    protected $conversationHistory = [];
    
    public function __construct($apiConfig = null)
    {
        $this->apiConfig = $apiConfig ?? [
            'url' => config('services.openrouter.url'),
            'key' => config('services.openrouter.key'),
            'model' => config('services.openrouter.model')
        ];
    }

    /**
     * Initialize patient simulation with case data
     */
    public function initializePatient($caseData)
    {
        $this->currentCase = $caseData;
        $this->conversationHistory = [];
        
        // Create initial system prompt based on case data
        $systemPrompt = $this->createSystemPrompt($caseData);
        
        $this->conversationHistory[] = [
            'role' => 'system',
            'content' => $systemPrompt
        ];

        Log::info('Patient simulator initialized', ['caseId' => $caseData['id']]);
    }

    /**
     * Process user input and generate patient response
     */
    public function processUserInput($userInput, $actionType = 'conversation')
    {
        if (!$this->currentCase) {
            throw new \Exception('Patient simulator not initialized with case data');
        }

        $startTime = microtime(true);

        try {
            // Add user message to conversation history
            $this->conversationHistory[] = [
                'role' => 'user',
                'content' => $userInput
            ];

            // Generate response based on action type
            $response = $this->generatePatientResponse($userInput, $actionType);

            // Add assistant response to conversation history
            $this->conversationHistory[] = [
                'role' => 'assistant',
                'content' => $response
            ];

            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::info('Patient response generated', [
                'caseId' => $this->currentCase['id'],
                'actionType' => $actionType,
                'duration' => $duration . 'ms'
            ]);

            return $response;

        } catch (\Exception $error) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::error('Failed to generate patient response', [
                'error' => $error->getMessage(),
                'caseId' => $this->currentCase['id'] ?? 'unknown',
                'duration' => $duration . 'ms'
            ]);

            throw $error;
        }
    }

    /**
     * Create system prompt based on case data
     */
    protected function createSystemPrompt($caseData)
    {
        $patientInfo = $caseData['data']['patientInfo'];
        $symptoms = $caseData['data']['presentingSymptoms'];
        $history = $caseData['data']['medicalHistory'];
        $examination = $caseData['data']['physicalExamination'];

        $prompt = "You are simulating a patient in a medical training scenario. Your role is to respond as the patient would, based on the following case information:\n\n";
        
        $prompt .= "PATIENT INFORMATION:\n";
        $prompt .= "- Name: {$patientInfo['name']}\n";
        $prompt .= "- Age: {$patientInfo['age']}\n";
        $prompt .= "- Gender: {$patientInfo['gender']}\n";
        $prompt .= "- Occupation: {$patientInfo['occupation']}\n\n";

        $prompt .= "PRESENTING COMPLAINT:\n";
        $prompt .= "- Chief Complaint: {$caseData['data']['chiefComplaint']}\n";
        $prompt .= "- Primary Symptom: {$symptoms['primary']}\n";
        $prompt .= "- Onset: {$symptoms['onset']}\n";
        $prompt .= "- Character: {$symptoms['character']}\n";
        $prompt .= "- Severity: {$symptoms['severity']}\n";
        if (isset($symptoms['radiation'])) {
            $prompt .= "- Radiation: {$symptoms['radiation']}\n";
        }
        if (isset($symptoms['associated'])) {
            $prompt .= "- Associated symptoms: " . implode(', ', $symptoms['associated']) . "\n";
        }
        $prompt .= "\n";

        $prompt .= "MEDICAL HISTORY:\n";
        if (isset($history['pastMedical'])) {
            $prompt .= "- Past Medical History: " . implode(', ', $history['pastMedical']) . "\n";
        }
        if (isset($history['medications'])) {
            $prompt .= "- Current Medications: " . implode(', ', $history['medications']) . "\n";
        }
        if (isset($history['allergies'])) {
            $prompt .= "- Allergies: " . implode(', ', $history['allergies']) . "\n";
        }
        if (isset($history['socialHistory'])) {
            $social = $history['socialHistory'];
            if (isset($social['smoking'])) {
                $prompt .= "- Smoking History: {$social['smoking']}\n";
            }
            if (isset($social['alcohol'])) {
                $prompt .= "- Alcohol Use: {$social['alcohol']}\n";
            }
            if (isset($social['familyHistory'])) {
                $prompt .= "- Family History: {$social['familyHistory']}\n";
            }
        }
        $prompt .= "\n";

        $prompt .= "INSTRUCTIONS:\n";
        $prompt .= "1. Respond as this patient would - be realistic and consistent\n";
        $prompt .= "2. Only provide information that the patient would naturally know\n";
        $prompt .= "3. If asked about examination findings, respond appropriately (e.g., 'I'm not sure, you're the doctor')\n";
        $prompt .= "4. When the doctor performs examinations or orders tests, provide the relevant findings from the case data\n";
        $prompt .= "5. Show appropriate concern and emotion for the symptoms\n";
        $prompt .= "6. Ask questions a real patient might ask\n";
        $prompt .= "7. Be concise but thorough in your responses\n\n";

        $prompt .= "VITAL SIGNS (for when examined):\n";
        if (isset($examination['vitalSigns'])) {
            $vitals = $examination['vitalSigns'];
            $prompt .= "- Blood Pressure: {$vitals['bp']}\n";
            $prompt .= "- Heart Rate: {$vitals['hr']}\n";
            $prompt .= "- Respiratory Rate: {$vitals['rr']}\n";
            $prompt .= "- Temperature: {$vitals['temp']}\n";
            $prompt .= "- Oxygen Saturation: {$vitals['o2sat']}\n";
        }
        $prompt .= "\n";

        $prompt .= "PHYSICAL EXAMINATION FINDINGS (for when examined):\n";
        if (isset($examination['general'])) {
            $prompt .= "- General: {$examination['general']}\n";
        }
        if (isset($examination['cardiovascular'])) {
            $prompt .= "- Cardiovascular: {$examination['cardiovascular']}\n";
        }
        if (isset($examination['respiratory'])) {
            $prompt .= "- Respiratory: {$examination['respiratory']}\n";
        }
        $prompt .= "\n";

        if (isset($caseData['data']['investigations'])) {
            $investigations = $caseData['data']['investigations'];
            $prompt .= "INVESTIGATION RESULTS (provide when tests are ordered):\n";
            
            if (isset($investigations['ecg'])) {
                $prompt .= "- ECG: {$investigations['ecg']['findings']}\n";
            }
            if (isset($investigations['labs'])) {
                $labs = $investigations['labs'];
                foreach ($labs as $test => $result) {
                    $prompt .= "- {$test}: {$result}\n";
                }
            }
            if (isset($investigations['imaging'])) {
                $imaging = $investigations['imaging'];
                foreach ($imaging as $test => $result) {
                    $prompt .= "- {$test}: {$result}\n";
                }
            }
        }

        return $prompt;
    }

    /**
     * Generate patient response using AI
     */
    protected function generatePatientResponse($userInput, $actionType)
    {
        $maxRetries = 3;
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiConfig['key'],
                        'Content-Type' => 'application/json',
                        'HTTP-Referer' => config('app.url'),
                        'X-Title' => 'Medical Training System'
                    ])
                    ->post($this->apiConfig['url'], [
                        'model' => $this->apiConfig['model'],
                        'messages' => $this->conversationHistory
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['choices'][0]['message']['content'];
                }

                throw new \Exception('API request failed: ' . $response->status() . ' - ' . $response->body());

            } catch (\Exception $error) {
                $retryCount++;

                if ($retryCount >= $maxRetries) {
                    throw new \Exception('Failed to generate patient response after ' . $maxRetries . ' attempts: ' . $error->getMessage());
                }

                // Wait before retrying (exponential backoff)
                $delay = min(1000 * pow(2, $retryCount - 1), 10000);
                usleep($delay * 1000); // Convert to microseconds
            }
        }
    }

    /**
     * Get conversation history
     */
    public function getConversationHistory()
    {
        return $this->conversationHistory;
    }

    /**
     * Reset the simulator
     */
    public function reset()
    {
        $this->currentCase = null;
        $this->conversationHistory = [];
    }
}