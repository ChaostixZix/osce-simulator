<?php

namespace Tests\Feature;

use App\Models\OsceCase;
use App\Models\OsceSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OsceSessionTimingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private OsceCase $osceCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->osceCase = OsceCase::factory()->create([
            'duration_minutes' => 15,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_calculates_elapsed_seconds_correctly()
    {
        // Create a session that started 5 minutes ago
        Carbon::setTestNow(Carbon::now());
        $startTime = Carbon::now()->subMinutes(5);

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => $startTime,
        ]);

        // Test elapsed seconds calculation
        $expectedElapsed = 5 * 60; // 5 minutes in seconds
        $this->assertEquals($expectedElapsed, $session->elapsed_seconds);
    }

    /** @test */
    public function it_calculates_remaining_seconds_correctly()
    {
        // Create a session that started 5 minutes ago with 15 minute duration
        Carbon::setTestNow(Carbon::now());
        $startTime = Carbon::now()->subMinutes(5);

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => $startTime,
        ]);

        // Test remaining seconds calculation
        $expectedRemaining = 10 * 60; // 10 minutes remaining
        $this->assertEquals($expectedRemaining, $session->remaining_seconds);
    }

    /** @test */
    public function remaining_time_decreases_as_time_passes()
    {
        Carbon::setTestNow(Carbon::now());
        $startTime = Carbon::now()->subMinutes(5);

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => $startTime,
        ]);

        $initialRemaining = $session->remaining_seconds;

        // Advance time by 2 minutes
        Carbon::setTestNow(Carbon::now()->addMinutes(2));
        $session = $session->fresh(); // Reload to get updated attributes

        $laterRemaining = $session->remaining_seconds;

        // Remaining time should decrease
        $this->assertLessThan($initialRemaining, $laterRemaining);
        $this->assertEquals(120, $initialRemaining - $laterRemaining); // 2 minutes difference
    }

    /** @test */
    public function session_expires_when_time_runs_out()
    {
        Carbon::setTestNow(Carbon::now());
        $startTime = Carbon::now()->subMinutes(20); // Started 20 minutes ago, case duration is 15 minutes

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => $startTime,
        ]);

        // Session should be expired
        $this->assertTrue($session->is_expired);
        $this->assertEquals('expired', $session->time_status);
        $this->assertEquals(0, $session->remaining_seconds);
    }

    /** @test */
    public function timer_endpoint_returns_correct_data()
    {
        Carbon::setTestNow(Carbon::now());
        $startTime = Carbon::now()->subMinutes(5);

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => $startTime,
        ]);

        $this->actingAs($this->user);

        $response = $this->getJson("/api/osce/sessions/{$session->id}/timer");

        $response->assertOk();
        $response->assertJsonStructure([
            'session_id',
            'elapsed_seconds',
            'remaining_seconds',
            'duration_minutes',
            'is_expired',
            'time_status',
            'formatted_time_remaining',
            'progress_percentage',
        ]);

        $data = $response->json();

        $this->assertEquals(15, $data['duration_minutes']);
        $this->assertEquals(300, $data['elapsed_seconds']); // 5 minutes
        $this->assertEquals(600, $data['remaining_seconds']); // 10 minutes remaining
        $this->assertEquals('active', $data['time_status']);
        $this->assertFalse($data['is_expired']);
    }

    /** @test */
    public function page_refresh_does_not_reset_timer()
    {
        Carbon::setTestNow(Carbon::now());
        $startTime = Carbon::now()->subMinutes(3);

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => $startTime,
        ]);

        $this->actingAs($this->user);

        // First request (simulating initial page load)
        $response1 = $this->getJson("/api/osce/sessions/{$session->id}/timer");
        $data1 = $response1->json();

        // Advance time by 1 minute
        Carbon::setTestNow(Carbon::now()->addMinutes(1));

        // Second request (simulating page refresh)
        $response2 = $this->getJson("/api/osce/sessions/{$session->id}/timer");
        $data2 = $response2->json();

        // Elapsed time should increase
        $this->assertGreaterThan($data1['elapsed_seconds'], $data2['elapsed_seconds']);

        // Remaining time should decrease
        $this->assertLessThan($data1['remaining_seconds'], $data2['remaining_seconds']);

        // The difference should be 60 seconds (1 minute)
        $this->assertEquals(60, $data2['elapsed_seconds'] - $data1['elapsed_seconds']);
        $this->assertEquals(60, $data1['remaining_seconds'] - $data2['remaining_seconds']);
    }

    /** @test */
    public function session_with_time_extension_calculates_correctly()
    {
        Carbon::setTestNow(Carbon::now());
        $startTime = Carbon::now()->subMinutes(5);

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => $startTime,
            'time_extended' => 5, // Extended by 5 minutes
        ]);

        // Total duration should be 20 minutes (15 + 5)
        $this->assertEquals(20, $session->duration_minutes);

        // With 5 minutes elapsed, should have 15 minutes remaining
        $this->assertEquals(15 * 60, $session->remaining_seconds);
    }

    /** @test */
    public function completed_session_shows_zero_remaining_time()
    {
        Carbon::setTestNow(Carbon::now());
        $startTime = Carbon::now()->subMinutes(10);

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'completed',
            'started_at' => $startTime,
            'completed_at' => Carbon::now()->subMinutes(2),
        ]);

        $this->assertEquals(0, $session->remaining_seconds);
        $this->assertEquals('completed', $session->time_status);
        $this->assertFalse($session->is_expired);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon test time
        parent::tearDown();
    }
}
