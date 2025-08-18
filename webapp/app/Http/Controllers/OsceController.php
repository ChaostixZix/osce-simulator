<?php

namespace App\Http\Controllers;

use App\Models\OsceCase;
use App\Models\OsceSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OsceController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        
        // Get all active OSCE cases
        $cases = OsceCase::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get user's recent sessions
        $userSessions = OsceSession::with('osceCase')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return Inertia::render('Osce', [
            'cases' => $cases,
            'userSessions' => $userSessions,
            'user' => $user
        ]);
    }

    public function showChat(OsceSession $session): Response
    {
        $user = auth()->user();
        
        // Ensure the session belongs to the authenticated user
        if ($session->user_id !== $user->id) {
            abort(403, 'Unauthorized access to session');
        }
        
        // Load the session with case information
        $session->load('osceCase');
        
        return Inertia::render('OsceChat', [
            'session' => $session,
            'user' => $user
        ]);
    }

    public function getCases()
    {
        $cases = OsceCase::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($cases);
    }

    public function getUserSessions()
    {
        $user = auth()->user();
        
        $sessions = OsceSession::with('osceCase')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($sessions);
    }

    public function startSession(Request $request)
    {
        $request->validate([
            'osce_case_id' => 'required|exists:osce_cases,id'
        ]);

        $user = auth()->user();
        
        // Check if user already has an active session for this case
        $existingSession = OsceSession::where('user_id', $user->id)
            ->where('osce_case_id', $request->osce_case_id)
            ->where('status', 'in_progress')
            ->first();
            
        if ($existingSession) {
            return response()->json([
                'message' => 'You already have an active session for this case',
                'session' => $existingSession
            ], 400);
        }

        $session = OsceSession::create([
            'user_id' => $user->id,
            'osce_case_id' => $request->osce_case_id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Session started successfully',
            'session' => $session->load('osceCase')
        ]);
    }
}
