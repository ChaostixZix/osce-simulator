#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Create a Supabase service instance
$supabase = $app->make(SupabaseService::class);

echo "Testing Supabase Authentication\n";
echo "==============================\n\n";

// Test 1: Configuration
echo "1. Testing Configuration...\n";
$url = config('services.supabase.url');
$key = config('services.supabase.key');

if (!$url || !$key) {
    echo "❌ ERROR: Supabase configuration missing\n";
    echo "URL: " . ($url ? '[SET]' : '[MISSING]') . "\n";
    echo "Key: " . ($key ? '[SET]' : '[MISSING]') . "\n";
    exit(1);
}

echo "✓ Configuration found\n";
echo "URL: $url\n";
echo "Key: " . substr($key, 0, 10) . "...\n\n";

// Test 2: API Connection
echo "2. Testing API Connection...\n";
try {
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
        echo "✓ API connection successful\n";
    } else {
        echo "❌ API connection failed with status: $statusCode\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "❌ API connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Signin
echo "\n3. Testing Signin...\n";
try {
    $response = $supabase->signInWithPassword([
        'email' => 'nonexistent@example.com',
        'password' => 'wrongpassword',
    ]);
    
    if (isset($response['error'])) {
        echo "✓ Signin endpoint reachable (expected error)\n";
        echo "Error: " . ($response['msg'] ?? $response['error'] ?? 'Unknown error') . "\n";
    } else {
        echo "✓ Signin test completed\n";
    }
} catch (\Exception $e) {
    echo "❌ Signin test failed: " . $e->getMessage() . "\n";
}

// Test 4: Signup
echo "\n4. Testing Signup...\n";
try {
    $response = $supabase->signUp([
        'email' => 'test' . time() . '@example.com',
        'password' => 'password123',
        'data' => ['test' => true],
    ]);
    
    if (isset($response['error'])) {
        echo "✓ Signup endpoint reachable (response: " . ($response['error']['message'] ?? 'Unknown error') . ")\n";
    } else {
        echo "✓ Signup test completed\n";
        echo "User ID: " . ($response['id'] ?? 'N/A') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Signup test failed: " . $e->getMessage() . "\n";
}

echo "\nSupabase authentication test completed!\n";