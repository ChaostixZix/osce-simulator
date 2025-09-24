<?php

namespace App\Services;

use GuzzleHttp\Client;
use Firebase\JWT\JWT;
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
            $decoded = JWT::decode($token, new Key($this->getJwtSecret(), 'HS256'));
            
            // Check if token is expired
            if ($decoded->exp < time()) {
                return false;
            }

            return $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get JWT secret from Supabase
     */
    protected function getJwtSecret()
    {
        return Cache::remember('supabase_jwt_secret', 3600, function () {
            // For development, use a default secret
            // In production, you should get this from Supabase
            return config('services.supabase.jwt_secret', 'your-secret-key');
        });
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
}