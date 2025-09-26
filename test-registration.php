#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Registration through Laravel Controller\n";
echo "=============================================\n\n";

// Generate test data
$testEmail = 'register.test.' . time() . '@gmail.com';
$testPassword = 'Password123!';
$testName = 'Test User';

echo "Test Data:\n";
echo "Name: $testName\n";
echo "Email: $testEmail\n";
echo "Password: $testPassword\n\n";

// Clean up any existing user
$existingUser = User::where('email', $testEmail)->first();
if ($existingUser) {
    echo "Cleaning up existing user...\n";
    $existingUser->delete();
}

// Create registration request
$request = new Request();
$request->setMethod('POST');
$request->request->add([
    'name' => $testName,
    'email' => $testEmail,
    'password' => $testPassword,
    'password_confirmation' => $testPassword,
]);
$request->headers->set('Accept', 'application/json');
$request->headers->set('Content-Type', 'application/json');

// Set up the request for the controller
$app->instance('request', $request);

echo "1. Testing registration controller...\n";

try {
    $controller = $app->make('App\Http\Controllers\Auth\SupabaseAuthController');
    $response = $controller->register($request);
    
    echo "Response status: " . $response->status() . "\n";
    
    if ($response->status() === 302) {
        echo "✓ Registration successful! Redirecting to: " . $response->getTargetUrl() . "\n\n";
        
        // Check if user was created locally
        $user = User::where('email', $testEmail)->first();
        if ($user) {
            echo "2. Local user verification:\n";
            echo "✓ User found in local database\n";
            echo "   ID: {$user->id}\n";
            echo "   Name: {$user->name}\n";
            echo "   Email: {$user->email}\n";
            echo "   Supabase ID: {$user->supabase_id}\n";
            echo "   Is migrated: " . ($user->is_migrated ? 'Yes' : 'No') . "\n";
            echo "   Created at: {$user->created_at}\n\n";
        } else {
            echo "❌ User not found in local database\n\n";
        }
        
        // Test login flow
        echo "3. Testing login with new credentials...\n";
        
        $loginRequest = new Request();
        $loginRequest->setMethod('POST');
        $loginRequest->request->add([
            'email' => $testEmail,
            'password' => $testPassword,
        ]);
        $loginRequest->headers->set('Accept', 'application/json');
        $app->instance('request', $loginRequest);
        
        $loginResponse = $controller->login($loginRequest);
        
        if ($loginResponse->status() === 302) {
            echo "✓ Login successful! Redirecting to: " . $loginResponse->getTargetUrl() . "\n";
            echo "   (Note: You'll need to confirm email first before actual login)\n";
        } else {
            echo "❌ Login failed with status: " . $loginResponse->status() . "\n";
            
            if ($loginResponse->getSession()->has('errors')) {
                $errors = $loginResponse->getSession()->get('errors');
                echo "   Errors: " . json_encode($errors) . "\n";
            }
        }
        
    } else if ($response->status() === 422) {
        echo "❌ Registration failed with validation errors\n";
        $errors = $response->getData()->errors;
        foreach ($errors as $field => $messages) {
            echo "   $field: " . implode(', ', $messages) . "\n";
        }
    } else {
        echo "❌ Unexpected response status: " . $response->status() . "\n";
        echo "Response content: " . $response->getContent() . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Registration exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nRegistration test completed!\n";