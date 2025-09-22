<?php

namespace Tests\Unit;

use App\Models\OsceCase;
use App\Models\OsceSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OsceSessionTimerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private OsceCase $osceCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->osceCase = OsceCase::factory()->create([
            'duration_minutes' => 25,
        ]);
    }

    /** @test */
    public function it_calculates_remaining_time_correctly_for_new_session()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        // Should have 25 minutes (1500 seconds) remaining for new session
        $this->assertEquals(1500, $session->remaining_seconds);
        $this->assertEquals(25, $session->duration_minutes);
        $this->assertFalse($session->is_expired);
        $this->assertEquals('active', $session->time_status);
    }

    /** @test */
    public function it_calculates_elapsed_time_correctly()
    {
        Carbon::setTestNow(now());

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5), // Started 5 minutes ago
        ]);

        // Should have elapsed 5 minutes (300 seconds)
        $this->assertEquals(300, $session->elapsed_seconds);
        $this->assertEquals(1200, $session->remaining_seconds); // 25min - 5min = 20min = 1200s
    }

    /** @test */
    public function it_detects_when_session_is_expired()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(30), // Started 30 minutes ago (beyond 25min limit)
        ]);

        $this->assertTrue($session->is_expired);
        $this->assertEquals('expired', $session->time_status);
        $this->assertEquals(0, $session->remaining_seconds);
    }

    /** @test */
    public function it_handles_timer_pause_and_resume()
    {
        Carbon::setTestNow(now());

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5), // Started 5 minutes ago
        ]);

        // Initially should have 20 minutes remaining
        $this->assertEquals(1200, $session->remaining_seconds);
        $this->assertFalse($session->isPaused());

        // Pause the timer
        $session->pauseTimer();
        $session = $session->fresh();

        $this->assertTrue($session->isPaused());
        $this->assertEquals(1200, $session->current_remaining_seconds);
        $this->assertNotNull($session->paused_at);

        // Simulate 2 minutes passing while paused
        Carbon::setTestNow(now()->addMinutes(2));

        // Time remaining should still be 1200 because it's paused
        $this->assertEquals(1200, $session->remaining_seconds);

        // Resume the timer
        $session->resumeTimer();
        $session = $session->fresh();

        $this->assertFalse($session->isPaused());
        $this->assertEquals(120, $session->total_paused_seconds); // 2 minutes paused
        $this->assertNotNull($session->resumed_at);
        $this->assertNull($session->current_remaining_seconds);

        // Now should have 20 minutes remaining (pause time excluded)
        $this->assertEquals(1200, $session->remaining_seconds);
    }

    /** @test */
    public function it_calculates_actual_elapsed_seconds_excluding_paused_time()
    {
        Carbon::setTestNow(now());

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(10), // Started 10 minutes ago
            'total_paused_seconds' => 180, // Was paused for 3 minutes total
        ]);

        // Actual elapsed time should be 10 minutes - 3 minutes = 7 minutes
        $this->assertEquals(420, $session->getActualElapsedSeconds()); // 7 * 60 = 420
        $this->assertEquals(1080, $session->remaining_seconds); // 1500 - 420 = 1080
    }

    /** @test */
    public function it_handles_multiple_pause_resume_cycles()
    {
        Carbon::setTestNow(now());

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
        ]);

        // First pause-resume cycle
        $session->pauseTimer();
        Carbon::setTestNow(now()->addMinutes(2)); // 2 minutes paused
        $session->resumeTimer();
        $session = $session->fresh();

        $this->assertEquals(120, $session->total_paused_seconds);

        // Second pause-resume cycle
        Carbon::setTestNow(now()->addMinutes(1)); // 1 minute active
        $session->pauseTimer();
        Carbon::setTestNow(now()->addMinutes(3)); // 3 minutes paused
        $session->resumeTimer();
        $session = $session->fresh();

        $this->assertEquals(300, $session->total_paused_seconds); // 2 + 3 = 5 minutes total paused

        // Total elapsed: 11 minutes real time - 5 minutes paused = 6 minutes active
        $this->assertEquals(360, $session->getActualElapsedSeconds());
        $this->assertEquals(1140, $session->remaining_seconds); // 1500 - 360
    }

    /** @test */
    public function it_prevents_multiple_pause_calls()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
        ]);

        $session->pauseTimer();
        $firstPauseTime = $session->fresh()->paused_at;

        // Try to pause again - should not change paused_at
        $session->pauseTimer();
        $secondPauseTime = $session->fresh()->paused_at;

        $this->assertEquals($firstPauseTime->format('Y-m-d H:i:s'), $secondPauseTime->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_prevents_resume_on_non_paused_session()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
        ]);

        // Try to resume non-paused session
        $session->resumeTimer();

        $this->assertNull($session->fresh()->resumed_at);
        $this->assertEquals(0, $session->fresh()->total_paused_seconds);
    }

    /** @test */
    public function completed_sessions_always_return_zero_remaining_time()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $this->assertEquals(0, $session->remaining_seconds);
        $this->assertEquals('completed', $session->time_status);
        $this->assertFalse($session->is_expired);
    }
}
