<?php

require_once 'webapp/vendor/autoload.php';
require_once 'webapp/bootstrap/app.php';

use App\Models\SessionOrderedTest;
use App\Jobs\ProcessTestResultsJob;
use Carbon\Carbon;

echo "=== DEBUG TEST RESULTS PROCESSING ===\n\n";

// Check for tests that should be ready for processing
echo "1. Checking for ready tests:\n";
$readyTests = SessionOrderedTest::whereNotNull('results_available_at')
    ->where('results_available_at', '<=', now())
    ->whereNull('completed_at')
    ->with(['osceSession.osceCase', 'medicalTest'])
    ->get();

echo "Found {$readyTests->count()} tests ready for processing\n\n";

foreach ($readyTests as $test) {
    echo "Test ID: {$test->id}\n";
    echo "- Name: {$test->test_name}\n";
    echo "- Ordered at: {$test->ordered_at}\n";
    echo "- Results available at: {$test->results_available_at}\n";
    echo "- Current time: " . now()->format('Y-m-d H:i:s') . "\n";
    echo "- Time diff: " . $test->results_available_at->diffInSeconds(now(), false) . " seconds\n";
    echo "- Has results: " . (empty($test->results) ? 'NO' : 'YES') . "\n";
    echo "- Completed at: " . ($test->completed_at ?? 'Not completed') . "\n\n";
}

echo "2. Running ProcessTestResultsJob manually:\n";
$job = new ProcessTestResultsJob();
$job->handle();

echo "Job executed!\n\n";

echo "3. Checking tests again after processing:\n";
$readyTestsAfter = SessionOrderedTest::whereNotNull('results_available_at')
    ->where('results_available_at', '<=', now())
    ->whereNull('completed_at')
    ->with(['osceSession.osceCase', 'medicalTest'])
    ->get();

echo "Ready tests after processing: {$readyTestsAfter->count()}\n";

$completedTests = SessionOrderedTest::whereNotNull('completed_at')
    ->whereNotNull('results')
    ->orderBy('completed_at', 'desc')
    ->limit(5)
    ->get();

echo "Recent completed tests: {$completedTests->count()}\n";

foreach ($completedTests as $test) {
    echo "- Test: {$test->test_name}, Completed: {$test->completed_at}, Results: " . 
         (is_array($test->results) ? json_encode($test->results) : 'None') . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";