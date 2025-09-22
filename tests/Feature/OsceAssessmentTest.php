<?php

use App\Jobs\AssessOsceSessionJob;
use App\Models\MedicalTest;
use App\Models\OsceCase;
use App\Models\OsceChatMessage;
use App\Models\OsceSession;
use App\Models\SessionExamination;
use App\Models\SessionOrderedTest;
use App\Models\User;
use App\Services\AiAssessorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test users
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();

    // Create test case directly
    $this->osceCase = OsceCase::create([
        'title' => 'Test Chest Pain Case',
        'description' => 'A patient presents with chest pain',
        'difficulty' => 'intermediate',
        'duration_minutes' => 30,
        'scenario' => 'You are a medical student in the emergency department. A 45-year-old patient presents with chest pain.',
        'objectives' => json_encode(['Take focused history', 'Perform physical examination', 'Order appropriate tests']),
        'checklist' => json_encode(['pain characteristics', 'vital signs', 'cardiovascular exam']),
        'required_tests' => json_encode(['ECG', 'Troponin']),
        'highly_appropriate_tests' => json_encode(['Chest X-ray']),
        'contraindicated_tests' => json_encode(['CT Brain']),
        'case_budget' => 1000,
        'is_active' => true,
    ]);

    // Create test session directly
    $this->session = OsceSession::create([
        'user_id' => $this->user->id,
        'osce_case_id' => $this->osceCase->id,
        'status' => 'completed',
        'started_at' => now()->subMinutes(25),
        'completed_at' => now(),
    ]);
});

test('assessment service computes rubric scores correctly', function () {
    $service = app(AiAssessorService::class);

    // Add some test data
    OsceChatMessage::create([
        'osce_session_id' => $this->session->id,
        'sender_type' => 'user',
        'message' => 'Tell me about your pain location',
        'sent_at' => now()->subMinutes(20),
    ]);

    $medicalTest = MedicalTest::create([
        'name' => 'ECG',
        'cost' => 50,
        'category' => 'cardiology',
        'type' => 'lab',
        'description' => 'Electrocardiogram',
    ]);

    SessionOrderedTest::create([
        'osce_session_id' => $this->session->id,
        'medical_test_id' => $medicalTest->id,
        'ordered_at' => now()->subMinutes(15),
        'result' => 'Normal sinus rhythm',
    ]);

    SessionExamination::create([
        'osce_session_id' => $this->session->id,
        'examination_type' => 'auscultation',
        'body_part' => 'chest',
        'finding' => 'Normal heart sounds',
        'performed_at' => now()->subMinutes(10),
    ]);

    $config = config('osce_scoring');
    $scores = $service->computeScores($this->session, $config);

    expect($scores)->toBeArray();
    expect($scores)->toHaveCount(7); // 7 criteria

    foreach ($scores as $score) {
        expect($score)->toHaveKeys(['key', 'score', 'max']);
        expect($score['score'])->toBeInt();
        expect($score['max'])->toBeInt();
        expect($score['score'])->toBeLessThanOrEqual($score['max']);
    }
});

test('assessment artifact includes all required data', function () {
    $service = app(AiAssessorService::class);

    // Add test data
    OsceChatMessage::create([
        'osce_session_id' => $this->session->id,
        'sender_type' => 'user',
        'message' => 'Test message',
        'sent_at' => now()->subMinutes(5),
    ]);

    $artifact = $service->buildArtifact($this->session);

    expect($artifact)->toHaveKeys([
        'session_id',
        'rubric_version',
        'case',
        'transcript',
        'actions',
        'metrics',
    ]);

    expect($artifact['case'])->toHaveKeys([
        'id',
        'title',
        'chief_complaint',
        'required_tests',
        'highly_appropriate_tests',
        'contraindicated_tests',
    ]);

    expect($artifact['transcript'])->toBeArray();
    expect($artifact['actions'])->toHaveKeys(['tests', 'examinations']);
    expect($artifact['metrics'])->toHaveKeys([
        'total_cost',
        'case_budget',
        'elapsed_minutes',
        'duration_minutes',
    ]);
});

test('session completion dispatches assessment job', function () {
    Queue::fake();

    $session = OsceSession::create([
        'user_id' => $this->user->id,
        'osce_case_id' => $this->osceCase->id,
        'status' => 'in_progress',
        'started_at' => now()->subMinutes(25),
    ]);

    $session->markAsCompleted();

    Queue::assertPushed(AssessOsceSessionJob::class, function ($job) use ($session) {
        return $job->sessionId === $session->id;
    });
});

test('assessment job processes session correctly', function () {
    $job = new AssessOsceSessionJob($this->session->id);
    $job->handle();

    $this->session->refresh();

    expect($this->session->assessed_at)->not->toBeNull();
    expect($this->session->score)->toBeInt();
    expect($this->session->max_score)->toBeInt();
    expect($this->session->assessor_payload)->toBeArray();
    expect($this->session->assessor_output)->toBeArray();
    expect($this->session->rubric_version)->toBe('RUBRIC_V1.0');
});

test('assessment job is idempotent', function () {
    // First assessment
    $job = new AssessOsceSessionJob($this->session->id);
    $job->handle();

    $firstAssessedAt = $this->session->fresh()->assessed_at;
    $firstScore = $this->session->fresh()->score;

    // Second assessment without force
    $job = new AssessOsceSessionJob($this->session->id);
    $job->handle();

    $this->session->refresh();
    expect($this->session->assessed_at->toISOString())->toBe($firstAssessedAt->toISOString());
    expect($this->session->score)->toBe($firstScore);
});

test('forced assessment overwrites existing results', function () {
    // First assessment
    $job = new AssessOsceSessionJob($this->session->id);
    $job->handle();

    $firstAssessedAt = $this->session->fresh()->assessed_at;

    // Wait a second to ensure different timestamp
    sleep(1);

    // Forced reassessment
    $job = new AssessOsceSessionJob($this->session->id, true);
    $job->handle();

    $this->session->refresh();
    expect($this->session->assessed_at->toISOString())->not->toBe($firstAssessedAt->toISOString());
});

test('unauthorized user cannot access assessment endpoints', function () {
    $this->actingAs($this->otherUser)
        ->postJson("/api/osce/sessions/{$this->session->id}/assess")
        ->assertStatus(403);

    $this->actingAs($this->otherUser)
        ->getJson("/api/osce/sessions/{$this->session->id}/results")
        ->assertStatus(403);

    $this->actingAs($this->otherUser)
        ->get("/osce/results/{$this->session->id}")
        ->assertStatus(403);
});

test('session owner can access assessment endpoints', function () {
    // Ensure session is assessed first
    $job = new AssessOsceSessionJob($this->session->id);
    $job->handle();

    $this->actingAs($this->user)
        ->postJson("/api/osce/sessions/{$this->session->id}/assess")
        ->assertStatus(200);

    $this->actingAs($this->user)
        ->getJson("/api/osce/sessions/{$this->session->id}/results")
        ->assertStatus(200)
        ->assertJsonStructure([
            'session_id',
            'score',
            'max_score',
            'assessed_at',
            'assessor_output',
        ]);

    $this->actingAs($this->user)
        ->get("/osce/results/{$this->session->id}")
        ->assertStatus(200);
});

test('user can reassess their own session', function () {
    // Ensure session is assessed first
    $job = new AssessOsceSessionJob($this->session->id);
    $job->handle();

    $this->actingAs($this->user)
        ->postJson("/api/osce/sessions/{$this->session->id}/assess", ['force' => true])
        ->assertStatus(200);
});

test('assessment api returns error for unassessed session', function () {
    $unassessedSession = OsceSession::create([
        'user_id' => $this->user->id,
        'osce_case_id' => $this->osceCase->id,
        'status' => 'completed',
        'started_at' => now()->subMinutes(30),
        'completed_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->getJson("/api/osce/sessions/{$unassessedSession->id}/results")
        ->assertStatus(404)
        ->assertJson(['error' => 'Session has not been assessed yet']);
});

test('assessment cannot be triggered for active session', function () {
    $activeSession = OsceSession::create([
        'user_id' => $this->user->id,
        'osce_case_id' => $this->osceCase->id,
        'status' => 'in_progress',
        'started_at' => now()->subMinutes(10),
    ]);

    $this->actingAs($this->user)
        ->postJson("/api/osce/sessions/{$activeSession->id}/assess")
        ->assertStatus(400)
        ->assertJson(['error' => 'Session must be completed or expired before assessment']);
});

test('scoring includes penalties for contraindicated tests', function () {
    $service = app(AiAssessorService::class);

    // Add contraindicated test
    $contraindicatedTest = MedicalTest::create([
        'name' => 'CT Brain',
        'cost' => 500,
        'category' => 'radiology',
        'type' => 'radiology',
        'description' => 'CT scan of brain',
    ]);

    SessionOrderedTest::create([
        'osce_session_id' => $this->session->id,
        'medical_test_id' => $contraindicatedTest->id,
        'ordered_at' => now()->subMinutes(15),
        'result' => 'Normal',
    ]);

    $config = config('osce_scoring');
    $scores = $service->computeScores($this->session, $config);

    $investigationsScore = collect($scores)->firstWhere('key', 'investigations');

    // Should have penalty applied
    expect($investigationsScore['score'])->toBeLessThan($investigationsScore['max']);
});

test('ai assessor service handles missing api key gracefully', function () {
    // Temporarily unset API key
    config(['services.gemini.api_key' => null]);

    $service = app(AiAssessorService::class);
    expect($service->isConfigured())->toBeFalse();

    $result = $service->assess($this->session);

    expect($result->assessor_output)->toBeArray();
    expect($result->assessor_output['model_info']['status'])->toBe('ai_unavailable');
});
