<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\OsceCase;
use App\Models\OsceSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class TimerResetPreventionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function prevents_started_at_modification_after_creation()
    {
        $user = User::factory()->create();
        $case = OsceCase::factory()->create(['duration_minutes' => 15]);

        Carbon::setTestNow('2024-01-01 12:00:00');
        
        $session = OsceSession::create([
            'user_id' => $user->id,
            'osce_case_id' => $case->id,
            'status' => 'in_progress',
        ]);
        
        // Set started_at explicitly (simulating the fixed creation process)
        $session->started_at = Carbon::parse('2024-01-01 11:55:00');
        $session->save();
        
        $originalStartedAt = $session->started_at;
        
        // Attempt to modify started_at (this should be prevented)
        $session->started_at = Carbon::parse('2024-01-01 12:00:00');
        $session->save();
        
        // Verify started_at was not modified
        $session->refresh();
        $this->assertEquals(
            $originalStartedAt->toDateTimeString(),
            $session->started_at->toDateTimeString(),
            'started_at should not be modifiable after creation'
        );
    }

    /** @test */
    public function started_at_can_be_set_during_initial_creation()
    {
        $user = User::factory()->create();
        $case = OsceCase::factory()->create(['duration_minutes' => 15]);

        $session = new OsceSession([
            'user_id' => $user->id,
            'osce_case_id' => $case->id,
            'status' => 'in_progress',
        ]);
        
        // This should work for new sessions
        $session->setStartedAt(Carbon::parse('2024-01-01 11:55:00'));
        $session->save();
        
        $this->assertNotNull($session->started_at);
        $this->assertEquals('2024-01-01 11:55:00', $session->started_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function mass_assignment_cannot_modify_started_at()
    {
        $user = User::factory()->create();
        $case = OsceCase::factory()->create(['duration_minutes' => 15]);

        $session = OsceSession::create([
            'user_id' => $user->id,
            'osce_case_id' => $case->id,
            'status' => 'in_progress',
        ]);
        
        $session->started_at = Carbon::parse('2024-01-01 11:55:00');
        $session->save();
        
        $originalStartedAt = $session->started_at;
        
        // Attempt mass assignment with started_at (should be ignored)
        $session->update([
            'score' => 85,
            'started_at' => Carbon::parse('2024-01-01 12:00:00'), // This should be ignored
        ]);
        
        // Verify score was updated but started_at was not
        $session->refresh();
        $this->assertEquals(85, $session->score);
        $this->assertEquals(
            $originalStartedAt->toDateTimeString(),
            $session->started_at->toDateTimeString(),
            'started_at should not be modifiable via mass assignment'
        );
    }
}