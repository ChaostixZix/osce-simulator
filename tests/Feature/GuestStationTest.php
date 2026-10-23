<?php

use App\Models\OsceCase;
use App\Models\OsceSession;
use App\Services\LocalPatientService;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    Http::preventStrayRequests();
    config(['demo.enabled' => true, 'services.ai.provider' => 'local']);
    $this->seed(DemoSeeder::class);
});

function beginGuest($test): OsceSession
{
    $test->post('/demo/sessions')->assertRedirect();
    return OsceSession::latest('id')->firstOrFail();
}

function guestSummary(): array
{
    return [
        'diagnosis' => 'Working diagnosis recorded for examiner review.',
        'differential_diagnosis' => 'Alternative diagnoses recorded for examiner review.',
        'plan' => 'Management reasoning recorded for examiner review.',
    ];
}

test('visitor can complete a persisted station without any external provider', function () {
    $this->get('/')->assertOk();
    $this->get('/demo')->assertOk();
    $session = beginGuest($this);
    $this->post("/demo/sessions/{$session->id}/messages", ['message' => 'When did the pain start?', 'client_message_id' => (string) Str::uuid()])->assertRedirect();
    $this->post("/demo/sessions/{$session->id}/actions", ['action' => 'vitals'])->assertRedirect();
    $this->post("/demo/sessions/{$session->id}/submit", guestSummary())->assertRedirect("/demo/sessions/{$session->id}/review");
    $this->get("/demo/sessions/{$session->id}/review")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Guest/Review')->where('review.kind', 'evidence_review')
        ->where('review.message_count', 1)->has('review.actions', 1)->has('messages', 2));
    $saved = $session->fresh();
    expect($saved->guest_review['summary']['diagnosis'])->toBe(guestSummary()['diagnosis'])
        ->and($saved->score)->toBeNull()->and($saved->assessed_at)->toBeNull()
        ->and($saved->guest_review['checklist'])->toBe($saved->osceCase->checklist);
    $this->get("/demo/sessions/{$session->id}/review")->assertOk();
    Http::assertNothingSent();
});

test('guest reads and writes require the owning browser session', function () {
    $session = beginGuest($this);
    $this->app['session']->flush();
    $this->get("/demo/sessions/{$session->id}")->assertNotFound();
    $this->get("/demo/sessions/{$session->id}/review")->assertNotFound();
    $this->post("/demo/sessions/{$session->id}/messages", [])->assertNotFound();
    $this->post("/demo/sessions/{$session->id}/actions", ['action' => 'vitals'])->assertNotFound();
    $this->post("/demo/sessions/{$session->id}/submit", guestSummary())->assertNotFound();
});

test('refresh and repeated starts preserve the timer and encounter', function () {
    $session = beginGuest($this);
    $started = $session->started_at->toISOString();
    $this->travel(3)->minutes();
    $this->get("/demo/sessions/{$session->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('encounter.started_at', $started)->where('encounter.remaining_seconds', 1320));
    $this->post('/demo/sessions')->assertRedirect("/demo/sessions/{$session->id}");
    expect(OsceSession::count())->toBe(1);
});

test('expired encounters reject new actions but accept a summary', function () {
    $session = beginGuest($this);
    $this->travel(26)->minutes();
    $this->post("/demo/sessions/{$session->id}/messages", ['message' => 'Where is the pain?', 'client_message_id' => (string) Str::uuid()])->assertSessionHasErrors('message');
    $this->post("/demo/sessions/{$session->id}/actions", ['action' => 'vitals'])->assertSessionHasErrors('action');
    $this->post("/demo/sessions/{$session->id}/submit", guestSummary())->assertRedirect("/demo/sessions/{$session->id}/review");
    expect($session->fresh()->guest_review['message_count'])->toBe(0);
});

test('retries do not duplicate messages actions or overwrite a completed review', function () {
    $session = beginGuest($this);
    $payload = ['message' => 'Where does the pain spread?', 'client_message_id' => (string) Str::uuid()];
    foreach ([1, 2] as $attempt) {
        $this->post("/demo/sessions/{$session->id}/messages", $payload)->assertRedirect();
        $this->post("/demo/sessions/{$session->id}/actions", ['action' => 'ecg'])->assertRedirect();
    }
    expect($session->chatMessages()->count())->toBe(2)->and($session->fresh()->guest_actions)->toHaveCount(1);
    $this->post("/demo/sessions/{$session->id}/submit", guestSummary())->assertRedirect();
    $review = $session->fresh()->guest_review;
    $this->post("/demo/sessions/{$session->id}/submit", array_merge(guestSummary(), ['diagnosis' => 'A different submitted diagnosis']))->assertRedirect();
    expect($session->fresh()->guest_review)->toBe($review);
    $this->post("/demo/sessions/{$session->id}/actions", ['action' => 'vitals'])->assertSessionHasErrors('action');
    $this->post("/demo/sessions/{$session->id}/messages", ['message' => 'Another question', 'client_message_id' => (string) Str::uuid()])->assertSessionHasErrors('message');
    $this->post('/demo/sessions')->assertRedirect();
    expect(OsceSession::count())->toBe(2);
});

test('summary validation cannot complete the encounter early', function () {
    $session = beginGuest($this);
    $this->get("/demo/sessions/{$session->id}/review")->assertRedirect("/demo/sessions/{$session->id}");
    $this->post("/demo/sessions/{$session->id}/submit", [])->assertSessionHasErrors(['diagnosis', 'differential_diagnosis', 'plan']);
    expect($session->fresh()->finalized_at)->toBeNull()->and($session->fresh()->guest_review)->toBeNull();
    $this->post("/demo/sessions/{$session->id}/actions", ['action' => 'invented'])->assertSessionHasErrors('action');
    $this->post("/demo/sessions/{$session->id}/messages", ['message' => '   ', 'client_message_id' => (string) Str::uuid()])->assertSessionHasErrors('message');
});

test('patient answers use authored facts and do not invent missing clinical information', function () {
    $case = OsceCase::where('title', config('demo.case_title'))->firstOrFail();
    $patient = app(LocalPatientService::class);
    expect($patient->reply($case, 'When did your pain start?')['message'])->toBe($case->ai_patient_responses['onset'])
        ->and($patient->reply($case, 'Does the pain travel to your arm?')['message'])->toBe($case->ai_patient_responses['radiation'])
        ->and($patient->reply($case, 'What medications do you take?')['topic'])->toBe('unrecorded')
        ->and($patient->reply($case, 'Do you have allergies?')['topic'])->toBe('unrecorded');
    Http::assertNothingSent();
});

test('demo seeding preserves existing clinical content and pruning removes only old guest encounters', function () {
    $case = OsceCase::where('title', config('demo.case_title'))->firstOrFail();
    $original = $case->getAttributes();
    $this->seed(DemoSeeder::class);
    expect(OsceCase::count())->toBe(4)->and($case->fresh()->getAttributes())->toBe($original);
    $session = beginGuest($this);
    $this->travel(8)->days();
    $this->artisan('osce:prune-guests')->assertSuccessful();
    expect(OsceSession::find($session->id))->toBeNull();
});

test('disabled or inactive demo cannot create a guest encounter', function () {
    config(['demo.enabled' => false]);
    $this->get('/demo')->assertNotFound();
    $this->post('/demo/sessions')->assertNotFound();
    config(['demo.enabled' => true]);
    OsceCase::where('title', config('demo.case_title'))->update(['is_active' => false]);
    $this->post('/demo/sessions')->assertNotFound();
});

test('history and investigation rate limits do not consume summary submissions', function () {
    $session = beginGuest($this);
    foreach (range(1, 8) as $question) {
        $this->post("/demo/sessions/{$session->id}/messages", [
            'message' => 'When did the pain start?',
            'client_message_id' => (string) Str::uuid(),
        ])->assertRedirect();
    }
    foreach (['vitals', 'ecg', 'troponin', 'chest_xray'] as $action) {
        $this->post("/demo/sessions/{$session->id}/actions", ['action' => $action])->assertRedirect();
    }
    $this->post("/demo/sessions/{$session->id}/submit", guestSummary())
        ->assertRedirect("/demo/sessions/{$session->id}/review");
    expect($session->fresh()->guest_review['message_count'])->toBe(8);
});
