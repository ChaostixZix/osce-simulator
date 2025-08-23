<?php

namespace Tests\Feature;

use App\Models\OsceCase;
use App\Models\OsceSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OsceSessionTimerApiTest extends TestCase
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

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_returns_timer_data_for_active_session()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
        ]);

        $response = $this->getJson("/api/osce/sessions/{$session->id}/timer");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'session_id',
                'elapsed_seconds',
                'remaining_seconds',
                'duration_minutes',
                'is_expired',
                'time_status',
                'is_paused',
                'formatted_time_remaining',
                'progress_percentage',
            ]);

        $data = $response->json();
        $this->assertEquals(25, $data['duration_minutes']);
        $this->assertEquals(1200, $data['remaining_seconds']); // 20 minutes remaining
        $this->assertEquals('active', $data['time_status']);
        $this->assertFalse($data['is_paused']);
        $this->assertFalse($data['is_expired']);
    }

    /** @test */
    public function it_auto_resumes_paused_session_when_accessing_timer()
    {
        Carbon::setTestNow(now());

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
            'paused_at' => now()->subMinutes(2), // Paused 2 minutes ago
            'current_remaining_seconds' => 1200,
        ]);

        $this->assertTrue($session->isPaused());

        $response = $this->getJson("/api/osce/sessions/{$session->id}/timer");

        $response->assertStatus(200);

        $session = $session->fresh();
        $this->assertFalse($session->isPaused());
        $this->assertEquals(120, $session->total_paused_seconds); // 2 minutes of pause time recorded
        $this->assertNotNull($session->resumed_at);
    }

    /** @test */
    public function it_pauses_session_timer()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
        ]);

        $response = $this->postJson("/api/osce/sessions/{$session->id}/pause");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Session paused',
                'is_paused' => true,
            ]);

        $session = $session->fresh();
        $this->assertTrue($session->isPaused());
        $this->assertNotNull($session->paused_at);
        $this->assertEquals(1200, $session->current_remaining_seconds);
    }

    /** @test */
    public function it_resumes_session_timer()
    {
        Carbon::setTestNow(now());

        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
            'paused_at' => now()->subMinutes(2),
            'current_remaining_seconds' => 1200,
        ]);

        Carbon::setTestNow(now()->addMinutes(1)); // 1 minute later

        $response = $this->postJson("/api/osce/sessions/{$session->id}/resume");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Session resumed',
                'is_paused' => false,
            ]);

        $session = $session->fresh();
        $this->assertFalse($session->isPaused());
        $this->assertEquals(180, $session->total_paused_seconds); // 3 minutes total pause
        $this->assertNotNull($session->resumed_at);
        $this->assertNull($session->current_remaining_seconds);
    }

    /** @test */
    public function it_auto_pauses_session_timer()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
        ]);

        $response = $this->postJson("/api/osce/sessions/{$session->id}/auto-pause");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Session auto-paused',
                'is_paused' => true,
            ]);

        $session = $session->fresh();
        $this->assertTrue($session->isPaused());
        $this->assertNotNull($session->paused_at);
    }

    /** @test */
    public function it_prevents_pause_on_already_paused_session()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
            'paused_at' => now()->subMinutes(2),
            'current_remaining_seconds' => 1200,
        ]);

        $originalPausedAt = $session->paused_at->format('Y-m-d H:i:s');

        $response = $this->postJson("/api/osce/sessions/{$session->id}/pause");

        $response->assertStatus(200);

        $session = $session->fresh();
        $this->assertEquals($originalPausedAt, $session->paused_at->format('Y-m-d H:i:s'));
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

        $response = $this->postJson("/api/osce/sessions/{$session->id}/resume");

        $response->assertStatus(200);

        $session = $session->fresh();
        $this->assertNull($session->resumed_at);
        $this->assertEquals(0, $session->total_paused_seconds);
    }

    /** @test */
    public function it_prevents_unauthorized_access_to_timer_endpoints()
    {
        $otherUser = User::factory()->create();
        $session = OsceSession::create([
            'user_id' => $otherUser->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
        ]);

        $this->getJson("/api/osce/sessions/{$session->id}/timer")->assertStatus(403);
        $this->postJson("/api/osce/sessions/{$session->id}/pause")->assertStatus(403);
        $this->postJson("/api/osce/sessions/{$session->id}/resume")->assertStatus(403);
        $this->postJson("/api/osce/sessions/{$session->id}/auto-pause")->assertStatus(403);
    }

    /** @test */
    public function it_marks_expired_sessions_as_completed_when_accessing_timer()
    {
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(30), // Started 30 minutes ago (expired)
        ]);

        $this->assertTrue($session->is_expired);
        $this->assertEquals('in_progress', $session->status);

        $response = $this->getJson("/api/osce/sessions/{$session->id}/timer");

        $response->assertStatus(200);

        $session = $session->fresh();
        $this->assertEquals('completed', $session->status);
        $this->assertNotNull($session->completed_at);
        $this->assertEquals('completed', $session->time_status);
    }

    /** @test */
    public function it_handles_timer_persistence_across_multiple_requests()
    {
        Carbon::setTestNow(now());

        // Create session and let some time pass
        $session = OsceSession::create([
            'user_id' => $this->user->id,
            'osce_case_id' => $this->osceCase->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(5),
        ]);

        // First request - should show 20 minutes remaining
        $response1 = $this->getJson("/api/osce/sessions/{$session->id}/timer");
        $response1->assertJson(['remaining_seconds' => 1200]);

        // Auto-pause (simulating page refresh)
        Carbon::setTestNow(now()->addMinutes(1));
        $this->postJson("/api/osce/sessions/{$session->id}/auto-pause");

        // Let time pass while paused
        Carbon::setTestNow(now()->addMinutes(5));

        // Second request - should auto-resume and still show ~19 minutes remaining
        // (only 1 minute should have counted, not the 5 minutes while paused)
        $response2 = $this->getJson("/api/osce/sessions/{$session->id}/timer");
        $data = $response2->json();

        $this->assertLessThanOrEqual(1140, $data['remaining_seconds']); // ~19 minutes or slightly less
        $this->assertGreaterThanOrEqual(1120, $data['remaining_seconds']); // Account for small timing differences
        $this->assertFalse($data['is_paused']);
        $this->assertEquals('active', $data['time_status']);
    }
}
