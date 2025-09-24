<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        
        return view('auth.login-supabase', compact('providers'));
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
                    'email' => $userData['email'],
                    'supabase_id' => $userData['id'],
                    'avatar' => $userData['user_metadata']['avatar_url'] ?? null,
                ]);
            } else {
                // Update existing user
                $user->update([
                    'supabase_id' => $userData['id'],
                    'last_login_at' => now(),
                ]);
            }

            // Login user
            Auth::login($user);
            
            // Store Supabase session
            $request->session()->put('supabase_access_token', $session['access_token']);
            $request->session()->put('supabase_refresh_token', $session['refresh_token']);
            
            return redirect()->intended('/dashboard');
            
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
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
                
                if (!$user) {
                    $user = User::create([
                        'email' => $userData['email'],
                        'supabase_id' => $userData['id'],
                        'provider' => $userData['app_metadata']['provider'] ?? null,
                        'provider_id' => $userData['identities'][0]['id'] ?? null,
                        'avatar' => $userData['user_metadata']['avatar_url'] ?? null,
                    ]);
                } else {
                    // Update existing user
                    $user->update([
                        'supabase_id' => $userData['id'],
                        'provider' => $userData['app_metadata']['provider'] ?? null,
                        'provider_id' => $userData['identities'][0]['id'] ?? null,
                        'last_login_at' => now(),
                    ]);
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
        return view('auth.register-supabase');
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
                return back()->withErrors([
                    'email' => $response['error']['message'] ?? 'Registration failed.'
                ]);
            }

            // Create local user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'supabase_id' => $response['user']['id'],
                'password' => bcrypt($validated['password']), // Keep for compatibility
            ]);

            return redirect('/login')->with('success', 'Registration successful! Please check your email to verify your account.');
            
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
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

        return redirect('/login');
    }

    /**
     * Show forgot password form
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password-supabase');
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
                return back()->withErrors([
                    'email' => 'Failed to send password reset email.'
                ]);
            }

            return back()->with('success', 'Password reset link has been sent to your email.');
            
        } catch (\Exception $e) {
            Log::error('Password reset error: ' . $e->getMessage());
            return back()->withErrors([
                'email' => 'Failed to send password reset email.'
            ]);
        }
    }
}