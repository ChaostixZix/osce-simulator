#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\SupabaseService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Complete Authentication Flow\n";
echo "==================================\n\n";

// Create a Supabase service instance
$supabase = $app->make(SupabaseService::class);

// Generate a realistic test email
$testEmail = 'test.user.' . time() . '@gmail.com';
$testPassword = 'Password123!';

echo "Test Email: $testEmail\n";
echo "Test Password: $testPassword\n\n";

// Step 1: Check if user exists locally
echo "1. Checking local user database...\n";
$localUser = User::where('email', $testEmail)->first();
if ($localUser) {
    echo "Found existing local user, deleting for clean test...\n";
    $localUser->delete();
}
echo "✓ No existing local user found\n\n";

// Step 2: Test Signup
echo "2. Testing Supabase signup...\n";
try {
    $signupResponse = $supabase->signUp([
        'email' => $testEmail,
        'password' => $testPassword,
        'data' => [
            'full_name' => 'Test User',
            'test_user' => true
        ],
    ]);
    
    if (isset($signupResponse['error'])) {
        echo "❌ Signup failed: " . json_encode($signupResponse['error']) . "\n";
    } else {
        echo "✓ Signup successful!\n";
        echo "User ID: " . ($signupResponse['id'] ?? 'N/A') . "\n";
        echo "Email confirmed: " . (isset($signupResponse['email_confirmed_at']) ? 'Yes' : 'No') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Signup exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 3: Check if user was created locally
echo "3. Checking if user was created locally...\n";
$localUser = User::where('email', $testEmail)->first();
if ($localUser) {
    echo "✓ Local user found!\n";
    echo "ID: {$localUser->id}\n";
    echo "Email: {$localUser->email}\n";
    echo "Supabase ID: {$localUser->supabase_id}\n";
    echo "Is migrated: " . ($localUser->is_migrated ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Local user not found\n";
}

echo "\n";

// Step 4: Test Login
echo "4. Testing login...\n";
try {
    $loginResponse = $supabase->signInWithPassword([
        'email' => $testEmail,
        'password' => $testPassword,
    ]);
    
    if (isset($loginResponse['error'])) {
        echo "❌ Login failed: " . ($loginResponse['msg'] ?? $loginResponse['error']) . "\n";
    } else {
        echo "✓ Login successful!\n";
        echo "Access token: " . substr($loginResponse['access_token'] ?? 'N/A', 0, 20) . "...\n";
        echo "User ID: " . ($loginResponse['user']['id'] ?? 'N/A') . "\n";
        echo "Email confirmed: " . (isset($loginResponse['user']['email_confirmed_at']) ? 'Yes' : 'No') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Login exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 5: Test Laravel auth controller
echo "5. Testing Laravel auth controller flow...\n";
$request = Request::create('/auth/supabase/login', 'POST', [
    'email' => $testEmail,
    'password' => $testPassword,
]);

// Set up the request for the controller
$app->instance('request', $request);

try {
    $controller = $app->make('App\Http\Controllers\Auth\SupabaseAuthController');
    $response = $controller->login($request);
    
    if ($response->status() === 302) {
        echo "✓ Controller login successful!\n";
        echo "Redirecting to: " . $response->getTargetUrl() . "\n";
    } else {
        echo "❌ Controller login failed with status: " . $response->status() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Controller login exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nAuthentication test completed!\n";