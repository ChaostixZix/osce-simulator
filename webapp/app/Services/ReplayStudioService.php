<?php

namespace App\Services;

use App\Models\OsceSession;
use App\Models\OsceChatMessage;
use App\Models\SessionOrderedTest;
use App\Models\SessionExamination;
use App\Models\SessionReplay;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for generating post-session replay analysis with alternative scenarios.
 * Creates timeline visualizations and "what-if" analysis using AI.
 */
class ReplayStudioService
{
    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Generate comprehensive replay analysis for completed session
     */
    public function generateReplay(OsceSession $session): array
    {
        try {
            // Check if replay already exists and is recent
            $existingReplay = SessionReplay::where('osce_session_id', $session->id)
                ->where('created_at', '>', now()->subHours(1))
                ->first();

            if ($existingReplay) {
                return $existingReplay->replay_data;
            }

            // Gather session data
            $sessionData = $this->gatherSessionData($session);

            // Generate timeline analysis
            $timeline = $this->generateTimeline($sessionData);

            // Generate alternative scenarios
            $alternatives = $this->generateAlternativeScenarios($session, $sessionData);

            // Generate performance insights
            $insights = $this->generatePerformanceInsights($session, $sessionData);

            // Generate voiceover scripts
            $voiceovers = $this->generateVoiceoverScripts($timeline, $alternatives, $insights);

            $replayData = [
                'session_id' => $session->id,
                'timeline' => $timeline,
                'alternative_scenarios' => $alternatives,
                'performance_insights' => $insights,
                'voiceover_scripts' => $voiceovers,
                'generated_at' => now(),
                'session_summary' => $this->generateSessionSummary($sessionData),
            ];

            // Save replay to database
            SessionReplay::updateOrCreate(
                ['osce_session_id' => $session->id],
                ['replay_data' => $replayData]
            );

            return $replayData;

        } catch (\Exception $e) {
            Log::error('Replay generation failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);

            return $this->getFallbackReplay($session);
        }
    }

    /**
     * Gather all session data for analysis
     */
    private function gatherSessionData(OsceSession $session): array
    {
        $chatMessages = OsceChatMessage::where('osce_session_id', $session->id)
            ->orderBy('created_at')
            ->get();

        $orderedTests = SessionOrderedTest::where('osce_session_id', $session->id)
            ->with('medicalTest')
            ->orderBy('created_at')
            ->get();

        $examinations = SessionExamination::where('osce_session_id', $session->id)
            ->orderBy('created_at')
            ->get();

        return [
            'session' => $session,
            'chat_messages' => $chatMessages,
            'ordered_tests' => $orderedTests,
            'examinations' => $examinations,
            'duration_minutes' => $session->started_at && $session->completed_at
                ? $session->started_at->diffInMinutes($session->completed_at)
                : ($session->osceCase->duration_minutes ?? 30),
            'total_cost' => $orderedTests->sum('cost'),
            'assessment_data' => $session->assessmentRuns()->latest()->first()?->toArray(),
        ];
    }

    /**
     * Generate interactive timeline with pivotal moments
     */
    private function generateTimeline(array $sessionData): array
    {
        $events = [];
        $session = $sessionData['session'];
        $startTime = $session->started_at;

        // Add chat milestones
        foreach ($sessionData['chat_messages'] as $message) {
            if ($message->sender_type === 'user') {
                $minutesFromStart = $startTime ? $startTime->diffInMinutes($message->created_at) : 0;

                $events[] = [
                    'type' => 'chat',
                    'timestamp' => $message->created_at,
                    'minutes_from_start' => $minutesFromStart,
                    'content' => $message->message,
                    'significance' => $this->assessMessageSignificance($message->message, $sessionData),
                ];
            }
        }

        // Add test orders
        foreach ($sessionData['ordered_tests'] as $test) {
            $minutesFromStart = $startTime ? $startTime->diffInMinutes($test->created_at) : 0;

            $events[] = [
                'type' => 'test_order',
                'timestamp' => $test->created_at,
                'minutes_from_start' => $minutesFromStart,
                'content' => $test->medicalTest->name ?? 'Test',
                'cost' => $test->cost,
                'reasoning' => $test->clinical_reasoning,
                'significance' => $this->assessTestSignificance($test, $sessionData),
            ];
        }

        // Add examinations
        foreach ($sessionData['examinations'] as $exam) {
            $minutesFromStart = $startTime ? $startTime->diffInMinutes($exam->created_at) : 0;

            $events[] = [
                'type' => 'examination',
                'timestamp' => $exam->created_at,
                'minutes_from_start' => $minutesFromStart,
                'content' => "{$exam->category} - {$exam->type}",
                'findings' => $exam->findings,
                'significance' => $this->assessExamSignificance($exam, $sessionData),
            ];
        }

        // Sort by timestamp
        usort($events, function ($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        // Identify pivotal moments
        $pivotalMoments = $this->identifyPivotalMoments($events, $sessionData);

        return [
            'events' => $events,
            'pivotal_moments' => $pivotalMoments,
            'duration_minutes' => $sessionData['duration_minutes'],
            'phase_breakdown' => $this->analyzeSessionPhases($events),
        ];
    }

    /**
     * Generate alternative scenario analysis
     */
    private function generateAlternativeScenarios(OsceSession $session, array $sessionData): array
    {
        $prompt = $this->buildAlternativeScenarioPrompt($session, $sessionData);

        $schema = [
            'type' => 'object',
            'properties' => [
                'scenarios' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'decision_point' => ['type' => 'string'],
                            'alternative_action' => ['type' => 'string'],
                            'likely_outcome' => ['type' => 'string'],
                            'learning_point' => ['type' => 'string'],
                            'difficulty_change' => ['type' => 'string', 'enum' => ['easier', 'harder', 'similar']],
                            'clinical_area' => ['type' => 'string']
                        ]
                    ],
                    'minItems' => 3,
                    'maxItems' => 6
                ]
            ],
            'required' => ['scenarios']
        ];

        $result = $this->geminiService->generateJson($schema, $prompt, [
            'temperature' => 0.4,
            'maxOutputTokens' => 2048
        ]);

        return $result['scenarios'] ?? $this->getFallbackScenarios();
    }

    /**
     * Generate performance insights and improvement areas
     */
    private function generatePerformanceInsights(OsceSession $session, array $sessionData): array
    {
        $insights = [
            'strengths' => [],
            'improvement_areas' => [],
            'efficiency_analysis' => [],
            'clinical_reasoning_feedback' => [],
            'resource_management' => [],
        ];

        // Analyze timing efficiency
        $duration = $sessionData['duration_minutes'];
        $expectedDuration = $session->osceCase->duration_minutes ?? 30;

        if ($duration < $expectedDuration * 0.7) {
            $insights['efficiency_analysis'][] = 'Session completed quickly - ensure thoroughness wasn\'t sacrificed for speed';
        } elseif ($duration > $expectedDuration) {
            $insights['efficiency_analysis'][] = 'Session exceeded time limit - focus on time management and prioritization';
        } else {
            $insights['strengths'][] = 'Good time management throughout the session';
        }

        // Analyze test ordering patterns
        $testCount = count($sessionData['ordered_tests']);
        $totalCost = $sessionData['total_cost'];

        if ($testCount > 8) {
            $insights['improvement_areas'][] = 'Consider more targeted test ordering based on clinical reasoning';
        } elseif ($testCount < 2) {
            $insights['improvement_areas'][] = 'May benefit from more comprehensive investigation strategy';
        }

        if ($totalCost > 1000) {
            $insights['resource_management'][] = 'Focus on cost-effective test selection';
        }

        // Analyze communication patterns
        $messageCount = count($sessionData['chat_messages']->where('sender_type', 'user'));
        if ($messageCount < 5) {
            $insights['improvement_areas'][] = 'Consider more thorough history taking';
        } elseif ($messageCount > 20) {
            $insights['improvement_areas'][] = 'Aim for more focused questioning';
        }

        return $insights;
    }

    /**
     * Generate voiceover scripts for different sections
     */
    private function generateVoiceoverScripts(array $timeline, array $alternatives, array $insights): array
    {
        return [
            'introduction' => "Let's review your OSCE session performance. This replay will help you understand key decision points and explore alternative approaches.",
            'timeline_overview' => "Your session lasted {$timeline['duration_minutes']} minutes with " . count($timeline['events']) . " key actions recorded.",
            'pivotal_moments' => "These were the most significant moments in your session that shaped the clinical outcome.",
            'alternatives_intro' => "Now let's explore what might have happened with different clinical decisions.",
            'performance_summary' => "Based on your session data, here are key areas for continued learning and development.",
            'conclusion' => "Remember, clinical reasoning is an iterative process. Each case builds your diagnostic skills and clinical judgment.",
        ];
    }

    /**
     * Generate session summary
     */
    private function generateSessionSummary(array $sessionData): array
    {
        return [
            'case_title' => $sessionData['session']->osceCase->title,
            'duration_minutes' => $sessionData['duration_minutes'],
            'messages_exchanged' => $sessionData['chat_messages']->count(),
            'tests_ordered' => count($sessionData['ordered_tests']),
            'examinations_performed' => count($sessionData['examinations']),
            'total_cost' => $sessionData['total_cost'],
            'completion_status' => $sessionData['session']->status,
        ];
    }

    /**
     * Build prompt for alternative scenario generation
     */
    private function buildAlternativeScenarioPrompt(OsceSession $session, array $sessionData): string
    {
        $testNames = $sessionData['ordered_tests']->pluck('medicalTest.name')->implode(', ');
        $examinations = $sessionData['examinations']->map(function ($exam) {
            return "{$exam->category} - {$exam->type}";
        })->implode(', ');

        return sprintf(
            "Analyze this completed OSCE session and generate alternative scenarios:\n\n" .
            "Case: %s\n" .
            "Chief Complaint: %s\n" .
            "Duration: %d minutes\n" .
            "Tests Ordered: %s\n" .
            "Examinations: %s\n" .
            "Total Cost: $%.2f\n\n" .
            "Generate 3-6 alternative scenarios showing what could have happened with different clinical decisions. " .
            "Focus on realistic alternatives that demonstrate different diagnostic approaches, timing choices, " .
            "or resource utilization strategies. Each scenario should include a clear learning point.",
            $session->osceCase->title,
            $session->osceCase->chief_complaint,
            $sessionData['duration_minutes'],
            $testNames ?: 'None',
            $examinations ?: 'None',
            $sessionData['total_cost']
        );
    }

    /**
     * Get fallback replay when AI generation fails
     */
    private function getFallbackReplay(OsceSession $session): array
    {
        return [
            'session_id' => $session->id,
            'timeline' => [
                'events' => [],
                'pivotal_moments' => [],
                'duration_minutes' => $session->osceCase->duration_minutes ?? 30,
                'phase_breakdown' => ['history' => 40, 'examination' => 30, 'investigations' => 30]
            ],
            'alternative_scenarios' => $this->getFallbackScenarios(),
            'performance_insights' => [
                'strengths' => ['Session completed successfully'],
                'improvement_areas' => ['Continue practicing clinical reasoning'],
                'efficiency_analysis' => ['Review session timing'],
                'clinical_reasoning_feedback' => ['Focus on systematic approach'],
                'resource_management' => ['Consider cost-effectiveness']
            ],
            'voiceover_scripts' => [
                'introduction' => 'Session replay is available for review.',
            ],
            'session_summary' => [
                'case_title' => $session->osceCase->title,
                'completion_status' => 'completed'
            ],
            'fallback' => true,
            'generated_at' => now()
        ];
    }

    /**
     * Get fallback scenarios
     */
    private function getFallbackScenarios(): array
    {
        return [
            [
                'title' => 'Earlier Investigation',
                'description' => 'What if you had ordered key tests earlier in the session?',
                'decision_point' => 'Test ordering timing',
                'alternative_action' => 'Order focused tests after initial history',
                'likely_outcome' => 'Faster diagnosis with similar accuracy',
                'learning_point' => 'Strategic test timing can improve efficiency',
                'difficulty_change' => 'easier',
                'clinical_area' => 'investigation'
            ],
            [
                'title' => 'Different History Approach',
                'description' => 'Alternative questioning strategies for gathering information',
                'decision_point' => 'History taking method',
                'alternative_action' => 'Focus on different symptom domains',
                'likely_outcome' => 'Different information priorities',
                'learning_point' => 'Multiple valid approaches to history taking',
                'difficulty_change' => 'similar',
                'clinical_area' => 'history'
            ]
        ];
    }

    // Helper methods for analysis
    private function assessMessageSignificance(string $message, array $sessionData): string
    {
        // Simple keyword-based significance assessment
        $highSignificanceKeywords = ['pain', 'when', 'started', 'family history', 'medications', 'allergies'];

        foreach ($highSignificanceKeywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                return 'high';
            }
        }

        return strlen($message) > 50 ? 'medium' : 'low';
    }

    private function assessTestSignificance(SessionOrderedTest $test, array $sessionData): string
    {
        // Assess based on cost and timing
        if ($test->cost > 200) return 'high';
        if ($test->cost > 50) return 'medium';
        return 'low';
    }

    private function assessExamSignificance(SessionExamination $exam, array $sessionData): string
    {
        // Basic significance based on type
        $highSignificanceExams = ['cardiovascular', 'respiratory', 'neurological'];

        return in_array(strtolower($exam->category), $highSignificanceExams) ? 'high' : 'medium';
    }

    private function identifyPivotalMoments(array $events, array $sessionData): array
    {
        // Find high-significance events
        return array_filter($events, function ($event) {
            return $event['significance'] === 'high';
        });
    }

    private function analyzeSessionPhases(array $events): array
    {
        // Simple phase analysis
        $totalEvents = count($events);
        if ($totalEvents === 0) return ['history' => 50, 'investigation' => 30, 'examination' => 20];

        $chatEvents = array_filter($events, fn($e) => $e['type'] === 'chat');
        $testEvents = array_filter($events, fn($e) => $e['type'] === 'test_order');
        $examEvents = array_filter($events, fn($e) => $e['type'] === 'examination');

        return [
            'history' => round((count($chatEvents) / $totalEvents) * 100),
            'investigation' => round((count($testEvents) / $totalEvents) * 100),
            'examination' => round((count($examEvents) / $totalEvents) * 100),
        ];
    }
}