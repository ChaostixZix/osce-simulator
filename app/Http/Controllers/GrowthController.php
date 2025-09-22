<?php

namespace App\Http\Controllers;

use App\Models\RefresherCase;
use App\Models\SpacedRepetitionCard;
use App\Services\LongitudinalGrowthService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GrowthController extends Controller
{
    public function __construct(
        private LongitudinalGrowthService $growthService
    ) {}

    public function dashboard(Request $request)
    {
        $user = $request->user();

        // Get growth metrics
        $streak = $this->growthService->getCurrentStreak($user);
        $milestones = $this->growthService->getMilestones($user);

        // Get due reviews and refreshers
        $dueCards = SpacedRepetitionCard::where('user_id', $user->id)
            ->due()
            ->with('osceCase')
            ->orderBy('next_review_date')
            ->limit(10)
            ->get();

        $refreshers = RefresherCase::where('user_id', $user->id)
            ->due()
            ->with('osceCase')
            ->orderBy('next_reminder_date')
            ->limit(5)
            ->get();

        // Get recent achievements
        $recentAchievements = $milestones
            ->where('achieved_at', '!=', null)
            ->where('achieved_at', '>=', now()->subDays(30))
            ->sortByDesc('achieved_at')
            ->take(5);

        // Calculate progress stats
        $stats = [
            'total_cards' => SpacedRepetitionCard::where('user_id', $user->id)->count(),
            'due_cards' => $dueCards->count(),
            'completed_refreshers' => RefresherCase::where('user_id', $user->id)
                ->whereNotNull('completed_at')
                ->count(),
            'pending_refreshers' => $refreshers->count(),
            'achievements_this_month' => $recentAchievements->count(),
            'current_streak' => $streak?->current_streak ?? 0,
            'longest_streak' => $streak?->longest_streak ?? 0
        ];

        return Inertia::render('Growth/Dashboard', [
            'streak' => $streak,
            'milestones' => $milestones->values(),
            'dueCards' => $dueCards,
            'refreshers' => $refreshers,
            'recentAchievements' => $recentAchievements->values(),
            'stats' => $stats
        ]);
    }

    public function reviewCard(Request $request, SpacedRepetitionCard $card)
    {
        $this->authorize('view', $card);

        return Inertia::render('Growth/ReviewCard', [
            'card' => $card->load('osceCase')
        ]);
    }

    public function submitCardReview(Request $request, SpacedRepetitionCard $card)
    {
        $this->authorize('update', $card);

        $request->validate([
            'quality' => 'required|integer|min:0|max:5'
        ]);

        $this->growthService->reviewCard($card, $request->integer('quality'));

        return redirect()->route('growth.dashboard')
            ->with('success', 'Card reviewed successfully!');
    }

    public function showRefresher(Request $request, RefresherCase $refresher)
    {
        $this->authorize('view', $refresher);

        return Inertia::render('Growth/RefresherCase', [
            'refresher' => $refresher->load('osceCase')
        ]);
    }

    public function submitRefresher(Request $request, RefresherCase $refresher)
    {
        $this->authorize('update', $refresher);

        $request->validate([
            'performance_score' => 'required|numeric|min:0|max:100',
            'responses' => 'sometimes|array'
        ]);

        $refresher->update([
            'completed_at' => now(),
            'performance_score' => $request->get('performance_score')
        ]);

        // Generate next refresher if performance is below threshold
        if ($request->get('performance_score') < 70) {
            $this->growthService->generateRefresherCase(
                $refresher->user,
                $refresher->osceCase,
                'skill_drill'
            );
        }

        return redirect()->route('growth.dashboard')
            ->with('success', 'Refresher completed successfully!');
    }

    public function milestones(Request $request)
    {
        $user = $request->user();
        $milestones = $this->growthService->getMilestones($user);

        $groupedMilestones = $milestones->groupBy('milestone_type');

        return Inertia::render('Growth/Milestones', [
            'milestones' => $groupedMilestones
        ]);
    }

    public function cards(Request $request)
    {
        $user = $request->user();

        // Get all cards with pagination
        $cards = SpacedRepetitionCard::where('user_id', $user->id)
            ->with('osceCase')
            ->orderBy('next_review_date')
            ->paginate(20);

        $dueCards = SpacedRepetitionCard::where('user_id', $user->id)
            ->due()
            ->count();

        return Inertia::render('Growth/Cards', [
            'cards' => $cards,
            'dueCount' => $dueCards
        ]);
    }

    public function analytics(Request $request)
    {
        $user = $request->user();

        // Get learning analytics
        $analytics = $this->growthService->getLearningAnalytics($user);

        return Inertia::render('Growth/Analytics', [
            'analytics' => $analytics
        ]);
    }
}