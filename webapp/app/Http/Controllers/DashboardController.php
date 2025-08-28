<?php

namespace App\Http\Controllers;

use App\Models\OsceCase;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'osce_cases_active' => OsceCase::where('is_active', true)->count(),
            'users_total' => User::count(),
        ];

        $welcome = [
            'title' => 'Welcome back 👋',
            'message' => 'Practice clinical skills and track your OSCE progress.',
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'welcome' => $welcome,
        ]);
    }
}
