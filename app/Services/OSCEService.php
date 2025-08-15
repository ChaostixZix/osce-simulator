<?php

namespace App\Services;

use App\Models\OSCECase;
use App\Models\OSCESession;
use App\Models\Session;
use App\Models\SystemLog;

class OSCEService
{
    private AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Get all available OSCE cases
     */
    public function getAvailableCases(): array
    {
        $cases = OSCECase::active()->get();

        return $cases->map(function ($case) {
            $stats = $case->getCompletionStats();
            
            return [
                'id' => $case->id,
                'case_id' => $case->case_id,
                'title' => $case->title,
                'description' => $case->description,
                'category' => $case->category,
                'difficulty' => $case->difficulty,
                'expected_duration_minutes' => $case->getExpectedDurationInMinutes(),
                'total_checklist_items' => $case->getTotalChecklistItems(),
                'stats' => $stats
            ];
        })->toArray();
    }

    /**
     * Start a new OSCE session
     */
    public function startOSCESession(string $sessionId, string $caseId): array
    {
        $session = Session::bySessionId($sessionId)->first();
        if (!$session) {
            throw new \Exception('Session not found');
        }

        $case = OSCECase::active()->byCaseId($caseId)->first();
        if (!$case) {
            throw new \Exception('Case not found or inactive');
        }

        // Check if there's already an active OSCE session
        $existingSession = OSCESession::bySession($sessionId)->active()->first();
        if ($existingSession) {
            throw new \Exception('An OSCE session is already active. Please complete or abandon it first.');
        }

        try {
            // Create new OSCE session
            $osceSession = OSCESession::create([
                'session_id' => $sessionId,
                'case_id' => $case->id,
                'status' => 'active',
                'started_at' => now(),
                'checklist_progress' => $this->initializeChecklistProgress($case->checklist),
                'conversation_log' => []
            ]);

            // Log session start
            SystemLog::logPerformance(
                $sessionId,
                'OSCE Service',
                "Started OSCE session for case: {$case->case_id}",
                [
                    'case_id' => $case->case_id,
                    'case_title' => $case->title,
                    'osce_session_id' => $osceSession->id
                ]
            );

            return [
                'success' => true,
                'osce_session_id' => $osceSession->id,
                'case' => [
                    'id' => $case->case_id,
                    'title' => $case->title,
                    'description' => $case->description,
                    'category' => $case->category,
                    'difficulty' => $case->difficulty,
                    'expected_duration_minutes' => $case->getExpectedDurationInMinutes()
                ],
                'message' => 'OSCE session started successfully. You can now begin interacting with the patient.'
            ];

        } catch (\Exception $e) {
            SystemLog::logError(
                $sessionId,
                'OSCE Service',
                'Failed to start OSCE session: ' . $e->getMessage(),
                ['case_id' => $caseId]
            );

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process user input during OSCE session
     */
    public function processOSCEInput(string $sessionId, string $userInput): array
    {
        $osceSession = OSCESession::bySession($sessionId)->active()->first();
        if (!$osceSession) {
            throw new \Exception('No active OSCE session found');
        }

        $case = $osceSession->osceCase;
        
        try {
            // Add user input to conversation log
            $osceSession->addConversationEntry('user', $userInput);

            // Get conversation history
            $conversationHistory = $osceSession->conversation_log ?? [];

            // Generate AI patient response
            $aiResponse = $this->aiService->generatePatientResponse(
                $case->patient_data,
                $conversationHistory,
                $userInput,
                $osceSession->checklist_progress,
                $sessionId
            );

            if (!$aiResponse['success']) {
                throw new \Exception($aiResponse['error'] ?? 'Failed to generate patient response');
            }

            // Add AI response to conversation log
            $osceSession->addConversationEntry('assistant', $aiResponse['content']);

            // Analyze user input for checklist updates
            $this->analyzeAndUpdateChecklist($osceSession, $userInput, $case);

            return [
                'success' => true,
                'patient_response' => $aiResponse['content'],
                'progress' => $osceSession->getProgressPercentage(),
                'session_duration_minutes' => $osceSession->started_at->diffInMinutes(now()),
                'checklist_categories' => $case->getChecklistCategories()
            ];

        } catch (\Exception $e) {
            SystemLog::logError(
                $sessionId,
                'OSCE Service',
                'Failed to process OSCE input: ' . $e->getMessage(),
                [
                    'user_input' => $userInput,
                    'osce_session_id' => $osceSession->id
                ]
            );

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Complete OSCE session with scoring
     */
    public function completeOSCESession(string $sessionId): array
    {
        $osceSession = OSCESession::bySession($sessionId)->active()->first();
        if (!$osceSession) {
            throw new \Exception('No active OSCE session found');
        }

        $case = $osceSession->osceCase;
        $session = $osceSession->session;

        try {
            // Calculate score
            $score = $this->calculateScore($osceSession, $case);

            // Generate AI feedback
            $feedbackResponse = $this->aiService->generateOSCEFeedback(
                $case->patient_data,
                $osceSession->conversation_log,
                $osceSession->checklist_progress,
                $sessionId
            );

            $feedback = $feedbackResponse['success'] 
                ? $feedbackResponse['content']
                : 'Unable to generate detailed feedback at this time.';

            // Mark session as completed
            $osceSession->markCompleted($score, $feedback);

            // Update main session statistics
            $duration = $osceSession->duration;
            $session->trackOsceSession($duration, $score);

            // Log completion
            SystemLog::logPerformance(
                $sessionId,
                'OSCE Service',
                'OSCE session completed',
                [
                    'case_id' => $case->case_id,
                    'score' => $score,
                    'duration_minutes' => round($duration / 60),
                    'progress_percentage' => $osceSession->getProgressPercentage()
                ]
            );

            return [
                'success' => true,
                'score' => $score,
                'feedback' => $feedback,
                'duration_minutes' => round($duration / 60),
                'progress_percentage' => $osceSession->getProgressPercentage(),
                'checklist_summary' => $this->getChecklistSummary($osceSession, $case),
                'conversation_summary' => $osceSession->getConversationSummary()
            ];

        } catch (\Exception $e) {
            SystemLog::logError(
                $sessionId,
                'OSCE Service',
                'Failed to complete OSCE session: ' . $e->getMessage(),
                ['osce_session_id' => $osceSession->id]
            );

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Abandon current OSCE session
     */
    public function abandonOSCESession(string $sessionId): array
    {
        $osceSession = OSCESession::bySession($sessionId)->active()->first();
        if (!$osceSession) {
            throw new \Exception('No active OSCE session found');
        }

        try {
            $osceSession->markAbandoned();

            SystemLog::logPerformance(
                $sessionId,
                'OSCE Service',
                'OSCE session abandoned',
                [
                    'case_id' => $osceSession->osceCase->case_id,
                    'duration_minutes' => $osceSession->getDurationInMinutes(),
                    'progress_percentage' => $osceSession->getProgressPercentage()
                ]
            );

            return [
                'success' => true,
                'message' => 'OSCE session has been abandoned.'
            ];

        } catch (\Exception $e) {
            SystemLog::logError(
                $sessionId,
                'OSCE Service',
                'Failed to abandon OSCE session: ' . $e->getMessage()
            );

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get current OSCE session status
     */
    public function getOSCEStatus(string $sessionId): array
    {
        $osceSession = OSCESession::bySession($sessionId)->active()->first();
        
        if (!$osceSession) {
            return [
                'active' => false,
                'message' => 'No active OSCE session'
            ];
        }

        $case = $osceSession->osceCase;

        return [
            'active' => true,
            'case' => [
                'id' => $case->case_id,
                'title' => $case->title,
                'category' => $case->category,
                'difficulty' => $case->difficulty
            ],
            'progress_percentage' => $osceSession->getProgressPercentage(),
            'duration_minutes' => $osceSession->started_at->diffInMinutes(now()),
            'conversation_count' => count($osceSession->conversation_log ?? []),
            'checklist_summary' => $this->getChecklistSummary($osceSession, $case)
        ];
    }

    /**
     * Get OSCE session history for a session
     */
    public function getOSCEHistory(string $sessionId): array
    {
        $osceSessions = OSCESession::bySession($sessionId)
            ->with('osceCase')
            ->orderBy('started_at', 'desc')
            ->get();

        return $osceSessions->map(function ($osceSession) {
            return [
                'id' => $osceSession->id,
                'case' => [
                    'id' => $osceSession->osceCase->case_id,
                    'title' => $osceSession->osceCase->title,
                    'category' => $osceSession->osceCase->category
                ],
                'status' => $osceSession->status,
                'started_at' => $osceSession->started_at->toISOString(),
                'completed_at' => $osceSession->completed_at?->toISOString(),
                'duration_minutes' => $osceSession->getDurationInMinutes(),
                'score' => $osceSession->score,
                'progress_percentage' => $osceSession->getProgressPercentage()
            ];
        })->toArray();
    }

    private function initializeChecklistProgress(array $checklist): array
    {
        $progress = [];
        
        foreach ($checklist as $category => $items) {
            $progress[$category] = [];
            foreach ($items as $item) {
                $progress[$category][$item] = false;
            }
        }

        return $progress;
    }

    private function analyzeAndUpdateChecklist(OSCESession $osceSession, string $userInput, OSCECase $case): void
    {
        $input = strtolower($userInput);
        
        // Simple keyword-based checklist analysis
        // This could be enhanced with more sophisticated NLP
        foreach ($case->checklist as $category => $items) {
            foreach ($items as $item) {
                $keywords = $this->getChecklistKeywords($category, $item);
                
                foreach ($keywords as $keyword) {
                    if (str_contains($input, strtolower($keyword))) {
                        $osceSession->updateChecklistItem($category, $item, true);
                        break;
                    }
                }
            }
        }
    }

    private function getChecklistKeywords(string $category, string $item): array
    {
        // Map checklist items to keywords that might indicate completion
        $keywordMap = [
            'history' => [
                'chest pain' => ['nyeri dada', 'sakit dada', 'chest pain'],
                'onset' => ['kapan mulai', 'sejak kapan', 'onset', 'mulai'],
                'duration' => ['berapa lama', 'duration', 'durasi'],
                'severity' => ['seberapa sakit', 'skala nyeri', 'severity', 'tingkat'],
                'radiation' => ['menjalar', 'radiasi', 'radiation'],
                'associated symptoms' => ['gejala lain', 'keluhan lain', 'symptoms']
            ],
            'examination' => [
                'vital signs' => ['tekanan darah', 'tensi', 'blood pressure', 'vital signs', 'nadi', 'pulse'],
                'heart sounds' => ['suara jantung', 'heart sounds', 'auskultasi jantung'],
                'lung examination' => ['paru', 'lung', 'napas', 'breathing', 'auskultasi paru']
            ],
            'investigations' => [
                'ECG' => ['ekg', 'ecg', 'elektrokardiogram', 'rekam jantung'],
                'chest x-ray' => ['rontgen', 'x-ray', 'foto thorax'],
                'blood tests' => ['lab', 'darah', 'blood test', 'laboratory']
            ]
        ];

        $categoryKeywords = $keywordMap[$category] ?? [];
        return $categoryKeywords[$item] ?? [$item];
    }

    private function calculateScore(OSCESession $osceSession, OSCECase $case): float
    {
        $totalScore = 0;
        $maxScore = $case->calculateMaxScore();

        foreach ($osceSession->checklist_progress as $category => $items) {
            $weight = $case->scoring_weights[$category] ?? 1;
            
            foreach ($items as $item => $completed) {
                if ($completed) {
                    $totalScore += $weight;
                }
            }
        }

        return $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 1) : 0;
    }

    private function getChecklistSummary(OSCESession $osceSession, OSCECase $case): array
    {
        $summary = [];

        foreach ($case->checklist as $category => $items) {
            $categoryProgress = $osceSession->getCategoryProgress($category);
            $completed = array_filter($categoryProgress, fn($item) => $item === true);
            
            $summary[$category] = [
                'total_items' => count($items),
                'completed_items' => count($completed),
                'percentage' => count($items) > 0 ? round((count($completed) / count($items)) * 100, 1) : 0,
                'items' => $categoryProgress
            ];
        }

        return $summary;
    }
}