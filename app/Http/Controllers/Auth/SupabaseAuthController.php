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
        $requestId = uniqid('login_', true);
        $clientIp = $request->ip();
        
        Log::info('🔐 Login attempt started', [
            'request_id' => $requestId,
            'ip' => $clientIp,
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ]);

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        Log::info('✅ Login validation passed', [
            'request_id' => $requestId,
            'email_domain' => substr(strrchr($credentials['email'], "@"), 1),
        ]);

        try {
            // Sign in with Supabase
            $response = $this->supabase->signInWithPassword($credentials);
            
            // Check for Supabase API errors
            // Supabase returns errors at root level: code, error_code, msg
            if (isset($response['error']) || isset($response['error_code'])) {
                $errorData = [
                    'code' => $response['code'] ?? 400,
                    'error_code' => $response['error_code'] ?? 'unknown',
                    'message' => $response['msg'] ?? $response['error'] ?? 'Unknown error',
                ];
                
                Log::error('❌ Supabase API error during login', [
                    'request_id' => $requestId,
                    'error_code' => $errorData['error_code'],
                    'error_message' => $errorData['message'],
                    'full_response' => $response,
                    'email_domain' => substr(strrchr($credentials['email'], "@"), 1),
                ]);
                
                $userMessage = $this->mapSupabaseLoginErrorToUserMessage($errorData);
                
                // Return JSON response for Inertia
                if ($request->wantsJson()) {
                    return response()->json([
                        'errors' => ['email' => $userMessage],
                        'debug_id' => $requestId
                    ], 422);
                }
                
                return back()->withErrors([
                    'email' => $userMessage
                ])->withInput($request->except('password'));
            }

            // Get user data and session
            $userData = $response['user'];
            $session = $response['session'] ?? $response; // Supabase returns session data at root level

            // Verify we have the required data
            if (!isset($userData['id']) || !isset($session['access_token'])) {
                Log::error('❌ Invalid Supabase response - missing required data', [
                    'request_id' => $requestId,
                    'has_user_id' => isset($userData['id']),
                    'has_access_token' => isset($session['access_token']),
                    'response_keys' => array_keys($response),
                ]);
                
                throw new \Exception('Invalid response from authentication service');
            }

            Log::info('✅ Supabase authentication successful', [
                'request_id' => $requestId,
                'supabase_user_id' => $userData['id'],
                'user_email' => $userData['email'] ?? 'unknown',
                'email_confirmed' => $userData['email_confirmed_at'] ?? false,
            ]);

            // Find or create user
            $user = User::where('email', $userData['email'])->first();
            
            if (!$user) {
                Log::info('👤 Creating new local user', [
                    'request_id' => $requestId,
                    'supabase_id' => $userData['id'],
                    'email' => $userData['email'],
                ]);
                
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
                
                Log::info('🔄 Updating existing user', [
                    'request_id' => $requestId,
                    'user_id' => $user->id,
                    'supabase_id' => $userData['id'],
                ]);
                
                $user->update($updateData);
            }

            // Login user
            Auth::login($user);
            
            // Store Supabase session
            $request->session()->put('supabase_access_token', $session['access_token']);
            $request->session()->put('supabase_refresh_token', $session['refresh_token']);
            
            Log::info('🎉 Login completed successfully', [
                'request_id' => $requestId,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            
            // Return redirect response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'redirect' => $request->session()->pull('url.intended', '/dashboard'),
                    'debug_id' => $requestId
                ]);
            }
            
            return redirect()->intended('/dashboard');
            
        } catch (\GuzzleHttp\Exception\RequestException $ge) {
            $response = $ge->getResponse();
            $errorBody = $response ? (string) $response->getBody() : null;
            $statusCode = $response ? $response->getStatusCode() : 'unknown';
            $decodedErrorBody = $errorBody ? json_decode($errorBody, true) : [];
            
            Log::error('❌ Guzzle/Network error during login', [
                'request_id' => $requestId,
                'error_message' => $ge->getMessage(),
                'status_code' => $statusCode,
                'response_body' => $errorBody,
                'email_domain' => substr(strrchr($credentials['email'], "@"), 1),
            ]);
            
            $userMessage = $this->mapHttpErrorToUserMessage($statusCode, $decodedErrorBody);
            
            // Return JSON response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'errors' => ['email' => $userMessage],
                    'debug_id' => $requestId
                ], 422);
            }
            
            return back()->withErrors([
                'email' => $userMessage
            ])->withInput($request->except('password'));
            
        } catch (\Exception $e) {
            Log::error('❌ Unexpected login error', [
                'request_id' => $requestId,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'email_domain' => substr(strrchr($credentials['email'], "@"), 1),
            ]);
            
            // Return JSON response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'errors' => ['email' => 'Login failed due to a server error. Please try again later.'],
                    'debug_id' => $requestId
                ], 422);
            }
            
            return back()->withErrors([
                'email' => 'Login failed due to a server error. Please try again later.'
            ])->withInput($request->except('password'));
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
        // Enhanced logging for debugging
        $requestId = uniqid('reg_', true);
        $clientIp = $request->ip();
        
        Log::info('🚀 Registration attempt started', [
            'request_id' => $requestId,
            'ip' => $clientIp,
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Validation step
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            Log::info('✅ Validation passed', [
                'request_id' => $requestId,
                'email_domain' => substr(strrchr($validated['email'], "@"), 1),
                'name_length' => strlen($validated['name']),
                'password_length' => strlen($validated['password']),
            ]);

            // Check Supabase configuration
            $supabaseUrl = config('supabase.url');
            $supabaseKey = config('supabase.key');
            
            if (!$supabaseUrl || !$supabaseKey) {
                Log::error('❌ Supabase configuration missing', [
                    'request_id' => $requestId,
                    'has_url' => !empty($supabaseUrl),
                    'has_key' => !empty($supabaseKey),
                ]);
                throw new \Exception('Supabase configuration is incomplete');
            }

            Log::info('📡 Calling Supabase API', [
                'request_id' => $requestId,
                'supabase_url' => $supabaseUrl,
                'endpoint' => '/auth/v1/signup'
            ]);

            // Call Supabase signup
            $response = $this->supabase->signUp([
                'email' => $validated['email'],
                'password' => $validated['password'],
                'data' => [
                    'name' => $validated['name']
                ]
            ]);

            if (isset($response['error'])) {
                $errorData = $response['error'];
                Log::error('❌ Supabase API error', [
                    'request_id' => $requestId,
                    'error_code' => $errorData['code'] ?? 'unknown',
                    'error_message' => $errorData['message'] ?? 'Unknown error',
                    'full_error' => $errorData,
                    'supabase_status' => $response['status'] ?? 'unknown',
                    'email_domain' => substr(strrchr($validated['email'], "@"), 1),
                ]);
                
                // Map specific Supabase errors to user-friendly messages
                $userMessage = $this->mapSupabaseErrorToUserMessage($errorData);
                
                // Return JSON response for Inertia
                if ($request->wantsJson()) {
                    return response()->json([
                        'errors' => ['email' => $userMessage],
                        'debug_id' => $requestId // For debugging
                    ], 422);
                }
                
                return back()->withErrors([
                    'email' => $userMessage
                ])->withInput($request->except('password', 'password_confirmation'));
            }

            // Check for rate limiting in the response (this happens at the HTTP level, not in the error object)
            if (isset($response['code']) && $response['code'] === 429) {
                Log::warning('⏰ Supabase rate limit hit', [
                    'request_id' => $requestId,
                    'error_code' => $response['code'] ?? 'unknown',
                    'error_message' => $response['msg'] ?? 'Rate limit exceeded',
                    'email_domain' => substr(strrchr($validated['email'], "@"), 1),
                ]);
                
                $userMessage = $response['msg'] ?? 'Too many registration attempts. Please wait a minute before trying again.';
                
                // Return JSON response for Inertia
                if ($request->wantsJson()) {
                    return response()->json([
                        'errors' => ['email' => $userMessage],
                        'debug_id' => $requestId,
                        'rate_limited' => true
                    ], 429);
                }
                
                return back()->withErrors([
                    'email' => $userMessage
                ])->withInput($request->except('password', 'password_confirmation'));
            }

            // Supabase returns user data at root level, not nested under 'user' key
          // Check if we have a valid Supabase user response
          if (!isset($response['id']) || empty($response['id'])) {
                Log::error('❌ Invalid Supabase response - missing user ID', [
                    'request_id' => $requestId,
                    'response_structure' => array_keys($response),
                    'has_id' => isset($response['id']),
                    'has_email' => isset($response['email']),
                ]);
                
                throw new \Exception('Invalid response from Supabase: user ID not found');
            }

            $supabaseUserId = $response['id'];
            $userEmail = $response['email'] ?? null;
            $userName = $response['user_metadata']['name'] ?? $validated['name'];

            Log::info('✅ Supabase signup successful', [
                'request_id' => $requestId,
                'supabase_user_id' => $supabaseUserId,
                'user_email' => $userEmail,
                'email_confirmed' => $response['email_confirmed_at'] ?? false,
                'response_keys' => array_keys($response),
            ]);

            // Create local user
            try {
                $user = User::create([
                    'name' => $userName,
                    'email' => $validated['email'],
                    'supabase_id' => $supabaseUserId,
                    'provider' => 'email',
                    'avatar' => null, // Explicitly set avatar to null since it's nullable now
                    'is_migrated' => true,
                ]);

                Log::info('✅ Local user created successfully', [
                    'request_id' => $requestId,
                    'local_user_id' => $user->id,
                    'supabase_id' => $user->supabase_id,
                ]);

            } catch (\Exception $dbError) {
                Log::error('❌ Failed to create local user', [
                    'request_id' => $requestId,
                    'error' => $dbError->getMessage(),
                    'supabase_user_id' => $supabaseUserId,
                ]);
                
                // Attempt to clean up Supabase user if local creation fails
                try {
                    // Note: This would require additional Supabase admin functionality
                    Log::warning('⚠️  Local user creation failed - manual cleanup may be needed', [
                        'request_id' => $requestId,
                        'email' => $validated['email'],
                    ]);
                } catch (\Exception $cleanupError) {
                    Log::error('❌ Cleanup failed', [
                        'request_id' => $requestId,
                        'cleanup_error' => $cleanupError->getMessage(),
                    ]);
                }
                
                throw new \Exception('Failed to create local user account');
            }

            Log::info('🎉 Registration completed successfully', [
                'request_id' => $requestId,
                'user_id' => $user->id,
                'email' => $validated['email'],
            ]);

            // Return JSON response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'redirect' => '/login',
                    'success' => 'Registration successful! Please check your email to verify your account.',
                    'debug_id' => $requestId
                ]);
            }

            return redirect('/login')->with('success', 'Registration successful! Please check your email to verify your account.');
            
        } catch (\Illuminate\Validation\ValidationException $ve) {
            Log::warning('❌ Validation failed', [
                'request_id' => $requestId,
                'errors' => $ve->errors(),
                'input' => [
                    'name' => $request->input('name') ? 'present' : 'missing',
                    'email' => $request->input('email') ? 'present' : 'missing',
                    'password' => $request->input('password') ? 'present' : 'missing',
                    'password_confirmation' => $request->input('password_confirmation') ? 'present' : 'missing',
                ]
            ]);
            
            // Return JSON response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'errors' => $ve->errors(),
                    'debug_id' => $requestId
                ], 422);
            }
            
            return back()->withErrors($ve->errors())->withInput($request->except('password', 'password_confirmation'));
            
        } catch (\GuzzleHttp\Exception\RequestException $ge) {
            $response = $ge->getResponse();
            $errorBody = $response ? (string) $response->getBody() : null;
            $statusCode = $response ? $response->getStatusCode() : 'unknown';
            $decodedErrorBody = $errorBody ? json_decode($errorBody, true) : [];
            
            Log::error('❌ Guzzle/Network error', [
                'request_id' => $requestId,
                'error_message' => $ge->getMessage(),
                'status_code' => $statusCode,
                'response_body' => $errorBody,
                'email_domain' => substr(strrchr($request->input('email'), "@"), 1),
            ]);
            
            $userMessage = $this->mapHttpErrorToUserMessage($statusCode, $decodedErrorBody);
            
            // Return JSON response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'errors' => ['email' => $userMessage],
                    'debug_id' => $requestId
                ], 422);
            }
            
            return back()->withErrors([
                'email' => $userMessage
            ])->withInput($request->except('password', 'password_confirmation'));
            
        } catch (\Exception $e) {
            Log::error('❌ Unexpected registration error', [
                'request_id' => $requestId,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'email_domain' => substr(strrchr($request->input('email'), "@"), 1),
            ]);
            
            // Return JSON response for Inertia
            if ($request->wantsJson()) {
                return response()->json([
                    'errors' => ['email' => 'Registration failed due to a server error. Please try again later.'],
                    'debug_id' => $requestId
                ], 422);
            }
            
            return back()->withErrors([
                'email' => 'Registration failed due to a server error. Please try again later.'
            ])->withInput($request->except('password', 'password_confirmation'));
        }
    }

    /**
     * Map Supabase error codes to user-friendly messages
     */
    private function mapSupabaseErrorToUserMessage(array $error): string
    {
        $message = $error['message'] ?? 'Registration failed.';
        
        // Common Supabase error patterns
        if (str_contains($message, 'already registered')) {
            return 'This email address is already registered. Please use a different email or try logging in.';
        }
        
        if (str_contains($message, 'password') && str_contains($message, 'weak')) {
            return 'Password is too weak. Please use a stronger password with at least 8 characters.';
        }
        
        if (str_contains($message, 'invalid email')) {
            return 'Please enter a valid email address.';
        }
        
        if (str_contains($message, 'rate limit')) {
            return 'Too many registration attempts. Please wait a few minutes before trying again.';
        }
        
        if (str_contains($message, 'network') || str_contains($message, 'timeout')) {
            return 'Network error occurred. Please check your connection and try again.';
        }
        
        return $message;
    }

    /**
     * Map HTTP status codes to user-friendly messages
     */
    private function mapHttpErrorToUserMessage($statusCode, array $errorBody = []): string
    {
        switch ($statusCode) {
            case 400:
                return 'Invalid request data. Please check your information and try again.';
            case 401:
                return 'Authentication error. Please check your Supabase configuration.';
            case 403:
                return 'Access denied. Please contact support.';
            case 404:
                return 'Service not found. Please check your Supabase configuration.';
            case 422:
                return 'Invalid data provided. Please check your information.';
            case 429:
                // Extract specific rate limit message if available
                if (isset($errorBody['msg'])) {
                    if (str_contains($errorBody['msg'], 'seconds')) {
                        // Extract the number of seconds from the message
                        if (preg_match('/(\d+)\s+seconds?/', $errorBody['msg'], $matches)) {
                            $waitTime = $matches[1];
                            return "Too many registration attempts. Please wait {$waitTime} seconds before trying again.";
                        }
                    }
                    return 'Too many registration attempts. Please wait a few minutes before trying again.';
                }
                return 'Too many registration attempts. Please wait a minute before trying again.';
            case 500:
            case 502:
            case 503:
            case 504:
                return 'Server error occurred. Please try again later.';
            default:
                return 'Network error occurred. Please check your connection and try again.';
        }
    }

    /**
     * Map Supabase login error codes to user-friendly messages
     */
    private function mapSupabaseLoginErrorToUserMessage(array $error): string
    {
        $errorCode = $error['error_code'] ?? null;
        $message = $error['message'] ?? 'Login failed.';
        
        // Handle case where message is an array
        if (is_array($message)) {
            $message = $message['message'] ?? json_encode($message);
        }
        
        // Check specific error codes first
        switch ($errorCode) {
            case 'invalid_credentials':
                return 'These credentials do not match our records. Please check your email and password.';
                
            case 'email_not_confirmed':
                return 'Please verify your email address before logging in. Check your inbox for the verification link.';
                
            case 'user_not_found':
                return 'No account found with this email address. Please check your email or register for a new account.';
                
            case 'rate_limit_exceeded':
            case 'too_many_requests':
                return 'Too many login attempts. Please wait a few minutes before trying again.';
                
            case 'user_disabled':
            case 'user_banned':
                return 'This account has been disabled. Please contact support for assistance.';
                
            default:
                // Fall back to message parsing
                if (str_contains($message, 'Invalid login credentials')) {
                    return 'These credentials do not match our records. Please check your email and password.';
                }
                
                if (str_contains($message, 'Email not confirmed')) {
                    return 'Please verify your email address before logging in. Check your inbox for the verification link.';
                }
                
                if (str_contains($message, 'password')) {
                    return 'Invalid password. Please check your password and try again.';
                }
                
                if (str_contains($message, 'rate limit') || str_contains($message, 'too many requests')) {
                    return 'Too many login attempts. Please wait a few minutes before trying again.';
                }
                
                if (str_contains($message, 'disabled') || str_contains($message, 'banned')) {
                    return 'This account has been disabled. Please contact support for assistance.';
                }
                
                if (str_contains($message, 'network') || str_contains($message, 'timeout')) {
                    return 'Network error occurred. Please check your connection and try again.';
                }
                
                if (str_contains($message, 'configuration') || str_contains($message, 'misconfigured')) {
                    return 'Authentication service is temporarily unavailable. Please try again later.';
                }
                
                return 'Login failed. Please check your credentials and try again.';
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