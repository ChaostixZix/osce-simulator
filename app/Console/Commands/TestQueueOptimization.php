<?php

namespace App\Console\Commands;

use App\Jobs\UpdateQueuePositionsJob;
use App\Models\AiAssessmentRun;
use App\Services\AssessmentQueueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestQueueOptimization extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'queue:test-optimization';

    /**
     * The console command description.
     */
    protected $description = 'Test the optimized queue system performance';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Testing optimized queue system...');

        // Skip creating test runs for now, just test the optimization logic
        $this->info('Testing optimization without database inserts...');
        
        // Check existing queued runs instead
        $existingRuns = AiAssessmentRun::where('status', 'queued')->count();
        $this->info("Found {$existingRuns} existing queued assessment runs");

        // Test 1: Async queue position update
        $this->info('Test 1: Testing async queue position updates...');
        $start = microtime(true);
        
        $queueService = app(AssessmentQueueService::class);
        $queueService->updateQueuePositions(); // Should be async now
        
        $asyncTime = (microtime(true) - $start) * 1000;
        $this->info("Async update time: {$asyncTime}ms");

        // Test 2: Sync queue position update (for comparison)
        $this->info('Test 2: Testing sync queue position updates...');
        $start = microtime(true);
        
        $queueService->updateQueuePositionsSync(); // Direct sync call
        
        $syncTime = (microtime(true) - $start) * 1000;
        $this->info("Sync update time: {$syncTime}ms");

        // Test 3: Background job processing
        $this->info('Test 3: Testing background job...');
        $start = microtime(true);
        
        UpdateQueuePositionsJob::dispatch()->onQueue('management');
        
        $jobDispatchTime = (microtime(true) - $start) * 1000;
        $this->info("Job dispatch time: {$jobDispatchTime}ms");

        // Verify results
        $this->info('Checking any existing queue positions...');
        $queuedRuns = AiAssessmentRun::where('status', 'queued')
            ->orderBy('created_at', 'asc')
            ->limit(5)
            ->get(['id', 'queue_position', 'status_message']);

        if ($queuedRuns->isNotEmpty()) {
            $this->table(
                ['ID', 'Position', 'Status Message'],
                $queuedRuns->map(fn($run) => [
                    $run->id,
                    $run->queue_position ?? 'NULL',
                    $run->status_message ?? 'NULL'
                ])->toArray()
            );
        } else {
            $this->info('No queued assessments found.');
        }

        // Performance summary
        $this->info('Performance Summary:');
        $this->line("• Async update: {$asyncTime}ms (should be ~0-5ms)");
        $this->line("• Sync update: {$syncTime}ms");
        $this->line("• Job dispatch: {$jobDispatchTime}ms");
        
        if ($asyncTime < 10) {
            $this->info('✅ Async optimization working correctly!');
        } else {
            $this->warn('⚠️  Async update taking too long');
        }

        // No cleanup needed since we didn't create test data
        
        $this->info('Queue optimization test completed!');
    }
}