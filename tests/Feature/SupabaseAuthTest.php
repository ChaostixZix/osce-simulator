<?php

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Auth;

test('user can login with email and password via Supabase', function () {
    // Create a test user
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
        'supabase_id' => 'test-supabase-id',
        'is_migrated' => true,
    ]);

    // Mock SupabaseService
    $this->mock(SupabaseService::class, function ($mock) use ($user) {
        $mock->shouldReceive('signInWithPassword')
            ->with(['email' => 'test@example.com', 'password' => 'password123'])
            ->andReturn([
                'user' => [
                    'id' => 'test-supabase-id',
                    'email' => 'test@example.com',
                    'user_metadata' => ['full_name' => $user->name],
                ],
                'session' => [
                    'access_token' => 'test-access-token',
                    'refresh_token' => 'test-refresh-token',
                ],
            ]);
    });

    // Attempt login
    $response = $this->post('/auth/supabase/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    // Assert successful login
    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
    $this->assertEquals('test-access-token', session('supabase_access_token'));
    $this->assertEquals('test-refresh-token', session('supabase_refresh_token'));
});

test('user cannot login with invalid credentials', function () {
    // Mock SupabaseService to return error
    $this->mock(SupabaseService::class, function ($mock) {
        $mock->shouldReceive('signInWithPassword')
            ->with(['email' => 'invalid@example.com', 'password' => 'wrongpassword'])
            ->andReturn(['error' => ['message' => 'Invalid login credentials']]);
    });

    // Attempt login with invalid credentials
    $response = $this->post('/auth/supabase/login', [
        'email' => 'invalid@example.com',
        'password' => 'wrongpassword',
    ]);

    // Assert login failed
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('user can register new account via Supabase', function () {
    // Mock SupabaseService
    $this->mock(SupabaseService::class, function ($mock) {
        $mock->shouldReceive('signUp')
            ->with([
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'data' => ['name' => 'New User'],
            ])
            ->andReturn([
                'user' => [
                    'id' => 'new-supabase-id',
                    'email' => 'newuser@example.com',
                ],
                'session' => null, // Email verification required
            ]);
    });

    // Attempt registration
    $response = $this->post('/auth/supabase/register', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    // Assert successful registration
    $response->assertRedirect('/login');
    $response->assertSessionHas('success', 'Registration successful! Please check your email to verify your account.');
    
    // Assert user was created in database
    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'name' => 'New User',
        'supabase_id' => 'new-supabase-id',
        'is_migrated' => true,
    ]);
});

test('user can logout via Supabase', function () {
    // Create and login a user
    $user = User::factory()->create();
    Auth::login($user);
    session(['supabase_access_token' => 'test-token']);

    // Mock SupabaseService
    $this->mock(SupabaseService::class, function ($mock) {
        $mock->shouldReceive('signOut')->with('test-token')->once();
    });

    // Logout
    $response = $this->post('/auth/supabase/logout');

    // Assert logout
    $response->assertRedirect('/login');
    $this->assertGuest();
    $this->assertNull(session('supabase_access_token'));
});

test('unauthenticated user cannot access protected routes', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('migrated user status can be checked', function () {
    // Create a migrated user
    $user = User::factory()->create([
        'is_migrated' => true,
        'supabase_id' => 'test-id',
    ]);

    // Login as user
    $this->actingAs($user);

    // Check migration status
    $response = $this->getJson('/auth/supabase/migration-status');

    $response->assertOk();
    $response->assertJson([
        'is_migrated' => true,
        'provider' => null,
        'supabase_id' => true,
    ]);
});

test('user can request password reset', function () {
    // Create a user
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    // Mock SupabaseService
    $this->mock(SupabaseService::class, function ($mock) {
        $mock->shouldReceive('resetPasswordForEmail')
            ->with('test@example.com')
            ->andReturn([]);
    });

    // Request password reset
    $response = $this->post('/auth/supabase/forgot-password', [
        'email' => 'test@example.com',
    ]);

    // Assert request was successful
    $response->assertSessionHas('success', 'Password reset link has been sent to your email.');
});

test('middleware validates supabase token for migrated users', function () {
    // Create a migrated user
    $user = User::factory()->create([
        'is_migrated' => true,
        'supabase_id' => 'test-id',
    ]);

    // Enable dual mode
    config(['auth.supabase_dual_mode' => true]);

    // Mock SupabaseService with invalid token
    $this->mock(SupabaseService::class, function ($mock) {
        $mock->shouldReceive('verifyToken')->andReturn(false);
    });

    // Set up session with invalid token
    $this->actingAs($user);
    session(['supabase_access_token' => 'invalid-token']);

    // Access protected route
    $response = $this->get('/dashboard');

    // Should redirect to login due to invalid token
    $response->assertRedirect('/login');
    $response->assertSessionHas('error');
});

test('oauth redirect works for valid provider', function () {
    // Mock SupabaseService
    $this->mock(SupabaseService::class, function ($mock) {
        $mock->shouldReceive('getOAuthUrl')
            ->with('google', config('services.supabase.redirect_url'))
            ->andReturn('https://supabase.com/auth/v1/authorize?provider=google');
    });

    // Request OAuth redirect
    $response = $this->get('/auth/supabase/oauth/google');

    // Should redirect to Supabase OAuth URL
    $response->assertRedirect('https://supabase.com/auth/v1/authorize?provider=google');
});

test('oauth fails for invalid provider', function () {
    // Request OAuth redirect with invalid provider
    $response = $this->get('/auth/supabase/oauth/invalid-provider');

    // Should return 404
    $response->assertStatus(404);
});