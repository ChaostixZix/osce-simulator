<?php

namespace App\Jobs;

use App\Models\SessionOrderedTest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTestResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $readyTests = SessionOrderedTest::whereNotNull('results_available_at')
            ->where('results_available_at', '<=', now())
            ->whereNull('completed_at')
            ->with(['osceSession.osceCase', 'medicalTest'])
            ->get();

        foreach ($readyTests as $test) {
            $results = $this->generateTestResults($test);
            $test->update([
                'results' => $results,
                'completed_at' => now(),
            ]);
        }
    }

    private function generateTestResults(SessionOrderedTest $test): array
    {
        $case = $test->osceSession->osceCase;
        $templates = $case->test_results_templates ?? [];
        $byId = $templates[$test->medical_test_id] ?? null;
        $fallback = [
            'status' => 'completed',
            'message' => 'Results not configured for this test in this case',
        ];
        $result = is_array($byId) ? $byId : $fallback;
        $result['turnaround_time_minutes'] = $test->ordered_at ? $test->ordered_at->diffInMinutes(now()) : null;
        return $result;
    }
}


