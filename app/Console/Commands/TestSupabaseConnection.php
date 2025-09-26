<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Log;

class TestSupabaseConnection extends Command
{
    protected $signature = 'supabase:test';
    protected $description = 'Test Supabase connection and authentication';

    public function handle(SupabaseService $supabase)
    {
        $this->info('Testing Supabase connection...');
        
        try {
            // Test 1: Check configuration
            $this->info('1. Checking Supabase configuration...');
            
            $url = config('services.supabase.url');
            $key = config('services.supabase.key');
            
            if (!$url || !$key) {
                $this->error('Supabase configuration is missing!');
                $this->error('URL: ' . ($url ? '[SET]' : '[MISSING]'));
                $this->error('Key: ' . ($key ? '[SET]' : '[MISSING]'));
                return 1;
            }
            
            $this->info('✓ Configuration found');
            $this->info('URL: ' . $url);
            $this->info('Key: ' . substr($key, 0, 10) . '...');
            
            // Test 2: Test API connectivity
            $this->info('2. Testing API connectivity...');
            
            $client = new \GuzzleHttp\Client([
                'base_uri' => $url,
                'headers' => [
                    'apikey' => $key,
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 10,
            ]);
            
            $response = $client->get('/auth/v1/settings');
            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                $this->info('✓ API connection successful');
            } else {
                $this->error('✗ API connection failed with status: ' . $statusCode);
                return 1;
            }
            
            // Test 3: Test signup (simulated)
            $this->info('3. Testing signup endpoint...');
            
            try {
                $testEmail = 'test@example.com';
                $testPassword = 'testpassword123';
                
                $response = $supabase->signUp([
                    'email' => $testEmail,
                    'password' => $testPassword,
                    'data' => ['test' => true],
                ]);
                
                if (isset($response['error'])) {
                    // This is expected - email already exists or other validation error
                    $this->info('✓ Signup endpoint reachable (expected error: ' . ($response['error']['message'] ?? 'Unknown') . ')');
                } else {
                    $this->info('✓ Signup test completed');
                }
            } catch (\Exception $e) {
                $this->error('✗ Signup test failed: ' . $e->getMessage());
                Log::error('Supabase signup test failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            
            // Test 4: Test signin (simulated)
            $this->info('4. Testing signin endpoint...');
            
            try {
                $response = $supabase->signInWithPassword([
                    'email' => $testEmail,
                    'password' => $testPassword,
                ]);
                
                if (isset($response['error'])) {
                    $this->info('✓ Signin endpoint reachable (expected error: ' . ($response['msg'] ?? 'Invalid credentials') . ')');
                } else {
                    $this->info('✓ Signin test completed successfully');
                }
            } catch (\Exception $e) {
                $this->error('✗ Signin test failed: ' . $e->getMessage());
                Log::error('Supabase signin test failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            
            $this->info('Supabase connection test completed!');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Connection test failed: ' . $e->getMessage());
            Log::error('Supabase connection test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}