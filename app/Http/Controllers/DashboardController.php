<?php

namespace App\Http\Controllers;

use App\Models\OsceCase;
use App\Models\OsceSession;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $allowedTabs = ['overview', 'cases', 'progress', 'history'];
        $requestedTab = $request->query('tab', 'overview');
        $activeTab = in_array($requestedTab, $allowedTabs, true) ? $requestedTab : 'overview';

        $cases = OsceCase::query()
            ->select([
                'id',
                'title',
                'description',
                'difficulty',
                'duration_minutes',
                'clinical_setting',
                'urgency_level',
                'is_active',
            ])
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->withCount([
                'sessions as user_sessions_count' => fn ($query) => $query->where('user_id', $user->id),
                'sessions as completed_sessions_count' => fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('status', 'completed'),
            ])
            ->get();

        $sessions = OsceSession::query()
            ->with(['osceCase:id,title,difficulty,duration_minutes,clinical_setting'])
            ->where('user_id', $user->id)
            ->orderByDesc('started_at')
            ->orderByDesc('created_at')
            ->take(40)
            ->get();

        $completedSessions = $sessions->where('status', 'completed');
        $completedSessionsCount = $completedSessions->count();
        $totalSessionsCount = $sessions->count();

        $averageScore = $completedSessionsCount === 0
            ? null
            : (int) round($completedSessions->avg(fn (OsceSession $session) => $session->score ?? 0));

        $bestScore = $completedSessionsCount === 0
            ? null
            : (int) $completedSessions->max(fn (OsceSession $session) => $session->score ?? 0);

        $totalMinutes = $completedSessions->sum(fn (OsceSession $session) => $session->duration_minutes);
        $completionRate = $totalSessionsCount > 0 ? (int) round(($completedSessionsCount / $totalSessionsCount) * 100) : 0;

        $scoreTrendCollection = $completedSessions
            ->sortBy(fn (OsceSession $session) => $session->completed_at ?? $session->started_at ?? $session->created_at)
            ->values();

        if ($scoreTrendCollection->count() > 8) {
            $scoreTrendCollection = $scoreTrendCollection->slice(-8);
        }

        $scoreTrend = $scoreTrendCollection->map(fn (OsceSession $session) => [
            'label' => optional($session->completed_at ?? $session->started_at ?? $session->created_at)?->format('M j'),
            'score' => $session->score,
            'max_score' => $session->max_score,
        ])->values();

        $recentActivity = $sessions->take(5)->map(fn (OsceSession $session) => [
            'id' => $session->id,
            'case_title' => $session->osceCase?->title ?? 'Unknown case',
            'status' => $session->status,
            'started_at' => optional($session->started_at ?? $session->created_at)?->toIso8601String(),
            'completed_at' => optional($session->completed_at)?->toIso8601String(),
            'score' => $session->score,
            'max_score' => $session->max_score,
        ])->values();

        $historySessions = $sessions->take(15)->map(fn (OsceSession $session) => [
            'id' => $session->id,
            'case_title' => $session->osceCase?->title ?? 'Unknown case',
            'status' => $session->status,
            'score' => $session->score,
            'max_score' => $session->max_score,
            'duration_minutes' => $session->duration_minutes,
            'started_at' => optional($session->started_at ?? $session->created_at)?->toIso8601String(),
            'completed_at' => optional($session->completed_at)?->toIso8601String(),
            'result_url' => $session->status === 'completed'
                ? route('osce.results.show', $session)
                : null,
        ])->values();

        $streakDays = $this->calculateStreakDays($completedSessions);

        $skillBreakdown = [
            [
                'label' => 'Clinical reasoning',
                'value' => $completedSessionsCount === 0
                    ? 0
                    : (int) round($completedSessions->avg(fn (OsceSession $session) => $session->clinical_reasoning_score ?? $session->score ?? 0)),
                'description' => 'Average reasoning score across completed cases',
            ],
            [
                'label' => 'Completion rate',
                'value' => $completionRate,
                'description' => 'Completed sessions versus total attempts',
            ],
            [
                'label' => 'Case diversity',
                'value' => $completedSessionsCount === 0
                    ? 0
                    : (int) round(($completedSessions->unique('osce_case_id')->count() / max(1, $cases->count())) * 100),
                'description' => 'Different cases completed across the library',
            ],
        ];

        $milestones = [
            [
                'title' => 'Complete 5 sessions',
                'current' => $completedSessionsCount,
                'target' => 5,
            ],
            [
                'title' => 'Maintain 7-day streak',
                'current' => $streakDays,
                'target' => 7,
            ],
            [
                'title' => 'Explore 6 specialties',
                'current' => $completedSessions->unique(fn (OsceSession $session) => $session->osceCase?->clinical_setting)->count(),
                'target' => 6,
            ],
        ];

        $flowSteps = $this->buildFlowSteps($sessions);

        $caseStatusById = $this->buildCaseStatuses($cases, $sessions);

        $caseFilters = [
            'difficulties' => $cases->pluck('difficulty')->filter()->unique()->values(),
            'settings' => $cases->pluck('clinical_setting')->filter()->unique()->values(),
            'statuses' => collect(array_values($caseStatusById))->pluck('status')->unique()->values(),
        ];

        $caseItems = $cases->map(function (OsceCase $case) use ($caseStatusById) {
            $statusMeta = $caseStatusById[$case->id] ?? ['status' => $case->is_active ? 'available' : 'locked', 'attempts' => 0, 'completed_attempts' => 0];

            return [
                'id' => $case->id,
                'title' => $case->title,
                'summary' => Str::limit(strip_tags($case->description ?? ''), 140),
                'difficulty' => $case->difficulty,
                'duration_minutes' => $case->duration_minutes,
                'clinical_setting' => $case->clinical_setting,
                'urgency_level' => $case->urgency_level,
                'is_active' => (bool) $case->is_active,
                'status' => $statusMeta['status'],
                'attempts' => $statusMeta['attempts'],
                'completed_attempts' => $statusMeta['completed_attempts'],
            ];
        })->values();

        return Inertia::render('Dashboard', [
            'meta' => [
                'active_tab' => $activeTab,
            ],
            'welcome' => [
                'title' => $user?->name
                    ? 'Welcome back, '.$user->name
                    : 'Welcome back 👋',
                'message' => 'Navigate your OSCE preparation with guided flows, analytics, and curated cases.',
            ],
            'overview' => [
                'quick_stats' => [
                    [
                        'label' => 'Active cases',
                        'value' => $cases->where('is_active', true)->count(),
                        'description' => 'Available scenarios ready to launch',
                        'badge' => 'Library',
                    ],
                    [
                        'label' => 'Completed sessions',
                        'value' => $completedSessionsCount,
                        'description' => 'Tracked across your preparation timeline',
                        'badge' => 'Progress',
                    ],
                    [
                        'label' => 'Average score',
                        'value' => $averageScore,
                        'description' => 'Calculated from assessed sessions',
                        'badge' => 'Performance',
                        'suffix' => '%',
                    ],
                    [
                        'label' => 'Total minutes trained',
                        'value' => $totalMinutes,
                        'description' => 'Time invested across completed sessions',
                        'badge' => 'Time on task',
                    ],
                ],
                'flow' => $flowSteps,
                'recent_activity' => $recentActivity,
                'system_status' => [
                    ['label' => 'Assessment AI', 'value' => 'Operational'],
                    ['label' => 'Realtime trainer', 'value' => 'Stable'],
                    ['label' => 'Data sync', 'value' => 'Up to date'],
                ],
            ],
            'cases' => [
                'items' => $caseItems,
                'filters' => $caseFilters,
            ],
            'progress' => [
                'score_trend' => $scoreTrend,
                'skill_breakdown' => $skillBreakdown,
                'milestones' => $milestones,
                'best_score' => $bestScore,
                'streak' => $streakDays,
            ],
            'history' => [
                'sessions' => $historySessions,
            ],
        ]);
    }

    private function buildFlowSteps(Collection $sessions): array
    {
        $latest = $sessions->first();

        $steps = collect([
            [
                'id' => 'select',
                'title' => 'Select case',
                'description' => 'Browse the library and choose your next scenario.',
                'status' => 'upcoming',
            ],
            [
                'id' => 'prepare',
                'title' => 'Prep & brief',
                'description' => 'Review primers, patient briefs, and reference material.',
                'status' => 'upcoming',
            ],
            [
                'id' => 'start',
                'title' => 'Run simulation',
                'description' => 'Engage with the patient actor and manage the encounter.',
                'status' => 'upcoming',
            ],
            [
                'id' => 'complete',
                'title' => 'Review results',
                'description' => 'Assess feedback, rationalize decisions, and plan next steps.',
                'status' => 'upcoming',
            ],
        ]);

        if (! $latest) {
            return $steps->map(function (array $step, int $index) {
                $step['status'] = $index === 0 ? 'current' : 'upcoming';

                return $step;
            })->toArray();
        }

        $currentIndex = match ($latest->status) {
            'completed' => 3,
            'in_progress' => 2,
            default => 1,
        };

        return $steps->map(function (array $step, int $index) use ($currentIndex) {
            if ($index < $currentIndex) {
                $step['status'] = 'completed';
            } elseif ($index === $currentIndex) {
                $step['status'] = 'current';
            } else {
                $step['status'] = 'upcoming';
            }

            return $step;
        })->toArray();
    }

    private function buildCaseStatuses(Collection $cases, Collection $sessions): array
    {
        $sessionsByCase = $sessions->groupBy('osce_case_id');

        $statusById = [];

        foreach ($cases as $case) {
            $caseSessions = $sessionsByCase->get($case->id, collect());

            $status = $case->is_active ? 'available' : 'locked';

            $inProgress = $caseSessions->firstWhere('status', 'in_progress');
            $completedCount = $caseSessions->where('status', 'completed')->count();

            if ($inProgress) {
                $status = 'in_progress';
            } elseif ($completedCount > 0) {
                $status = 'completed';
            }

            $statusById[$case->id] = [
                'status' => $status,
                'attempts' => $caseSessions->count(),
                'completed_attempts' => $completedCount,
            ];
        }

        return $statusById;
    }

    private function calculateStreakDays(Collection $completedSessions): int
    {
        if ($completedSessions->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $previousDay = null;

        $ordered = $completedSessions->sortByDesc(fn (OsceSession $session) => $session->completed_at ?? $session->started_at ?? $session->created_at);

        foreach ($ordered as $session) {
            $timestamp = $session->completed_at ?? $session->started_at ?? $session->created_at;

            if (! $timestamp instanceof Carbon) {
                continue;
            }

            $currentDay = $timestamp->copy()->startOfDay();

            if ($previousDay === null) {
                $streak = 1;
                $previousDay = $currentDay;
                continue;
            }

            $diff = $previousDay->diffInDays($currentDay);

            if ($diff === 0) {
                continue;
            }

            if ($diff === 1) {
                $streak++;
                $previousDay = $currentDay;
                continue;
            }

            break;
        }

        return $streak;
    }
}
