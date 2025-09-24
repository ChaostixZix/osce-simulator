<?php

namespace App\Http\Middleware;

use App\Services\SupabaseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SupabaseAuthenticate
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated via Laravel auth
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            return redirect('/login');
        }

        // Validate Supabase token if exists
        $accessToken = $request->session()->get('supabase_access_token');
        if ($accessToken) {
            $user = $this->validateSupabaseToken($accessToken);
            
            if (!$user) {
                // Try to refresh token
                $refreshToken = $request->session()->get('supabase_refresh_token');
                if ($refreshToken) {
                    $newTokens = $this->refreshSupabaseToken($refreshToken);
                    
                    if ($newTokens) {
                        $request->session()->put('supabase_access_token', $newTokens['access_token']);
                        $request->session()->put('supabase_refresh_token', $newTokens['refresh_token']);
                    } else {
                        // Token refresh failed, logout user
                        $this->logoutUser($request);
                        return redirect('/login');
                    }
                } else {
                    // No refresh token, logout user
                    $this->logoutUser($request);
                    return redirect('/login');
                }
            }
        }

        return $next($request);
    }

    /**
     * Validate Supabase access token
     */
    protected function validateSupabaseToken($accessToken)
    {
        try {
            // Get user info from Supabase
            $response = $this->supabase->getUser($accessToken);
            
            if (isset($response['id'])) {
                // Update user's last login time
                $user = Auth::user();
                if ($user && $user->supabase_id !== $response['id']) {
                    $user->update([
                        'supabase_id' => $response['id'],
                        'last_login_at' => now(),
                    ]);
                }
                
                return $response;
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Supabase token validation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Refresh Supabase token
     */
    protected function refreshSupabaseToken($refreshToken)
    {
        try {
            $response = $this->supabase->refreshToken($refreshToken);
            
            if (isset($response['access_token'])) {
                return [
                    'access_token' => $response['access_token'],
                    'refresh_token' => $response['refresh_token'] ?? $refreshToken,
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Supabase token refresh error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Logout user and clear session
     */
    protected function logoutUser(Request $request)
    {
        Auth::logout();
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}