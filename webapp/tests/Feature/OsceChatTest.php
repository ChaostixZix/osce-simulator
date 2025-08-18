<?php

use App\Models\OsceCase;
use App\Models\OsceSession;
use App\Models\User;
use App\Models\OsceChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->osceCase = OsceCase::create([
        'title' => 'Test Case',
        'description' => 'Test Description',
        'difficulty' => 'medium',
        'duration_minutes' => 15,
        'scenario' => 'Test scenario',
        'objectives' => 'Test objectives',
        'stations' => ['Station 1'],
        'checklist' => ['Check 1'],
        'is_active' => true,
        'ai_patient_profile' => 'Test patient profile',
        'ai_patient_vitals' => ['BP' => '120/80'],
        'ai_patient_symptoms' => ['Symptom 1'],
        'ai_patient_instructions' => 'Test instructions',
        'ai_patient_responses' => ['test' => 'Test response']
    ]);
    
    $this->session = OsceSession::create([
        'user_id' => $this->user->id,
        'osce_case_id' => $this->osceCase->id,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);
});

it('can start chat for an active session', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/osce/chat/start', [
            'session_id' => $this->session->id
        ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'session',
            'system_message'
        ]);

    $this->assertDatabaseHas('osce_chat_messages', [
        'osce_session_id' => $this->session->id,
        'sender_type' => 'system'
    ]);
});

it('cannot start chat for inactive session', function () {
    $this->session->update(['status' => 'completed']);

    $response = $this->actingAs($this->user)
        ->postJson('/api/osce/chat/start', [
            'session_id' => $this->session->id
        ]);

    $response->assertStatus(400)
        ->assertJson(['error' => 'Session is not active']);
});

it('cannot start chat for another users session', function () {
    $otherUser = User::factory()->create();
    $otherSession = OsceSession::create([
        'user_id' => $otherUser->id,
        'osce_case_id' => $this->osceCase->id,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/api/osce/chat/start', [
            'session_id' => $otherSession->id
        ]);

    $response->assertStatus(404);
});

it('can send message and receive ai response', function () {
    // Mock the AI service to return a predictable response
    $this->mock(\App\Services\AiPatientService::class, function ($mock) {
        $mock->shouldReceive('generatePatientResponse')
            ->once()
            ->andReturn('Mock AI response');
    });

    $response = $this->actingAs($this->user)
        ->postJson('/api/osce/chat/message', [
            'session_id' => $this->session->id,
            'message' => 'Hello, how are you feeling?'
        ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'user_message',
            'ai_response'
        ]);

    $this->assertDatabaseHas('osce_chat_messages', [
        'osce_session_id' => $this->session->id,
        'sender_type' => 'user',
        'message' => 'Hello, how are you feeling?'
    ]);

    $this->assertDatabaseHas('osce_chat_messages', [
        'osce_session_id' => $this->session->id,
        'sender_type' => 'ai_patient',
        'message' => 'Mock AI response'
    ]);
});

it('cannot send message to inactive session', function () {
    $this->session->update(['status' => 'completed']);

    $response = $this->actingAs($this->user)
        ->postJson('/api/osce/chat/message', [
            'session_id' => $this->session->id,
            'message' => 'Test message'
        ]);

    $response->assertStatus(400)
        ->assertJson(['error' => 'Session is not active']);
});

it('validates message input', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/osce/chat/message', [
            'session_id' => $this->session->id,
            'message' => '' // Empty message
        ]);

    $response->assertStatus(422);

    $response = $this->actingAs($this->user)
        ->postJson('/api/osce/chat/message', [
            'session_id' => $this->session->id,
            'message' => str_repeat('a', 1001) // Too long message
        ]);

    $response->assertStatus(422);
});

it('can retrieve chat history', function () {
    // Create some chat messages
    OsceChatMessage::create([
        'osce_session_id' => $this->session->id,
        'sender_type' => 'user',
        'message' => 'User message 1',
        'sent_at' => now()->subMinutes(2)
    ]);

    OsceChatMessage::create([
        'osce_session_id' => $this->session->id,
        'sender_type' => 'ai_patient',
        'message' => 'AI response 1',
        'sent_at' => now()->subMinute()
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/osce/chat/history/{$this->session->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'messages'
        ])
        ->assertJsonCount('messages', 2);
});

it('cannot retrieve chat history for another users session', function () {
    $otherUser = User::factory()->create();
    $otherSession = OsceSession::create([
        'user_id' => $otherUser->id,
        'osce_case_id' => $this->osceCase->id,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/osce/chat/history/{$otherSession->id}");

    $response->assertStatus(404);
});

it('can access osce chat page', function () {
    $response = $this->actingAs($this->user)
        ->get("/osce/chat/{$this->session->id}");

    $response->assertSuccessful();
});

it('cannot access osce chat page for another users session', function () {
    $otherUser = User::factory()->create();
    $otherSession = OsceSession::create([
        'user_id' => $otherUser->id,
        'osce_case_id' => $this->osceCase->id,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->get("/osce/chat/{$otherSession->id}");

    $response->assertForbidden();
});
