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

        // Get the authenticated user
        $user = Auth::user();
        
        // Check if user is banned
        if ($user->isBanned()) {
            $this->logoutUser($request);
            return redirect('/login')->with('error', 'Your account has been banned.');
        }

        // All users now use Supabase authentication
        return $this->handleSupabaseAuth($request, $next);
    }

    /**
     * Handle Supabase authentication
     */
    protected function handleSupabaseAuth(Request $request, Closure $next)
    {
        $accessToken = $request->session()->get('supabase_access_token');
        
        if (!$accessToken) {
            // User should have Supabase token but doesn't
            $this->logoutUser($request);
            return redirect('/login')->with('error', 'Authentication session expired. Please login again.');
        }

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
                    return redirect('/login')->with('error', 'Session expired. Please login again.');
                }
            } else {
                // No refresh token, logout user
                $this->logoutUser($request);
                return redirect('/login')->with('error', 'Authentication failed. Please login again.');
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
            $response = $this->supabase->verifyToken($accessToken);
            
            if ($response && isset($response['id'])) {
                // Update user's last login time and sync data
                $user = Auth::user();
                if ($user) {
                    $updateData = [
                        'last_login_at' => now(),
                    ];
                    
                    // Update Supabase ID if different
                    if ($user->supabase_id !== $response['id']) {
                        $updateData['supabase_id'] = $response['id'];
                    }
                    
                    // Update other info if available
                    if (isset($response['email']) && $user->email !== $response['email']) {
                        $updateData['email'] = $response['email'];
                    }
                    
                    if (isset($response['user_metadata']['full_name']) && $user->name !== $response['user_metadata']['full_name']) {
                        $updateData['name'] = $response['user_metadata']['full_name'];
                    }
                    
                    $user->update($updateData);
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