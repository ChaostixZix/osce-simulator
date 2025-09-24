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
        $response = $this->client->post('/auth/v1/signup', [
            'json' => [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
                'data' => $credentials['data'] ?? [],
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Sign in with email and password
     */
    public function signInWithPassword(array $credentials)
    {
        $response = $this->client->post('/auth/v1/token?grant_type=password', [
            'json' => [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
            ]
        ]);

        return json_decode($response->getBody(), true);
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