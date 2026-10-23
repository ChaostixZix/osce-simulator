<?php

namespace Tests\Feature;

use App\Models\OsceCase;
use App\Models\OsceSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OsceSessionTimerApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private OsceSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        $this->user = User::factory()->create();
        $this->session = OsceSession::factory()->create([
            'user_id' => $this->user->id,
            'osce_case_id' => OsceCase::factory()->create(['duration_minutes' => 25])->id,
            'started_at' => now()->subMinutes(5),
        ]);
        $this->actingAs($this->user);
    }

    public function test_timer_exposes_authoritative_elapsed_and_remaining_seconds(): void
    {
        $this->getJson("/api/osce/sessions/{$this->session->id}/timer")
            ->assertOk()->assertJson([
                'elapsed_seconds' => 300, 'remaining_seconds' => 1200,
                'duration_minutes' => 25, 'time_status' => 'active',
                'formatted_time_remaining' => '20:00', 'progress_percentage' => 20,
            ]);
    }

    public function test_navigation_does_not_pause_or_restart_a_station(): void
    {
        $started = $this->session->started_at->toISOString();
        $this->getJson("/api/osce/sessions/{$this->session->id}/timer")->assertOk();
        $this->travel(6)->minutes();
        $this->getJson("/api/osce/sessions/{$this->session->id}/timer")
            ->assertOk()->assertJson(['remaining_seconds' => 840]);
        $this->assertSame($started, $this->session->fresh()->started_at->toISOString());
        foreach (['pause', 'resume', 'auto-pause'] as $removedRoute) {
            $this->postJson("/api/osce/sessions/{$this->session->id}/{$removedRoute}")->assertNotFound();
        }
    }

    public function test_timer_is_owner_only(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson("/api/osce/sessions/{$this->session->id}/timer")->assertForbidden();
    }

    public function test_expiry_completes_encounter_without_assessing_an_unsubmitted_summary(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $this->travel(21)->minutes();
        $this->getJson("/api/osce/sessions/{$this->session->id}/timer")
            ->assertOk()->assertJson(['remaining_seconds' => 0, 'time_status' => 'completed']);
        $this->assertNotNull($this->session->fresh()->completed_at);
        \Illuminate\Support\Facades\Queue::assertNothingPushed();
    }
}
