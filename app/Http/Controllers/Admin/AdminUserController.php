<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(): Response
    {
        $activeThreshold = now()->subMinutes(10)->timestamp;

        $activeSessions = DB::table('sessions')
            ->select('user_id', 'last_activity')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $activeThreshold)
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => (int) $rows->max('last_activity'));

        $users = User::orderBy('name')
            ->get(['id', 'name', 'email', 'is_admin', 'is_banned', 'created_at', 'updated_at'])
            ->map(function (User $user) use ($activeSessions) {
                $lastActivity = $activeSessions->get($user->id);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'is_banned' => $user->is_banned,
                    'is_active' => $lastActivity !== null,
                    'last_active_at' => $lastActivity ? Carbon::createFromTimestamp($lastActivity)->toIso8601String() : null,
                    'joined_at' => optional($user->created_at)->toIso8601String(),
                ];
            });

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function toggleAdminStatus(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot change your own admin status.');
        }

        $user->forceFill([
            'is_admin' => ! $user->is_admin,
        ])->save();

        return back()->with('success', 'User admin status updated.');
    }

    public function toggleBanStatus(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot ban your own account.');
        }

        $user->forceFill([
            'is_banned' => ! $user->is_banned,
        ])->save();

        return back()->with('success', 'User ban status updated.');
    }
}
