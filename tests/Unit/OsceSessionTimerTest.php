<?php

namespace Tests\Unit;

use App\Models\OsceCase;
use App\Models\OsceSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OsceSessionTimerTest extends TestCase
{
    use RefreshDatabase;

    public function test_timer_uses_case_duration_and_original_start(): void
    {
        $this->freezeTime();
        $session = OsceSession::factory()->create([
            'osce_case_id' => OsceCase::factory()->create(['duration_minutes' => 25])->id,
            'started_at' => now(),
        ]);
        $this->assertSame(1500, $session->remaining_seconds);
        $this->travel(5)->minutes();
        $this->assertSame(300, $session->elapsed_seconds);
        $this->assertSame(1200, $session->remaining_seconds);
        $this->assertSame('active', $session->time_status);
    }

    public function test_time_continues_across_inactivity_and_refresh(): void
    {
        $this->freezeTime();
        $session = OsceSession::factory()->create([
            'osce_case_id' => OsceCase::factory()->create(['duration_minutes' => 25])->id,
            'started_at' => now()->subMinutes(5),
        ]);
        $started = $session->started_at->toISOString();
        $this->travel(2)->minutes();
        $session->refresh();
        $this->assertSame(1080, $session->remaining_seconds);
        $this->assertSame($started, $session->started_at->toISOString());
    }

    public function test_start_cannot_be_reset_after_persistence(): void
    {
        $this->freezeTime();
        $session = OsceSession::factory()->create(['started_at' => now()->subMinutes(5)]);
        $started = $session->started_at->toISOString();
        $session->started_at = now();
        $session->save();
        $this->assertSame($started, $session->fresh()->started_at->toISOString());
    }

    public function test_expiry_and_completion_do_not_return_negative_remaining_time(): void
    {
        $session = OsceSession::factory()->create([
            'osce_case_id' => OsceCase::factory()->create(['duration_minutes' => 25])->id,
            'started_at' => now()->subMinutes(30),
        ]);
        $this->assertSame(0, $session->remaining_seconds);
        $this->assertTrue($session->is_expired);
        $session->markAsCompleted();
        $this->assertSame(0, $session->remaining_seconds);
        $this->assertSame('completed', $session->time_status);
        $this->assertFalse($session->is_expired);
    }
}
