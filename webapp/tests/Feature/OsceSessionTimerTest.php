<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\OsceCase;
use App\Models\OsceSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;

class OsceSessionTimerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected OsceCase $osceCase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->osceCase = OsceCase::create([
            'title' => 'Test Case',
            'description' => 'Test Description',
            'difficulty' => 'medium',
            'duration_minutes' => 25,
            'stations' => ['Station 1'],
            'scenario' => 'Test Scenario',
            'objectives' => 'Test Objectives',
            'checklist' => ['Item 1', 'Item 2'],
            'is_active' => true,
        ]);
    }

    /** @test */
    public function timer_calculates_elapsed_time_correctly()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(10), // Started 10 minutes ago
        ]);

        $this->assertEquals(600, $session->elapsed_seconds); // 10 minutes = 600 seconds
    }

    /** @test */
    public function timer_calculates_remaining_time_correctly()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(10), // Started 10 minutes ago
        ]);

        $expectedRemaining = (25 * 60) - 600; // 25 minutes - 10 minutes = 15 minutes
        $this->assertEquals($expectedRemaining, $session->remaining_seconds);
    }

    /** @test */
    public function timer_persists_after_page_refresh()
    {
        $startTime = now()->subMinutes(15);
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => $startTime,
        ]);

        // Simulate page refresh by calling timer endpoint again
        $response = $this->actingAs($this->user)
            ->getJson("/api/osce/sessions/{$session->id}/timer");

        $response->assertOk();
        
        $data = $response->json();
        $expectedRemaining = (25 * 60) - (15 * 60); // 25 - 15 = 10 minutes
        
        // Allow 1 second tolerance for test execution time
        $this->assertGreaterThanOrEqual($expectedRemaining - 1, $data['remaining_seconds']);
        $this->assertLessThanOrEqual($expectedRemaining + 1, $data['remaining_seconds']);
    }

    /** @test */
    public function timer_auto_completes_expired_sessions()
    {
        $startTime = now()->subMinutes(30); // Started 30 minutes ago (beyond 25 minute limit)
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => $startTime,
        ]);

        // Call timer endpoint which should auto-complete expired session
        $response = $this->actingAs($this->user)
            ->getJson("/api/osce/sessions/{$session->id}/timer");

        $response->assertOk();
        
        // Session should be marked as completed
        $session->refresh();
        $this->assertEquals('completed', $session->status);
        $this->assertNotNull($session->completed_at);
        
        $data = $response->json();
        $this->assertEquals('completed', $data['time_status']);
        $this->assertEquals(0, $data['remaining_seconds']);
    }

    /** @test */
    public function timer_prevents_started_at_modification()
    {
        $originalStartTime = now()->subMinutes(10);
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => $originalStartTime,
        ]);

        $sessionId = $session->id;
        
        // Attempt to modify started_at (this should be prevented)
        $session->started_at = now();
        $session->save();
        
        // Refresh from database
        $session->refresh();
        
        // started_at should remain unchanged
        $this->assertEquals($originalStartTime->timestamp, $session->started_at->timestamp);
    }

    /** @test */
    public function timer_handles_zero_duration_correctly()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(10),
        ]);

        // Set duration to 0
        $this->osceCase->update(['duration_minutes' => 0]);
        
        $this->assertEquals(0, $session->remaining_seconds);
        $this->assertTrue($session->is_expired);
    }

    /** @test */
    public function timer_handles_extended_time_correctly()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(10),
            'time_extended' => 5, // 5 minutes extension
        ]);

        // Total duration should be 25 + 5 = 30 minutes
        $this->assertEquals(30, $session->duration_minutes);
        
        // Remaining time should account for extension
        $expectedRemaining = (30 * 60) - (10 * 60); // 30 - 10 = 20 minutes
        $this->assertEquals($expectedRemaining, $session->remaining_seconds);
    }

    /** @test */
    public function timer_api_returns_correct_format()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/osce/sessions/{$session->id}/timer");

        $response->assertOk();
        
        $data = $response->json();
        
        $this->assertArrayHasKey('session_id', $data);
        $this->assertArrayHasKey('elapsed_seconds', $data);
        $this->assertArrayHasKey('remaining_seconds', $data);
        $this->assertArrayHasKey('duration_minutes', $data);
        $this->assertArrayHasKey('is_expired', $data);
        $this->assertArrayHasKey('time_status', $data);
        $this->assertArrayHasKey('formatted_time_remaining', $data);
        $this->assertArrayHasKey('progress_percentage', $data);
        $this->assertArrayHasKey('server_timestamp', $data);
        $this->assertArrayHasKey('started_at_timestamp', $data);
        
        // Verify data types
        $this->assertIsInt($data['elapsed_seconds']);
        $this->assertIsInt($data['remaining_seconds']);
        $this->assertIsInt($data['duration_minutes']);
        $this->assertIsBool($data['is_expired']);
        $this->assertIsString($data['time_status']);
    }

    /** @test */
    public function timer_handles_completed_sessions_correctly()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'completed',
            'started_at' => now()->subMinutes(30),
            'completed_at' => now()->subMinutes(5),
        ]);

        $this->assertEquals(0, $session->remaining_seconds);
        $this->assertEquals('completed', $session->time_status);
        $this->assertFalse($session->is_expired);
    }

    /** @test */
    public function timer_handles_edge_case_negative_elapsed_time()
    {
        // This shouldn't happen in normal operation, but let's test the safeguard
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->addMinutes(1), // Future time (shouldn't happen)
        ]);

        // Should handle gracefully and return 0 elapsed time
        $this->assertEquals(0, $session->elapsed_seconds);
        $this->assertEquals(25 * 60, $session->remaining_seconds); // Full duration
    }
}