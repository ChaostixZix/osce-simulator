<?php

namespace Tests\Feature;

use App\Models\OsceSession;
use App\Models\User;
use App\Models\OsceCase;
use App\Services\AssessmentQueueService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssessmentQueueTest extends TestCase
{
    use RefreshDatabase;

    private AssessmentQueueService $queueService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queueService = app(AssessmentQueueService::class);
    }

    public function test_can_enqueue_assessment()
    {
        // Create test data
        $user = User::factory()->create();
        $case = OsceCase::factory()->create();
        $session = OsceSession::factory()->create([
            'user_id' => $user->id,
            'osce_case_id' => $case->id,
            'status' => 'completed',
        ]);

        // Enqueue assessment
        $run = $this->queueService->enqueueAssessment($session->id);

        $this->assertDatabaseHas('ai_assessment_runs', [
            'id' => $run->id,
            'osce_session_id' => $session->id,
            'status' => 'queued',
        ]);

        // Check queue status
        $status = $this->queueService->getQueueStatus($session->id);
        
        $this->assertEquals('queued', $status['status']);
        $this->assertEquals(1, $status['queue_position']);
        $this->assertNotNull($status['estimated_wait_time_minutes']);
    }

    public function test_queue_positions_update_correctly()
    {
        // Create multiple sessions
        $user = User::factory()->create();
        $case = OsceCase::factory()->create();
        
        $sessions = [];
        for ($i = 0; $i < 3; $i++) {
            $sessions[] = OsceSession::factory()->create([
                'user_id' => $user->id,
                'osce_case_id' => $case->id,
                'status' => 'completed',
            ]);
        }

        // Enqueue all assessments
        $runs = [];
        foreach ($sessions as $session) {
            $runs[] = $this->queueService->enqueueAssessment($session->id);
        }

        // Check positions
        for ($i = 0; $i < 3; $i++) {
            $status = $this->queueService->getQueueStatus($sessions[$i]->id);
            $this->assertEquals($i + 1, $status['queue_position']);
        }
    }

    public function test_assessment_lifecycle()
    {
        $user = User::factory()->create();
        $case = OsceCase::factory()->create();
        $session = OsceSession::factory()->create([
            'user_id' => $user->id,
            'osce_case_id' => $case->id,
            'status' => 'completed',
        ]);

        // 1. Enqueue
        $run = $this->queueService->enqueueAssessment($session->id);
        $status = $this->queueService->getQueueStatus($session->id);
        $this->assertEquals('queued', $status['status']);

        // 2. Start
        $this->queueService->markAsStarted($run->id, 'history');
        $status = $this->queueService->getQueueStatus($session->id);
        $this->assertEquals('in_progress', $status['status']);
        $this->assertEquals('history', $status['current_area']);

        // 3. Update area
        $this->queueService->updateCurrentArea($run->id, 'examination');
        $status = $this->queueService->getQueueStatus($session->id);
        $this->assertEquals('examination', $status['current_area']);

        // 4. Complete
        $this->queueService->markAsCompleted($run->id);
        $status = $this->queueService->getQueueStatus($session->id);
        $this->assertEquals('completed', $status['status']);
    }

    public function test_failed_assessment_handling()
    {
        $user = User::factory()->create();
        $case = OsceCase::factory()->create();
        $session = OsceSession::factory()->create([
            'user_id' => $user->id,
            'osce_case_id' => $case->id,
            'status' => 'completed',
        ]);

        $run = $this->queueService->enqueueAssessment($session->id);
        $this->queueService->markAsStarted($run->id);
        
        // Mark as failed
        $errorMessage = 'Test error message';
        $this->queueService->markAsFailed($run->id, $errorMessage);
        
        $status = $this->queueService->getQueueStatus($session->id);
        $this->assertEquals('failed', $status['status']);
        $this->assertStringContains($errorMessage, $status['status_message']);
    }

    public function test_queue_overview()
    {
        $user = User::factory()->create();
        $case = OsceCase::factory()->create();
        
        // Create queued and processing assessments
        $queuedSession = OsceSession::factory()->create([
            'user_id' => $user->id,
            'osce_case_id' => $case->id,
            'status' => 'completed',
        ]);
        
        $processingSession = OsceSession::factory()->create([
            'user_id' => $user->id,
            'osce_case_id' => $case->id,
            'status' => 'completed',
        ]);

        $queuedRun = $this->queueService->enqueueAssessment($queuedSession->id);
        $processingRun = $this->queueService->enqueueAssessment($processingSession->id);
        $this->queueService->markAsStarted($processingRun->id, 'history');

        $overview = $this->queueService->getActiveQueue();

        $this->assertCount(1, $overview['queued']);
        $this->assertCount(1, $overview['processing']);
        $this->assertEquals(1, $overview['summary']['queued_count']);
        $this->assertEquals(1, $overview['summary']['processing_count']);
    }
}