<?php

namespace App\Services;

use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;

class SupabaseService
{
    protected $client;
    protected $url;
    protected $key;
    protected $serviceRoleKey;

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->key = config('services.supabase.key');
        $this->serviceRoleKey = config('services.supabase.service_role_key');

        $this->client = new Client([
            'base_uri' => $this->url,
            'headers' => [
                'apikey' => $this->key,
                'Authorization' => 'Bearer ' . $this->key,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Sign up a new user
     */
    public function signUp(array $credentials)
    {
        $requestId = uniqid('supabase_signup_', true);
        
        \Log::info('📡 SupabaseService: Starting signup request', [
            'request_id' => $requestId,
            'email_domain' => substr(strrchr($credentials['email'], "@"), 1),
            'endpoint' => '/auth/v1/signup',
            'timestamp' => now()->toISOString()
        ]);

        try {
            $response = $this->client->post('/auth/v1/signup', [
                'json' => [
                    'email' => $credentials['email'],
                    'password' => $credentials['password'],
                    'data' => $credentials['data'] ?? [],
                ],
                'timeout' => 30, // Add timeout
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody = (string) $response->getBody();
            $responseBody = json_decode($rawBody, true);
            
            \Log::info('✅ SupabaseService: Signup request completed', [
                'request_id' => $requestId,
                'status_code' => $statusCode,
                'has_user' => isset($responseBody['user']),
                'has_error' => isset($responseBody['error']),
                'response_keys' => array_keys($responseBody),
                'email_confirmed' => $responseBody['user']['email_confirmed_at'] ?? false,
                'user_id_present' => isset($responseBody['user']['id']),
                'identities_present' => isset($responseBody['user']['identities']),
                'timestamp' => now()->toISOString(),
                'raw_response_size' => strlen($rawBody),
            ]);

            // Log the complete response structure for debugging (but mask sensitive data)
            \Log::info('🔍 SupabaseService: Response structure debug', [
                'request_id' => $requestId,
                'response_structure' => $this->getResponseStructure($responseBody),
            ]);

            if (isset($responseBody['error'])) {
                \Log::error('❌ SupabaseService: API returned error', [
                    'request_id' => $requestId,
                    'error' => $responseBody['error'],
                    'status_code' => $statusCode,
                    'email_domain' => substr(strrchr($credentials['email'], "@"), 1),
                ]);
            }

            return $responseBody;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : 'unknown';
            $errorBody = $response ? (string) $response->getBody() : 'no response body';
            
            \Log::error('❌ SupabaseService: Guzzle exception during signup', [
                'request_id' => $requestId,
                'error_message' => $e->getMessage(),
                'status_code' => $statusCode,
                'error_body' => $errorBody,
                'email_domain' => substr(strrchr($credentials['email'], "@"), 1),
                'timestamp' => now()->toISOString()
            ]);
            
            throw $e;

        } catch (\Exception $e) {
            \Log::error('❌ SupabaseService: Unexpected error during signup', [
                'request_id' => $requestId,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'email_domain' => substr(strrchr($credentials['email'], "@"), 1),
                'timestamp' => now()->toISOString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Helper method to get response structure for logging (masks sensitive data)
     */
    private function getResponseStructure(array $data): array
    {
        $structure = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($key === 'user') {
                    // Mask user data but show structure
                    $structure[$key] = [
                        'id' => isset($value['id']) ? '[PRESENT]' : '[MISSING]',
                        'email' => isset($value['email']) ? '[PRESENT]' : '[MISSING]',
                        'email_confirmed_at' => isset($value['email_confirmed_at']) ? '[PRESENT]' : '[MISSING]',
                        'keys' => array_keys($value),
                    ];
                } else {
                    $structure[$key] = is_array($value) ? array_keys($value) : gettype($value);
                }
            } else {
                $structure[$key] = gettype($value);
            }
        }
        
        return $structure;
    }

    /**
     * Helper method to get signin response structure for logging (masks sensitive data)
     */
    private function getSigninResponseStructure(array $data): array
    {
        $structure = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($key === 'user') {
                    // Mask user data but show structure
                    $structure[$key] = [
                        'id' => isset($value['id']) ? '[PRESENT]' : '[MISSING]',
                        'email' => isset($value['email']) ? '[PRESENT]' : '[MISSING]',
                        'email_confirmed_at' => isset($value['email_confirmed_at']) ? '[PRESENT]' : '[MISSING]',
                        'keys' => array_keys($value),
                    ];
                } else {
                    $structure[$key] = is_array($value) ? array_keys($value) : gettype($value);
                }
            } else {
                // Mask sensitive tokens
                if (in_array($key, ['access_token', 'refresh_token'])) {
                    $structure[$key] = isset($value) ? '[PRESENT]' : '[MISSING]';
                } else {
                    $structure[$key] = gettype($value);
                }
            }
        }
        
        return $structure;
    }

    /**
     * Sign in with email and password
     */
    public function signInWithPassword(array $credentials)
    {
        $requestId = uniqid('supabase_signin_', true);
        
        \Log::info('🔐 SupabaseService: Starting signin request', [
            'request_id' => $requestId,
            'email_domain' => substr(strrchr($credentials['email'], "@"), 1),
            'endpoint' => '/auth/v1/token?grant_type=password',
            'timestamp' => now()->toISOString()
        ]);

        try {
            $response = $this->client->post('/auth/v1/token?grant_type=password', [
                'json' => [
                    'email' => $credentials['email'],
                    'password' => $credentials['password'],
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody = (string) $response->getBody();
            $responseBody = json_decode($rawBody, true);
            
            \Log::info('✅ SupabaseService: Signin request completed', [
                'request_id' => $requestId,
                'status_code' => $statusCode,
                'has_access_token' => isset($responseBody['access_token']),
                'has_refresh_token' => isset($responseBody['refresh_token']),
                'has_user' => isset($responseBody['user']),
                'has_error' => isset($responseBody['error']),
                'response_keys' => array_keys($responseBody),
                'expires_in' => $responseBody['expires_in'] ?? null,
                'timestamp' => now()->toISOString(),
                'raw_response_size' => strlen($rawBody),
            ]);

            // Log the response structure for debugging (but mask sensitive data)
            \Log::info('🔍 SupabaseService: Signin response structure debug', [
                'request_id' => $requestId,
                'response_structure' => $this->getSigninResponseStructure($responseBody),
            ]);

            if (isset($responseBody['error'])) {
                \Log::error('❌ SupabaseService: API returned error in signin', [
                    'request_id' => $requestId,
                    'error' => $responseBody['error'],
                    'error_description' => $responseBody['error_description'] ?? null,
                    'status_code' => $statusCode,
                    'email_domain' => substr(strrchr($credentials['email'], "@"), 1),
                ]);
            }

            return $responseBody;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : 'unknown';
            $errorBody = $response ? (string) $response->getBody() : 'no response body';
            
            \Log::error('❌ SupabaseService: Guzzle exception during signin', [
                'request_id' => $requestId,
                'error_message' => $e->getMessage(),
                'status_code' => $statusCode,
                'error_body' => $errorBody,
                'email_domain' => substr(strrchr($credentials['email'], "@"), 1),
                'timestamp' => now()->toISOString()
            ]);
            
            throw $e;

        } catch (\Exception $e) {
            \Log::error('❌ SupabaseService: Unexpected error during signin', [
                'request_id' => $requestId,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'email_domain' => substr(strrchr($credentials['email'], "@"), 1),
                'timestamp' => now()->toISOString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get user information
     */
    public function getUser($accessToken)
    {
        $response = $this->client->get('/auth/v1/user', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Refresh access token
     */
    public function refreshToken($refreshToken)
    {
        $response = $this->client->post('/auth/v1/token?grant_type=refresh_token', [
            'json' => [
                'refresh_token' => $refreshToken,
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Sign out user
     */
    public function signOut($accessToken)
    {
        $response = $this->client->post('/auth/v1/logout', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get OAuth provider URL
     */
    public function getOAuthUrl($provider, $redirectTo = null)
    {
        $params = [
            'provider' => $provider,
        ];

        if ($redirectTo) {
            $params['redirect_to'] = $redirectTo;
        }

        return $this->url . '/auth/v1/authorize?' . http_build_query($params);
    }

    /**
     * Exchange code for session
     */
    public function exchangeCodeForSession($code)
    {
        $response = $this->client->post('/auth/v1/token?grant_type=pkce', [
            'json' => [
                'code' => $code,
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Admin functions
     */
    public function adminCreateUser(array $userData)
    {
        $client = new Client([
            'base_uri' => $this->url,
            'headers' => [
                'apikey' => $this->serviceRoleKey,
                'Authorization' => 'Bearer ' . $this->serviceRoleKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        $response = $client->post('/auth/v1/admin/users', [
            'json' => $userData
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Verify JWT token
     */
    public function verifyToken($token)
    {
        try {
            // For Supabase tokens, we need to verify against the Supabase project
            $response = $this->client->get('/auth/v1/user', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ]
            ]);

            $user = json_decode($response->getBody(), true);
            
            if (isset($user['id']) && $user['aud'] === 'authenticated') {
                return $user;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    
    /**
     * Reset password for email
     */
    public function resetPasswordForEmail($email)
    {
        $response = $this->client->post('/auth/v1/recover', [
            'json' => [
                'email' => $email,
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Update user
     */
    public function updateUser($accessToken, array $attributes)
    {
        $response = $this->client->put('/auth/v1/user', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'json' => $attributes
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Update user password using service role key
     */
    public function updateUserPassword($email, $password)
    {
        $client = new Client([
            'base_uri' => $this->url,
            'headers' => [
                'apikey' => $this->serviceRoleKey,
                'Authorization' => 'Bearer ' . $this->serviceRoleKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        // First get the user by email
        $userResponse = $client->get('/auth/v1/admin/users', [
            'query' => [
                'email' => $email,
            ]
        ]);

        $users = json_decode($userResponse->getBody(), true);
        
        if (empty($users['users'])) {
            return ['error' => ['message' => 'User not found']];
        }

        $userId = $users['users'][0]['id'];

        // Update user password
        $response = $client->put('/auth/v1/admin/users/' . $userId, [
            'json' => [
                'password' => $password,
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Create user with service role key
     */
    public function createUser(array $userData)
    {
        $client = new Client([
            'base_uri' => $this->url,
            'headers' => [
                'apikey' => $this->serviceRoleKey,
                'Authorization' => 'Bearer ' . $this->serviceRoleKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        $response = $client->post('/auth/v1/admin/users', [
            'json' => array_merge([
                'email_confirm' => true,
            ], $userData)
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Delete user
     */
    public function deleteUser($userId)
    {
        $client = new Client([
            'base_uri' => $this->url,
            'headers' => [
                'apikey' => $this->serviceRoleKey,
                'Authorization' => 'Bearer ' . $this->serviceRoleKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        $response = $client->delete('/auth/v1/admin/users/' . $userId);
        return json_decode($response->getBody(), true);
    }

    /**
     * Invite user by email
     */
    public function inviteUserByEmail($email, $data = [])
    {
        $client = new Client([
            'base_uri' => $this->url,
            'headers' => [
                'apikey' => $this->serviceRoleKey,
                'Authorization' => 'Bearer ' . $this->serviceRoleKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        $response = $client->post('/auth/v1/invite', [
            'json' => array_merge([
                'email' => $email,
            ], $data)
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get user by ID
     */
    public function getUserById($userId)
    {
        $client = new Client([
            'base_uri' => $this->url,
            'headers' => [
                'apikey' => $this->serviceRoleKey,
                'Authorization' => 'Bearer ' . $this->serviceRoleKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        $response = $client->get('/auth/v1/admin/users/' . $userId);
        return json_decode($response->getBody(), true);
    }

    /**
     * List users
     */
    public function listUsers($page = 1, $perPage = 50)
    {
        $client = new Client([
            'base_uri' => $this->url,
            'headers' => [
                'apikey' => $this->serviceRoleKey,
                'Authorization' => 'Bearer ' . $this->serviceRoleKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        $response = $client->get('/auth/v1/admin/users', [
            'query' => [
                'page' => $page,
                'per_page' => $perPage,
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Generate magic link
     */
    public function generateMagicLink($email, $redirectTo = null)
    {
        $data = [
            'email' => $email,
        ];

        if ($redirectTo) {
            $data['data'] = ['redirect_to' => $redirectTo];
        }

        $response = $this->client->post('/auth/v1/magiclink', [
            'json' => $data
        ]);

        return json_decode($response->getBody(), true);
    }
}