<?php

namespace App\Http\Controllers;

use App\Models\McqTest;
use App\Models\OsceCase;
use App\Models\Post;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'osce_cases_active' => OsceCase::where('is_active', true)->count(),
            'forum_posts' => Post::count(),
            'users_total' => User::count(),
            'mcq_available' => McqTest::count(),
        ];

        $welcome = [
            'title' => 'Welcome back 👋',
            'message' => 'Train clinical skills, ace your MCQs, and learn together.',
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'welcome' => $welcome,
        ]);
    }
}
