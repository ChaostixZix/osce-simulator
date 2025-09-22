<?php

namespace App\Services;

use App\Models\User;
use App\Models\OsceSession;
use App\Models\LearningStreak;
use App\Models\SpacedRepetitionCard;
use App\Models\GrowthMilestone;
use App\Models\RefresherCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RefresherCaseReady;
use App\Notifications\MilestoneAchieved;

/**
 * Service for managing longitudinal learning growth with spaced repetition.
 * Tracks learning streaks, generates refresher content, and manages growth milestones.
 */
class LongitudinalGrowthService
{
    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Update user's learning progress after session completion
     */
    public function updateLearningProgress(User $user, OsceSession $session): void
    {
        try {
            // Update learning streak
            $this->updateLearningStreak($user, $session);

            // Check for milestones
            $this->checkGrowthMilestones($user);

            // Schedule spaced repetition cards
            $this->scheduleSpacedRepetition($user, $session);

            // Check if refresher case is needed
            $this->scheduleRefresherIfNeeded($user);

        } catch (\Exception $e) {
            Log::error('Failed to update learning progress', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update or create learning streak
     */
    private function updateLearningStreak(User $user, OsceSession $session): void
    {
        $today = now()->startOfDay();
        $yesterday = $today->copy()->subDay();

        $currentStreak = LearningStreak::where('user_id', $user->id)
            ->latest()
            ->first();

        if (!$currentStreak || $currentStreak->last_activity_date < $yesterday) {
            // Start new streak
            LearningStreak::create([
                'user_id' => $user->id,
                'current_streak' => 1,
                'longest_streak' => $currentStreak ? max(1, $currentStreak->longest_streak) : 1,
                'last_activity_date' => $today,
                'total_sessions' => 1,
                'total_study_time' => $this->calculateSessionDuration($session),
                'streak_type' => 'daily_sessions',
            ]);
        } elseif ($currentStreak->last_activity_date->isSameDay($yesterday)) {
            // Continue streak
            $newStreak = $currentStreak->current_streak + 1;
            $currentStreak->update([
                'current_streak' => $newStreak,
                'longest_streak' => max($newStreak, $currentStreak->longest_streak),
                'last_activity_date' => $today,
                'total_sessions' => $currentStreak->total_sessions + 1,
                'total_study_time' => $currentStreak->total_study_time + $this->calculateSessionDuration($session),
            ]);
        } elseif ($currentStreak->last_activity_date->isSameDay($today)) {
            // Same day, just update totals
            $currentStreak->update([
                'total_sessions' => $currentStreak->total_sessions + 1,
                'total_study_time' => $currentStreak->total_study_time + $this->calculateSessionDuration($session),
            ]);
        }
    }

    /**
     * Check and award growth milestones
     */
    private function checkGrowthMilestones(User $user): void
    {
        $userStats = $this->getUserStats($user);

        $milestones = [
            ['type' => 'sessions_completed', 'threshold' => 5, 'title' => 'First Steps', 'description' => '5 OSCE sessions completed'],
            ['type' => 'sessions_completed', 'threshold' => 10, 'title' => 'Getting Started', 'description' => '10 OSCE sessions completed'],
            ['type' => 'sessions_completed', 'threshold' => 25, 'title' => 'Dedicated Learner', 'description' => '25 OSCE sessions completed'],
            ['type' => 'sessions_completed', 'threshold' => 50, 'title' => 'OSCE Veteran', 'description' => '50 OSCE sessions completed'],
            ['type' => 'learning_streak', 'threshold' => 7, 'title' => 'Week Warrior', 'description' => '7-day learning streak'],
            ['type' => 'learning_streak', 'threshold' => 30, 'title' => 'Month Master', 'description' => '30-day learning streak'],
            ['type' => 'study_time', 'threshold' => 600, 'title' => 'Time Invested', 'description' => '10+ hours of study time'],
            ['type' => 'study_time', 'threshold' => 3000, 'title' => 'Serious Student', 'description' => '50+ hours of study time'],
        ];

        foreach ($milestones as $milestone) {
            $achieved = $this->checkMilestoneThreshold($userStats, $milestone);

            if ($achieved) {
                $existing = GrowthMilestone::where('user_id', $user->id)
                    ->where('milestone_type', $milestone['type'])
                    ->where('threshold_value', $milestone['threshold'])
                    ->first();

                if (!$existing) {
                    $growthMilestone = GrowthMilestone::create([
                        'user_id' => $user->id,
                        'milestone_type' => $milestone['type'],
                        'milestone_title' => $milestone['title'],
                        'milestone_description' => $milestone['description'],
                        'threshold_value' => $milestone['threshold'],
                        'achieved_at' => now(),
                        'current_value' => $this->getMilestoneCurrentValue($userStats, $milestone['type']),
                    ]);

                    // Send notification
                    Notification::send($user, new MilestoneAchieved($growthMilestone));
                }
            }
        }
    }

    /**
     * Schedule spaced repetition cards based on session content
     */
    private function scheduleSpacedRepetition(User $user, OsceSession $session): void
    {
        // Analyze session performance to identify knowledge gaps
        $assessmentData = $session->assessmentRuns()->latest()->first();

        if (!$assessmentData || !$assessmentData->area_results) {
            return;
        }

        // Find areas with lower scores for spaced repetition
        $lowScoreAreas = collect($assessmentData->area_results)
            ->filter(function ($area) {
                return isset($area['score']) && $area['score'] < 70; // Below 70%
            });

        foreach ($lowScoreAreas as $area) {
            $this->createSpacedRepetitionCard($user, $session, $area);
        }
    }

    /**
     * Create spaced repetition card for specific area
     */
    private function createSpacedRepetitionCard(User $user, OsceSession $session, array $areaData): void
    {
        // Generate flashcard content using AI
        $cardContent = $this->generateFlashcardContent($session, $areaData);

        if ($cardContent) {
            SpacedRepetitionCard::create([
                'user_id' => $user->id,
                'osce_case_id' => $session->osce_case_id,
                'clinical_area' => $areaData['key'] ?? 'general',
                'card_content' => $cardContent,
                'repetition_level' => 1,
                'easiness_factor' => 2.5,
                'next_review_date' => now()->addDay(), // First review tomorrow
                'created_from_session' => $session->id,
            ]);
        }
    }

    /**
     * Generate flashcard content using AI
     */
    private function generateFlashcardContent(OsceSession $session, array $areaData): ?array
    {
        try {
            $prompt = sprintf(
                "Create a spaced repetition flashcard for medical education based on this OSCE session:\n\n" .
                "Case: %s\n" .
                "Chief Complaint: %s\n" .
                "Clinical Area: %s\n" .
                "Score: %s%%\n" .
                "Feedback: %s\n\n" .
                "Generate a question-answer pair that helps reinforce learning in this area. " .
                "Focus on practical clinical knowledge and reasoning.",
                $session->osceCase->title,
                $session->osceCase->chief_complaint,
                $areaData['key'] ?? 'general',
                $areaData['score'] ?? 'N/A',
                $areaData['justification'] ?? 'Performance gap identified'
            );

            $schema = [
                'type' => 'object',
                'properties' => [
                    'question' => ['type' => 'string'],
                    'answer' => ['type' => 'string'],
                    'difficulty' => ['type' => 'string', 'enum' => ['easy', 'medium', 'hard']],
                    'tags' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'maxItems' => 5
                    ],
                    'explanation' => ['type' => 'string']
                ],
                'required' => ['question', 'answer', 'difficulty']
            ];

            return $this->geminiService->generateJson($schema, $prompt, [
                'temperature' => 0.4,
                'maxOutputTokens' => 1024
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate flashcard content', [
                'session_id' => $session->id,
                'area' => $areaData['key'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Schedule refresher case if needed
     */
    private function scheduleRefresherIfNeeded(User $user): void
    {
        $recentSessions = OsceSession::where('user_id', $user->id)
            ->where('completed_at', '>', now()->subDays(7))
            ->count();

        $totalSessions = OsceSession::where('user_id', $user->id)->count();

        // Schedule refresher every 10 sessions or if inactive for 3+ days
        $lastSession = OsceSession::where('user_id', $user->id)
            ->latest('completed_at')
            ->first();

        $daysSinceLastSession = $lastSession ? $lastSession->completed_at->diffInDays(now()) : 0;

        if ($totalSessions % 10 === 0 || $daysSinceLastSession >= 3) {
            $this->generateRefresherCase($user);
        }
    }

    /**
     * Generate AI-crafted refresher case
     */
    private function generateRefresherCase(User $user): void
    {
        try {
            // Analyze user's performance history
            $performanceAnalysis = $this->analyzeUserPerformance($user);

            $refresherContent = $this->generateRefresherContent($user, $performanceAnalysis);

            if ($refresherContent) {
                $refresherCase = RefresherCase::create([
                    'user_id' => $user->id,
                    'case_content' => $refresherContent,
                    'target_areas' => $performanceAnalysis['weak_areas'] ?? [],
                    'difficulty_level' => $performanceAnalysis['recommended_difficulty'] ?? 'medium',
                    'scheduled_date' => now()->addHours(24),
                    'expires_at' => now()->addDays(7),
                ]);

                // Schedule notification
                Notification::send($user, new RefresherCaseReady($refresherCase));
            }

        } catch (\Exception $e) {
            Log::error('Failed to generate refresher case', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get user learning statistics
     */
    public function getUserStats(User $user): array
    {
        $streak = LearningStreak::where('user_id', $user->id)->latest()->first();

        return [
            'sessions_completed' => OsceSession::where('user_id', $user->id)->count(),
            'current_streak' => $streak?->current_streak ?? 0,
            'longest_streak' => $streak?->longest_streak ?? 0,
            'total_study_time' => $streak?->total_study_time ?? 0,
            'pending_cards' => SpacedRepetitionCard::where('user_id', $user->id)
                ->where('next_review_date', '<=', now())
                ->count(),
            'milestones_achieved' => GrowthMilestone::where('user_id', $user->id)->count(),
            'refresher_cases_available' => RefresherCase::where('user_id', $user->id)
                ->where('completed_at', null)
                ->where('expires_at', '>', now())
                ->count(),
        ];
    }

    /**
     * Get dashboard data for growth visualization
     */
    public function getDashboardData(User $user): array
    {
        $stats = $this->getUserStats($user);

        // Get recent milestones
        $recentMilestones = GrowthMilestone::where('user_id', $user->id)
            ->latest('achieved_at')
            ->take(3)
            ->get();

        // Get pending spaced repetition cards
        $pendingCards = SpacedRepetitionCard::where('user_id', $user->id)
            ->where('next_review_date', '<=', now())
            ->with('osceCase')
            ->take(5)
            ->get();

        // Get available refresher cases
        $availableRefreshers = RefresherCase::where('user_id', $user->id)
            ->whereNull('completed_at')
            ->where('expires_at', '>', now())
            ->latest('scheduled_date')
            ->take(3)
            ->get();

        return [
            'stats' => $stats,
            'recent_milestones' => $recentMilestones,
            'pending_cards' => $pendingCards,
            'available_refreshers' => $availableRefreshers,
            'streak_widget' => $this->getStreakWidget($user),
            'progress_chart' => $this->getProgressChartData($user),
        ];
    }

    /**
     * Process spaced repetition card review
     */
    public function reviewSpacedRepetitionCard(SpacedRepetitionCard $card, int $performance): void
    {
        // SM-2 algorithm implementation
        if ($performance >= 3) {
            // Correct response
            $card->repetition_level++;
            $interval = $this->calculateInterval($card->repetition_level, $card->easiness_factor);
            $card->next_review_date = now()->addDays($interval);
        } else {
            // Incorrect response - reset
            $card->repetition_level = 1;
            $card->next_review_date = now()->addDay();
        }

        // Update easiness factor
        $card->easiness_factor = max(1.3, $card->easiness_factor + (0.1 - (5 - $performance) * (0.08 + (5 - $performance) * 0.02)));

        $card->last_reviewed_at = now();
        $card->review_count++;
        $card->save();
    }

    // Helper methods

    private function calculateSessionDuration(OsceSession $session): int
    {
        if ($session->started_at && $session->completed_at) {
            return $session->started_at->diffInMinutes($session->completed_at);
        }
        return $session->osceCase->duration_minutes ?? 30;
    }

    private function checkMilestoneThreshold(array $userStats, array $milestone): bool
    {
        $value = $this->getMilestoneCurrentValue($userStats, $milestone['type']);
        return $value >= $milestone['threshold'];
    }

    private function getMilestoneCurrentValue(array $userStats, string $type): int
    {
        return match($type) {
            'sessions_completed' => $userStats['sessions_completed'],
            'learning_streak' => $userStats['longest_streak'],
            'study_time' => $userStats['total_study_time'],
            default => 0
        };
    }

    private function analyzeUserPerformance(User $user): array
    {
        // Simple performance analysis
        $recentSessions = OsceSession::where('user_id', $user->id)
            ->with('assessmentRuns')
            ->latest()
            ->take(5)
            ->get();

        $weakAreas = [];
        $averageScores = [];

        foreach ($recentSessions as $session) {
            $assessment = $session->assessmentRuns()->latest()->first();
            if ($assessment && $assessment->area_results) {
                foreach ($assessment->area_results as $area) {
                    if (isset($area['score']) && $area['score'] < 70) {
                        $weakAreas[] = $area['key'] ?? 'general';
                    }
                    $averageScores[] = $area['score'] ?? 0;
                }
            }
        }

        $avgScore = count($averageScores) > 0 ? array_sum($averageScores) / count($averageScores) : 70;

        return [
            'weak_areas' => array_unique($weakAreas),
            'average_score' => $avgScore,
            'recommended_difficulty' => $avgScore >= 80 ? 'hard' : ($avgScore >= 60 ? 'medium' : 'easy'),
            'sessions_analyzed' => $recentSessions->count()
        ];
    }

    private function generateRefresherContent(User $user, array $analysis): ?array
    {
        // Generate refresher case content using AI
        try {
            $prompt = sprintf(
                "Generate a refresher OSCE case for continued learning:\n\n" .
                "Target Areas: %s\n" .
                "Difficulty: %s\n" .
                "Average Score: %.1f%%\n" .
                "Sessions Analyzed: %d\n\n" .
                "Create a brief case scenario that focuses on the weak areas while being appropriately challenging.",
                implode(', ', $analysis['weak_areas'] ?? ['general clinical reasoning']),
                $analysis['recommended_difficulty'],
                $analysis['average_score'],
                $analysis['sessions_analyzed']
            );

            $schema = [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'scenario' => ['type' => 'string'],
                    'learning_objectives' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'maxItems' => 5
                    ],
                    'estimated_duration' => ['type' => 'integer', 'minimum' => 10, 'maximum' => 45]
                ],
                'required' => ['title', 'scenario', 'learning_objectives']
            ];

            return $this->geminiService->generateJson($schema, $prompt, [
                'temperature' => 0.6,
                'maxOutputTokens' => 1024
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate refresher content', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function getStreakWidget(User $user): array
    {
        $streak = LearningStreak::where('user_id', $user->id)->latest()->first();

        return [
            'current_streak' => $streak?->current_streak ?? 0,
            'longest_streak' => $streak?->longest_streak ?? 0,
            'last_activity' => $streak?->last_activity_date,
            'next_milestone' => $this->getNextStreakMilestone($streak?->current_streak ?? 0),
        ];
    }

    private function getProgressChartData(User $user): array
    {
        // Get last 30 days of activity
        $sessions = OsceSession::where('user_id', $user->id)
            ->where('completed_at', '>', now()->subDays(30))
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $sessions->map(function ($session) {
            return [
                'date' => $session->date,
                'sessions' => $session->count
            ];
        })->toArray();
    }

    private function getNextStreakMilestone(int $currentStreak): ?int
    {
        $milestones = [7, 14, 30, 60, 100, 365];

        foreach ($milestones as $milestone) {
            if ($currentStreak < $milestone) {
                return $milestone;
            }
        }

        return null;
    }

    private function calculateInterval(int $repetitionLevel, float $easinessFactor): int
    {
        // SM-2 algorithm
        if ($repetitionLevel === 1) return 1;
        if ($repetitionLevel === 2) return 6;

        return max(1, round(($repetitionLevel - 1) * $easinessFactor));
    }
}