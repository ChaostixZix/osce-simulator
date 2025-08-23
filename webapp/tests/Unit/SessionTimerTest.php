<?php

namespace Tests\Unit;

use App\Models\OsceCase;
use App\Models\OsceSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTimerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function elapsed_seconds_attribute_calculates_correctly()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $session = new OsceSession([
            'started_at' => Carbon::parse('2024-01-01 12:00:00')->subMinutes(7),
        ]);

        $this->assertEquals(420, $session->elapsed_seconds); // 7 minutes = 420 seconds
    }

    /** @test */
    public function remaining_seconds_attribute_calculates_correctly()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $user = User::factory()->create();
        $case = OsceCase::factory()->create(['duration_minutes' => 15]);

        $session = new OsceSession([
            'user_id' => $user->id,
            'osce_case_id' => $case->id,
            'status' => 'in_progress',
            'started_at' => Carbon::parse('2024-01-01 12:00:00')->subMinutes(5),
        ]);

        // Mock the relationship
        $session->setRelation('osceCase', $case);

        $expectedRemaining = (15 * 60) - (5 * 60); // 10 minutes remaining
        $this->assertEquals($expectedRemaining, $session->remaining_seconds);
    }

    /** @test */
    public function duration_minutes_includes_time_extension()
    {
        $user = User::factory()->create();
        $case = OsceCase::factory()->create(['duration_minutes' => 15]);

        $session = new OsceSession([
            'user_id' => $user->id,
            'osce_case_id' => $case->id,
            'status' => 'in_progress',
            'time_extended' => 10,
        ]);

        // Mock the relationship
        $session->setRelation('osceCase', $case);

        $this->assertEquals(25, $session->duration_minutes); // 15 + 10
    }

    /** @test */
    public function time_status_reflects_session_state_correctly()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $user = User::factory()->create();
        $case = OsceCase::factory()->create(['duration_minutes' => 15]);

        // Active session
        $activeSession = new OsceSession([
            'user_id' => $user->id,
            'osce_case_id' => $case->id,
            'status' => 'in_progress',
            'started_at' => Carbon::parse('2024-01-01 12:00:00')->subMinutes(5),
        ]);
        $activeSession->setRelation('osceCase', $case);

        $this->assertEquals('active', $activeSession->time_status);
        $this->assertFalse($activeSession->is_expired);

        // Expired session
        $expiredSession = new OsceSession([
            'user_id' => $user->id,
            'osce_case_id' => $case->id,
            'status' => 'in_progress',
            'started_at' => Carbon::parse('2024-01-01 12:00:00')->subMinutes(20),
        ]);
        $expiredSession->setRelation('osceCase', $case);

        $this->assertEquals('expired', $expiredSession->time_status);
        $this->assertTrue($expiredSession->is_expired);

        // Completed session
        $completedSession = new OsceSession([
            'user_id' => $user->id,
            'osce_case_id' => $case->id,
            'status' => 'completed',
            'started_at' => Carbon::parse('2024-01-01 12:00:00')->subMinutes(10),
            'completed_at' => Carbon::parse('2024-01-01 12:00:00')->subMinutes(2),
        ]);
        $completedSession->setRelation('osceCase', $case);

        $this->assertEquals('completed', $completedSession->time_status);
        $this->assertFalse($completedSession->is_expired);
    }

    /** @test */
    public function timer_api_endpoint_preserves_time_across_requests()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => Carbon::parse('2024-01-01 12:00:00')->subMinutes(3),
        ]);

        $this->actingAs($this->user);

        // First request
        $response1 = $this->getJson("/api/osce/sessions/{$session->id}/timer");
        $data1 = $response1->json();

        // Simulate 2 minutes passing
        Carbon::setTestNow(Carbon::now()->addMinutes(2));

        // Second request (simulating page refresh)
        $response2 = $this->getJson("/api/osce/sessions/{$session->id}/timer");
        $data2 = $response2->json();

        // Verify time progressed correctly
        $this->assertEquals(180, $data1['elapsed_seconds']); // 3 minutes initially
        $this->assertEquals(300, $data2['elapsed_seconds']); // 5 minutes after refresh

        $this->assertEquals(720, $data1['remaining_seconds']); // 12 minutes remaining initially
        $this->assertEquals(600, $data2['remaining_seconds']); // 10 minutes remaining after refresh

        // Time difference should be exactly 2 minutes
        $this->assertEquals(120, $data2['elapsed_seconds'] - $data1['elapsed_seconds']);
        $this->assertEquals(120, $data1['remaining_seconds'] - $data2['remaining_seconds']);
    }

    /** @test */
    public function database_started_at_is_not_modified_on_subsequent_requests()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => Carbon::parse('2024-01-01 12:00:00')->subMinutes(5),
        ]);

        $originalStartedAt = $session->started_at;

        $this->actingAs($this->user);

        // Make multiple requests to timer endpoint
        $this->getJson("/api/osce/sessions/{$session->id}/timer");

        Carbon::setTestNow(Carbon::now()->addMinutes(1));
        $this->getJson("/api/osce/sessions/{$session->id}/timer");

        Carbon::setTestNow(Carbon::now()->addMinutes(1));
        $this->getJson("/api/osce/sessions/{$session->id}/timer");

        // Verify started_at hasn't changed
        $session->refresh();
        $this->assertEquals($originalStartedAt->toDateTimeString(), $session->started_at->toDateTimeString());
    }

    /** @test */
    public function time_calculation_is_consistent_across_multiple_refreshes()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => Carbon::parse('2024-01-01 12:00:00')->subMinutes(2),
        ]);

        $this->actingAs($this->user);

        // Collect timer data over multiple requests with time advancement
        $timerData = [];

        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson("/api/osce/sessions/{$session->id}/timer");
            $timerData[] = $response->json();

            // Advance time by 30 seconds
            Carbon::setTestNow(Carbon::now()->addSeconds(30));
        }

        // Verify time progresses consistently
        for ($i = 1; $i < count($timerData); $i++) {
            $prev = $timerData[$i - 1];
            $curr = $timerData[$i];

            // Elapsed should increase by 30 seconds
            $this->assertEquals(30, $curr['elapsed_seconds'] - $prev['elapsed_seconds']);

            // Remaining should decrease by 30 seconds
            $this->assertEquals(30, $prev['remaining_seconds'] - $curr['remaining_seconds']);

            // Total duration should remain constant
            $this->assertEquals($prev['duration_minutes'], $curr['duration_minutes']);
        }
    }

    /** @test */
    public function progress_percentage_increases_as_time_passes()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => Carbon::parse('2024-01-01 12:00:00')->subMinutes(0), // Just started
        ]);

        $this->actingAs($this->user);

        // At start: 0% progress
        $response1 = $this->getJson("/api/osce/sessions/{$session->id}/timer");
        $this->assertEquals(0.0, $response1->json('progress_percentage'));

        // After 7.5 minutes: 50% progress
        Carbon::setTestNow(Carbon::now()->addMinutes(7.5));
        $response2 = $this->getJson("/api/osce/sessions/{$session->id}/timer");
        $this->assertEquals(50.0, $response2->json('progress_percentage'));

        // After 15 minutes: 100% progress
        Carbon::setTestNow(Carbon::now()->addMinutes(7.5));
        $response3 = $this->getJson("/api/osce/sessions/{$session->id}/timer");
        $this->assertEquals(100.0, $response3->json('progress_percentage'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon test time
        parent::tearDown();
    }

    private function setupTestCase(): void
    {
        $this->user = User::factory()->create();
        $this->osceCase = OsceCase::factory()->create([
            'duration_minutes' => 15,
            'is_active' => true,
        ]);
    }
}
