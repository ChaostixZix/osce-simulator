<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;

class SupabaseAuthController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Show login page
     */
    public function showLoginForm()
    {
        $providers = config('supabase.providers', []);
        
        return Inertia::render('auth/Login', compact('providers'));
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            // Sign in with Supabase
            $response = $this->supabase->signInWithPassword($credentials);
            
            if (isset($response['error'])) {
                Log::error('Supabase login error: ' . json_encode($response['error']));
                
                // Return JSON response for Inertia
                if ($request->wantsJson()) {
                    return response()->json([
                        'errors' => ['email' => 'Invalid credentials or user not found.']
                    ], 422);
                }
                
                return back()->withErrors([
                    'email' => 'Invalid credentials or user not found.'
                ]);
            }

            // Get user data
            $userData = $response['user'];
            $session = $response['session'];

            // Find or create user
            $user = User::where('email', $userData['email'])->first();
            
            if (!$user) {
                $user = User::create([
                    'name' => $userData['user_metadata']['full_name'] ?? $userData['email'],
                    'email' => $userData['email'],
                    'supabase_id' => $userData['id'],
                    'provider' => 'email',
                    'avatar' => $userData['user_metadata']['avatar_url'] ?? null,
                    'is_migrated' => true,
                ]);
            } else {
                // Update existing user - mark as migrated if using Supabase
                $updateData = [
                    'supabase_id' => $userData['id'],
                    'provider' => 'email',
                    'last_login_at' => now(),
                    'is_migrated' => true,
                ];
                
                // Update name if available
                if (isset($userData['user_metadata']['full_name']) && (!$user->name || $user->name === $userData['email'])) {
                    $updateData['name'] = $userData['user_metadata']['full_name'];
                }
                
                $user->update($updateData);
            }

            // Login user
            Auth::login($user);
            
            // Store Supabase session
            $request->session()->put('supabase_access_token', $session['access_token']);
            $request->session()->put('supabase_refresh_token', $session['refresh_token']);
            
            // Return redirect response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'redirect' => $request->session()->pull('url.intended', '/dashboard')
                ]);
            }
            
            return redirect()->intended('/dashboard');
            
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            
            // Return JSON response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'errors' => ['email' => 'An error occurred during login. Please try again.']
                ], 422);
            }
            
            return back()->withErrors([
                'email' => 'An error occurred during login. Please try again.'
            ]);
        }
    }

    /**
     * Redirect to OAuth provider
     */
    public function redirectToProvider($provider)
    {
        $validProviders = array_keys(config('supabase.providers', []));
        
        if (!in_array($provider, $validProviders)) {
            abort(404);
        }

        $url = $this->supabase->getOAuthUrl($provider, config('supabase.redirect_url'));
        
        return redirect($url);
    }

    /**
     * Handle OAuth callback
     */
    public function handleCallback(Request $request)
    {
        try {
            if ($request->has('code')) {
                // Exchange code for session
                $response = $this->supabase->exchangeCodeForSession($request->code);
                
                if (isset($response['error'])) {
                    Log::error('OAuth callback error: ' . json_encode($response['error']));
                    return redirect('/login')->withErrors([
                        'email' => 'Authentication failed.'
                    ]);
                }

                // Get user info
                $accessToken = $response['access_token'];
                $userData = $this->supabase->getUser($accessToken);
                
                if (!isset($userData['id'])) {
                    return redirect('/login')->withErrors([
                        'email' => 'Failed to retrieve user information.'
                    ]);
                }

                // Find or create user
                $user = User::where('email', $userData['email'])->first();
                $provider = $userData['app_metadata']['provider'] ?? null;
                
                if (!$user) {
                    $user = User::create([
                        'name' => $userData['user_metadata']['full_name'] ?? $userData['email'],
                        'email' => $userData['email'],
                        'supabase_id' => $userData['id'],
                        'provider' => $provider,
                        'provider_id' => $userData['identities'][0]['id'] ?? null,
                        'avatar' => $userData['user_metadata']['avatar_url'] ?? null,
                        'is_migrated' => true,
                    ]);
                } else {
                    // Update existing user - mark as migrated if using Supabase
                    $updateData = [
                        'supabase_id' => $userData['id'],
                        'provider' => $provider,
                        'provider_id' => $userData['identities'][0]['id'] ?? null,
                        'last_login_at' => now(),
                        'is_migrated' => true,
                    ];
                    
                    // Update name if available
                    if (isset($userData['user_metadata']['full_name']) && (!$user->name || $user->name === $userData['email'])) {
                        $updateData['name'] = $userData['user_metadata']['full_name'];
                    }
                    
                    $user->update($updateData);
                }
                
                // Login user
                Auth::login($user);
                
                // Store session
                $request->session()->put('supabase_access_token', $accessToken);
                $request->session()->put('supabase_refresh_token', $response['refresh_token']);
                
                return redirect()->intended('/dashboard');
            }
            
            return redirect('/login')->withErrors([
                'email' => 'Authentication failed.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('OAuth callback error: ' . $e->getMessage());
            return redirect('/login')->withErrors([
                'email' => 'An error occurred during authentication.'
            ]);
        }
    }

    /**
     * Show registration form
     */
    public function showRegistrationForm()
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle registration
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $response = $this->supabase->signUp([
                'email' => $validated['email'],
                'password' => $validated['password'],
                'data' => [
                    'name' => $validated['name']
                ]
            ]);

            if (isset($response['error'])) {
                Log::error('Registration error: ' . json_encode($response['error']));
                
                // Return JSON response for Inertia
                if ($request->wantsJson()) {
                    return response()->json([
                        'errors' => ['email' => $response['error']['message'] ?? 'Registration failed.']
                    ], 422);
                }
                
                return back()->withErrors([
                    'email' => $response['error']['message'] ?? 'Registration failed.'
                ]);
            }

            // Create local user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'supabase_id' => $response['user']['id'],
                'provider' => 'email',
                'password' => bcrypt($validated['password']), // Keep for compatibility
                'is_migrated' => true,
            ]);

            // Return JSON response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'redirect' => '/login',
                    'success' => 'Registration successful! Please check your email to verify your account.'
                ]);
            }

            return redirect('/login')->with('success', 'Registration successful! Please check your email to verify your account.');
            
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            
            // Return JSON response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'errors' => ['email' => 'Registration failed. Please try again.']
                ], 422);
            }
            
            return back()->withErrors([
                'email' => 'Registration failed. Please try again.'
            ]);
        }
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        $accessToken = $request->session()->get('supabase_access_token');
        
        if ($accessToken) {
            try {
                $this->supabase->signOut($accessToken);
            } catch (\Exception $e) {
                Log::error('Supabase logout error: ' . $e->getMessage());
            }
        }

        Auth::logout();
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Return JSON response for Inertia
        if ($request->wantsJson()) {
            return response()->json([
                'redirect' => '/login'
            ]);
        }

        return redirect('/login');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPasswordForm()
    {
        return Inertia::render('auth/ForgotPassword');
    }

    /**
     * Handle forgot password request
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $response = $this->supabase->resetPasswordForEmail($request->email);
            
            if (isset($response['error'])) {
                Log::error('Password reset error: ' . json_encode($response['error']));
                
                // Return JSON response for Inertia
                if ($request->wantsJson()) {
                    return response()->json([
                        'errors' => ['email' => 'Failed to send password reset email.']
                    ], 422);
                }
                
                return back()->withErrors([
                    'email' => 'Failed to send password reset email.'
                ]);
            }

            // Return JSON response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => 'Password reset link has been sent to your email.'
                ]);
            }

            return back()->with('success', 'Password reset link has been sent to your email.');
            
        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage());
            
            // Return JSON response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'errors' => ['email' => 'Failed to send password reset email.']
                ], 422);
            }
            
            return back()->withErrors([
                'email' => 'Failed to send password reset email.'
            ]);
        }
    }

    /**
     * Show reset password form
     */
    public function showResetPasswordForm()
    {
        return Inertia::render('auth/ResetPassword');
    }

    /**
     * Handle reset password request
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $response = $this->supabase->updateUserPassword($validated['email'], $validated['password']);
            
            if (isset($response['error'])) {
                Log::error('Password update error: ' . json_encode($response['error']));
                
                // Return JSON response for Inertia
                if ($request->wantsJson()) {
                    return response()->json([
                        'errors' => ['email' => 'Failed to reset password.']
                    ], 422);
                }
                
                return back()->withErrors([
                    'email' => 'Failed to reset password.'
                ]);
            }

            // Update local user password
            $user = User::where('email', $validated['email'])->first();
            if ($user) {
                $user->update([
                    'password' => bcrypt($validated['password']),
                    'is_migrated' => true,
                ]);
            }

            // Return JSON response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => 'Password has been reset successfully.'
                ]);
            }

            return redirect('/login')->with('success', 'Password has been reset successfully.');
            
        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage());
            
            // Return JSON response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'errors' => ['email' => 'Failed to reset password.']
                ], 422);
            }
            
            return back()->withErrors([
                'email' => 'Failed to reset password.'
            ]);
        }
    }

    /**
     * Check migration status
     */
    public function checkMigrationStatus(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        return response()->json([
            'is_migrated' => $user->isMigrated(),
            'provider' => $user->getAuthProvider(),
            'supabase_id' => $user->supabase_id ? true : false,
        ]);
    }

    /**
     * Trigger user migration to Supabase
     */
    public function migrateToSupabase(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        if ($user->isMigrated()) {
            return response()->json(['message' => 'User already migrated'], 200);
        }
        
        try {
            // Create user in Supabase
            $password = Str::random(16); // Generate random password
            
            $response = $this->supabase->createUser([
                'email' => $user->email,
                'password' => $password,
                'email_confirm' => true,
                'user_metadata' => [
                    'full_name' => $user->name,
                ]
            ]);
            
            if (isset($response['error'])) {
                Log::error('User migration error: ' . json_encode($response['error']));
                return response()->json(['error' => 'Migration failed'], 500);
            }
            
            // Update local user
            $user->update([
                'supabase_id' => $response['id'],
                'is_migrated' => true,
            ]);
            
            return response()->json([
                'message' => 'Migration successful',
                'password' => $password, // Show this once for the user to note down
            ]);
            
        } catch (\Exception $e) {
            Log::error('Migration error: ' . $e->getMessage());
            return response()->json(['error' => 'Migration failed'], 500);
        }
    }

    /**
     * Handle magic link login
     */
    public function magicLinkLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $response = $this->supabase->generateMagicLink($request->email);
            
            if (isset($response['error'])) {
                Log::error('Magic link error: ' . json_encode($response['error']));
                return back()->withErrors([
                    'email' => 'Failed to send magic link.'
                ]);
            }

            return back()->with('success', 'Magic link has been sent to your email.');
            
        } catch (\Exception $e) {
            Log::error('Magic link error: ' . $e->getMessage());
            return back()->withErrors([
                'email' => 'Failed to send magic link.'
            ]);
        }
    }
}