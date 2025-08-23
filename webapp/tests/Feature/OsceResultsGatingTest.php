<?php

use App\Models\OsceCase;
use App\Models\OsceSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeUserSession(string $status = 'in_progress'): array {
    $user = User::factory()->create();
    $case = OsceCase::factory()->create(['duration_minutes' => 15]);

    $session = OsceSession::create([
        'user_id' => $user->id,
        'osce_case_id' => $case->id,
        'status' => $status,
        'started_at' => now(),
    ]);

    return [$user, $case, $session];
}

test('blocks results before rationalization completion', function () {
    [$user, $case, $session] = makeUserSession('in_progress');

    $this->actingAs($user);

    $resp = $this->get(route('osce.results.show', $session));
    $resp->assertRedirect(route('osce.rationalization.show', $session));
});

test('allows results after rationalization completion', function () {
    [$user, $case, $session] = makeUserSession('completed');
    $session->update(['rationalization_completed_at' => now()]);

    $this->actingAs($user);

    $resp = $this->get(route('osce.results.show', $session));
    $resp->assertOk();
});
