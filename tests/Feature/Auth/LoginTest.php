<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads()
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('auth/Login'));
    }

    public function test_user_can_login_with_valid_credentials()
    {
        // Create a test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_migrated' => true,
            'supabase_id' => 'test-supabase-id',
        ]);

        // Mock Supabase service
        $this->mock(SupabaseService::class, function ($mock) use ($user) {
            $mock->shouldReceive('signInWithPassword')
                ->andReturn([
                    'user' => [
                        'id' => $user->supabase_id,
                        'email' => $user->email,
                        'email_confirmed_at' => now(),
                        'user_metadata' => [
                            'full_name' => $user->name,
                        ],
                    ],
                    'access_token' => 'test-access-token',
                    'refresh_token' => 'test-refresh-token',
                ]);
        });

        $response = $this->post('/auth/supabase/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        // Mock Supabase service to return error
        $this->mock(SupabaseService::class, function ($mock) {
            $mock->shouldReceive('signInWithPassword')
                ->andReturn([
                    'error' => 'Invalid login credentials',
                    'msg' => 'Invalid login credentials',
                ]);
        });

        $response = $this->post('/auth/supabase/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_must_verify_email_before_login()
    {
        // Mock Supabase service to return email not confirmed error
        $this->mock(SupabaseService::class, function ($mock) {
            $mock->shouldReceive('signInWithPassword')
                ->andReturn([
                    'error' => 'Email not confirmed',
                    'msg' => 'Email not confirmed',
                ]);
        });

        $response = $this->post('/auth/supabase/login', [
            'email' => 'unverified@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_from_login()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/login');

        $response->assertRedirect('/dashboard');
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        session(['supabase_access_token' => 'test-token']);

        $response = $this->post('/auth/supabase/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
        $this->assertSessionMissing('supabase_access_token');
    }
}