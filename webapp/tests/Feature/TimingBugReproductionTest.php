<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\OsceCase;
use App\Models\OsceSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class TimingBugReproductionTest extends TestCase
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
            'is_active' => true
        ]);
    }

    /** @test */
    public function reproduces_timer_count_up_bug_on_page_refresh()
    {
        // Start with a fixed time
        Carbon::setTestNow('2024-01-01 12:00:00');
        
        // Create session that started 3 minutes ago
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => Carbon::parse('2024-01-01 11:57:00'), // 3 minutes ago
        ]);

        $this->actingAs($this->user);
        
        // Initial timer request
        $response1 = $this->getJson("/api/osce/sessions/{$session->id}/timer");
        $data1 = $response1->json();
        
        $this->assertEquals(180, $data1['elapsed_seconds']); // 3 minutes elapsed
        $this->assertEquals(720, $data1['remaining_seconds']); // 12 minutes remaining
        
        // Store the original started_at
        $originalStartedAt = $session->fresh()->started_at;
        
        // Advance time by 2 minutes
        Carbon::setTestNow('2024-01-01 12:02:00');
        
        // Second timer request (simulating page refresh after 2 minutes)
        $response2 = $this->getJson("/api/osce/sessions/{$session->id}/timer");
        $data2 = $response2->json();
        
        // Verify started_at hasn't been modified
        $currentStartedAt = $session->fresh()->started_at;
        $this->assertEquals(
            $originalStartedAt->toDateTimeString(), 
            $currentStartedAt->toDateTimeString(),
            'started_at should not be modified on timer requests'
        );
        
        // Verify timing progressed correctly
        $this->assertEquals(300, $data2['elapsed_seconds']); // 5 minutes total elapsed
        $this->assertEquals(600, $data2['remaining_seconds']); // 10 minutes remaining
        
        // Time difference should be exactly 2 minutes
        $expectedElapsedDiff = 120; // 2 minutes
        $actualElapsedDiff = $data2['elapsed_seconds'] - $data1['elapsed_seconds'];
        $actualRemainingDiff = $data1['remaining_seconds'] - $data2['remaining_seconds'];
        
        $this->assertEquals($expectedElapsedDiff, $actualElapsedDiff, 'Elapsed time should increase by 2 minutes');
        $this->assertEquals($expectedElapsedDiff, $actualRemainingDiff, 'Remaining time should decrease by 2 minutes');
        
        // Ensure time is counting DOWN, not UP
        $this->assertLessThan($data1['remaining_seconds'], $data2['remaining_seconds'], 'Remaining time should DECREASE, not increase');
        $this->assertGreaterThan($data1['elapsed_seconds'], $data2['elapsed_seconds'], 'Elapsed time should INCREASE');
    }

    /** @test */
    public function detects_if_started_at_is_being_modified()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');
        
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => Carbon::parse('2024-01-01 11:55:00'), // 5 minutes ago
        ]);

        $this->actingAs($this->user);
        
        $originalStartedAt = $session->started_at;
        
        // Make multiple timer requests
        for ($i = 0; $i < 5; $i++) {
            $this->getJson("/api/osce/sessions/{$session->id}/timer");
            
            // Check if started_at changed
            $session->refresh();
            $this->assertEquals(
                $originalStartedAt->toDateTimeString(),
                $session->started_at->toDateTimeString(),
                "started_at was modified after request #{$i}"
            );
            
            // Advance time slightly
            Carbon::setTestNow(Carbon::now()->addSeconds(30));
        }
    }

    /** @test */
    public function reproduces_exact_user_reported_issue()
    {
        // Test scenario: User reports that refreshing adds minutes instead of counting down
        Carbon::setTestNow('2024-01-01 12:00:00');
        
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => Carbon::parse('2024-01-01 11:58:00'), // 2 minutes ago
        ]);

        $this->actingAs($this->user);
        
        // Get initial state
        $response1 = $this->getJson("/api/osce/sessions/{$session->id}/timer");
        $initialData = $response1->json();
        
        // Simulate user refreshing page after some time
        Carbon::setTestNow('2024-01-01 12:03:00'); // 3 minutes later
        
        // Get state after refresh
        $response2 = $this->getJson("/api/osce/sessions/{$session->id}/timer");
        $afterRefreshData = $response2->json();
        
        // Debug output
        dump([
            'Initial' => [
                'elapsed' => $initialData['elapsed_seconds'],
                'remaining' => $initialData['remaining_seconds'],
                'formatted' => $initialData['formatted_time_remaining']
            ],
            'After Refresh' => [
                'elapsed' => $afterRefreshData['elapsed_seconds'],
                'remaining' => $afterRefreshData['remaining_seconds'],
                'formatted' => $afterRefreshData['formatted_time_remaining']
            ],
            'Expected Changes' => [
                'elapsed_should_increase_by' => 180, // 3 minutes
                'remaining_should_decrease_by' => 180, // 3 minutes
            ],
            'Actual Changes' => [
                'elapsed_changed_by' => $afterRefreshData['elapsed_seconds'] - $initialData['elapsed_seconds'],
                'remaining_changed_by' => $initialData['remaining_seconds'] - $afterRefreshData['remaining_seconds'],
            ]
        ]);
        
        // The bug: if remaining time INCREASES instead of decreases, this test will fail
        $this->assertGreaterThan(
            $initialData['elapsed_seconds'], 
            $afterRefreshData['elapsed_seconds'],
            'BUG: Elapsed time should INCREASE after refresh, not decrease or stay same'
        );
        
        $this->assertLessThan(
            $initialData['remaining_seconds'], 
            $afterRefreshData['remaining_seconds'],
            'BUG: Remaining time should DECREASE after refresh, not increase'
        );
    }

    /** @test */
    public function checks_for_timezone_consistency()
    {
        // Test with different timezone scenarios
        Carbon::setTestNow('2024-01-01 12:00:00');
        
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => Carbon::parse('2024-01-01 11:55:00'), // 5 minutes ago
        ]);

        // Test calculation using model methods
        $elapsed = $session->elapsed_seconds;
        $remaining = $session->remaining_seconds;
        
        // Manual calculation for verification
        $now = Carbon::now();
        $startedAt = $session->started_at;
        $manualElapsed = $now->diffInSeconds($startedAt);
        $totalDuration = $session->duration_minutes * 60;
        $manualRemaining = max(0, $totalDuration - $manualElapsed);
        
        $this->assertEquals($manualElapsed, $elapsed, 'Model elapsed calculation matches manual calculation');
        $this->assertEquals($manualRemaining, $remaining, 'Model remaining calculation matches manual calculation');
        
        // Verify elapsed + remaining = total duration
        $this->assertEquals($totalDuration, $elapsed + $remaining, 'Elapsed + Remaining should equal total duration');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon test time
        parent::tearDown();
    }
}