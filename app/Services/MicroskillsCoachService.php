<?php

namespace App\Services;

use App\Models\OsceSession;
use App\Models\OsceChatMessage;
use App\Models\SessionOrderedTest;
use App\Models\CoachingIntervention;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service for providing real-time coaching during OSCE sessions.
 * Analyzes session patterns and provides contextual hints and interventions.
 */
class MicroskillsCoachService
{
    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Analyze session and provide coaching intervention if needed
     */
    public function analyzeSession(OsceSession $session): ?array
    {
        try {
            // Check if we've provided coaching recently
            $recentIntervention = CoachingIntervention::where('osce_session_id', $session->id)
                ->where('created_at', '>', now()->subMinutes(3))
                ->latest()
                ->first();

            if ($recentIntervention) {
                return null; // Don't spam with interventions
            }

            // Analyze session patterns
            $analysis = $this->analyzeSessionPatterns($session);

            if (!$analysis['needs_intervention']) {
                return null;
            }

            // Generate coaching intervention
            $intervention = $this->generateIntervention($session, $analysis);

            if ($intervention) {
                // Save intervention
                CoachingIntervention::create([
                    'osce_session_id' => $session->id,
                    'intervention_type' => $intervention['type'],
                    'trigger_reason' => $analysis['trigger_reason'],
                    'content' => $intervention['content'],
                    'priority' => $intervention['priority'],
                    'displayed_at' => null, // Will be set when displayed
                ]);

                return $intervention;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Microskills coaching analysis failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Analyze session patterns for coaching opportunities
     */
    private function analyzeSessionPatterns(OsceSession $session): array
    {
        $patterns = [
            'needs_intervention' => false,
            'trigger_reason' => '',
            'session_progress' => 0,
            'decision_patterns' => [],
        ];

        // Calculate session progress
        $startTime = $session->started_at;
        $duration = $session->osceCase->duration_minutes ?? 30;
        $elapsed = $startTime ? now()->diffInMinutes($startTime) : 0;
        $patterns['session_progress'] = min(100, ($elapsed / $duration) * 100);

        // Analyze chat patterns
        $recentMessages = OsceChatMessage::where('osce_session_id', $session->id)
            ->where('created_at', '>', now()->subMinutes(5))
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $totalMessages = OsceChatMessage::where('osce_session_id', $session->id)->count();

        // Detect long pauses (decision fatigue)
        if ($recentMessages->isEmpty() && $totalMessages > 0) {
            $lastMessage = OsceChatMessage::where('osce_session_id', $session->id)
                ->latest()
                ->first();

            if ($lastMessage && $lastMessage->created_at < now()->subMinutes(3)) {
                $patterns['needs_intervention'] = true;
                $patterns['trigger_reason'] = 'long_pause';
                return $patterns;
            }
        }

        // Detect excessive testing without clear reasoning
        $orderedTests = SessionOrderedTest::where('osce_session_id', $session->id)->count();
        $timeElapsed = $elapsed;

        if ($orderedTests > 5 && $timeElapsed < 10) {
            $patterns['needs_intervention'] = true;
            $patterns['trigger_reason'] = 'excessive_testing';
            return $patterns;
        }

        // Detect late in session without any tests
        if ($patterns['session_progress'] > 60 && $orderedTests === 0) {
            $patterns['needs_intervention'] = true;
            $patterns['trigger_reason'] = 'late_no_tests';
            return $patterns;
        }

        // Detect repetitive questioning
        $userMessages = $recentMessages->where('sender_type', 'user');
        if ($userMessages->count() >= 3) {
            $messageSimilarity = $this->calculateMessageSimilarity($userMessages->pluck('message')->toArray());
            if ($messageSimilarity > 0.7) {
                $patterns['needs_intervention'] = true;
                $patterns['trigger_reason'] = 'repetitive_questions';
                return $patterns;
            }
        }

        // Detect rushed behavior (too fast)
        if ($patterns['session_progress'] > 80 && $timeElapsed < ($duration * 0.5)) {
            $patterns['needs_intervention'] = true;
            $patterns['trigger_reason'] = 'too_fast';
            return $patterns;
        }

        return $patterns;
    }

    /**
     * Generate appropriate coaching intervention
     */
    private function generateIntervention(OsceSession $session, array $analysis): ?array
    {
        $triggerReason = $analysis['trigger_reason'];
        $progress = $analysis['session_progress'];

        $interventionTemplates = [
            'long_pause' => [
                'type' => 'decision_support',
                'priority' => 'medium',
                'prompts' => [
                    'Consider your next steps systematically. What additional history might help narrow your differential?',
                    'Take a moment to review what you\'ve learned so far. What physical examination would be most useful?',
                    'Feeling stuck? Try asking about associated symptoms or reviewing the timeline.',
                ]
            ],
            'excessive_testing' => [
                'type' => 'resource_management',
                'priority' => 'high',
                'prompts' => [
                    'Consider the cost-effectiveness of your investigations. What\'s your clinical reasoning for these tests?',
                    'Focus on targeted testing based on your differential diagnosis. Each test should have a clear purpose.',
                    'Remember: good clinical reasoning often reduces the need for extensive testing.',
                ]
            ],
            'late_no_tests' => [
                'type' => 'time_management',
                'priority' => 'high',
                'prompts' => [
                    'You\'re well into the session. Consider what investigations might help confirm your suspected diagnosis.',
                    'Time is advancing - what key tests would help you make clinical decisions?',
                    'Based on your history so far, what focused investigations would be most valuable?',
                ]
            ],
            'repetitive_questions' => [
                'type' => 'communication',
                'priority' => 'medium',
                'prompts' => [
                    'Try varying your questioning approach. Consider open-ended questions or exploring different symptom domains.',
                    'You\'ve covered this area well. What other aspects of the history might be relevant?',
                    'Consider moving to physical examination or a different line of questioning.',
                ]
            ],
            'too_fast' => [
                'type' => 'time_management',
                'priority' => 'medium',
                'prompts' => [
                    'You\'re making good progress, but ensure you\'re being thorough. Quality over speed.',
                    'Consider if you\'ve gathered enough information before moving to investigations.',
                    'Take time to build a complete picture - you have time remaining.',
                ]
            ]
        ];

        if (!isset($interventionTemplates[$triggerReason])) {
            return null;
        }

        $template = $interventionTemplates[$triggerReason];
        $content = $template['prompts'][array_rand($template['prompts'])];

        // Personalize intervention with case context
        $caseContext = $this->getCaseContext($session);
        if ($caseContext) {
            $content = $this->personalizeIntervention($content, $caseContext, $triggerReason);
        }

        return [
            'type' => $template['type'],
            'priority' => $template['priority'],
            'content' => $content,
            'trigger' => $triggerReason,
            'session_progress' => $progress,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get micro-quiz for knowledge reinforcement
     */
    public function generateMicroQuiz(OsceSession $session): ?array
    {
        try {
            $cacheKey = "micro_quiz_{$session->id}_" . floor(time() / 300); // 5-minute cache

            return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($session) {
                $sessionContext = $this->getSessionContext($session);

                $prompt = sprintf(
                    "Based on this OSCE session context, create a quick micro-quiz question for reinforcement learning:\n\n" .
                    "Case: %s\n" .
                    "Chief Complaint: %s\n" .
                    "Session Progress: %s\n\n" .
                    "Create a single multiple-choice question (4 options) that reinforces key clinical concepts " .
                    "relevant to this case. Focus on practical knowledge that helps with clinical reasoning. " .
                    "Make it educational but not too obvious.",
                    $session->osceCase->title,
                    $session->osceCase->chief_complaint,
                    $sessionContext
                );

                $schema = [
                    'type' => 'object',
                    'properties' => [
                        'question' => ['type' => 'string'],
                        'options' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'minItems' => 4,
                            'maxItems' => 4
                        ],
                        'correct_answer' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 3],
                        'explanation' => ['type' => 'string'],
                        'topic' => ['type' => 'string']
                    ],
                    'required' => ['question', 'options', 'correct_answer', 'explanation', 'topic']
                ];

                $result = $this->geminiService->generateJson($schema, $prompt, [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 1024
                ]);

                return $result ?: null;
            });

        } catch (\Exception $e) {
            Log::error('Micro-quiz generation failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Mark intervention as displayed
     */
    public function markInterventionDisplayed(int $interventionId): void
    {
        CoachingIntervention::where('id', $interventionId)
            ->update(['displayed_at' => now()]);
    }

    /**
     * Get coaching statistics for session
     */
    public function getCoachingStats(OsceSession $session): array
    {
        $interventions = CoachingIntervention::where('osce_session_id', $session->id)->get();

        return [
            'total_interventions' => $interventions->count(),
            'interventions_by_type' => $interventions->groupBy('intervention_type')->map->count(),
            'displayed_interventions' => $interventions->whereNotNull('displayed_at')->count(),
            'priority_breakdown' => $interventions->groupBy('priority')->map->count(),
        ];
    }

    /**
     * Calculate similarity between messages
     */
    private function calculateMessageSimilarity(array $messages): float
    {
        if (count($messages) < 2) return 0;

        $similarities = [];
        for ($i = 0; $i < count($messages) - 1; $i++) {
            for ($j = $i + 1; $j < count($messages); $j++) {
                $similarities[] = $this->stringSimilarity($messages[$i], $messages[$j]);
            }
        }

        return count($similarities) > 0 ? array_sum($similarities) / count($similarities) : 0;
    }

    /**
     * Simple string similarity calculation
     */
    private function stringSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) return 1.0;

        $len1 = strlen($str1);
        $len2 = strlen($str2);

        if ($len1 === 0 || $len2 === 0) return 0;

        $intersection = count(array_intersect(str_split($str1), str_split($str2)));
        $union = $len1 + $len2 - $intersection;

        return $union > 0 ? $intersection / $union : 0;
    }

    /**
     * Get case context for personalization
     */
    private function getCaseContext(OsceSession $session): ?string
    {
        return $session->osceCase->chief_complaint ?? null;
    }

    /**
     * Personalize intervention content
     */
    private function personalizeIntervention(string $content, string $caseContext, string $trigger): string
    {
        // Simple personalization based on case context
        if (strpos(strtolower($caseContext), 'chest pain') !== false) {
            $content .= ' Consider cardiac risk factors and ECG findings.';
        } elseif (strpos(strtolower($caseContext), 'shortness of breath') !== false) {
            $content .= ' Think about respiratory and cardiac causes.';
        } elseif (strpos(strtolower($caseContext), 'abdominal pain') !== false) {
            $content .= ' Consider location, timing, and associated symptoms.';
        }

        return $content;
    }

    /**
     * Get session context summary
     */
    private function getSessionContext(OsceSession $session): string
    {
        $messageCount = OsceChatMessage::where('osce_session_id', $session->id)->count();
        $testCount = SessionOrderedTest::where('osce_session_id', $session->id)->count();

        $elapsed = $session->started_at ? now()->diffInMinutes($session->started_at) : 0;

        return "Messages exchanged: {$messageCount}, Tests ordered: {$testCount}, Time elapsed: {$elapsed} minutes";
    }
}