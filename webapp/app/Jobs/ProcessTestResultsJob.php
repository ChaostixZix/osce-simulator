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
        // If configured template exists, use it
        if (is_array($byId)) {
            $result = $byId;
        } else {
            // Try AI generation via Gemini when templates are missing
            $result = $this->generateViaGemini($test) ?: [
                'status' => 'completed',
                'message' => 'Results not configured for this test in this case',
            ];
        }
        $result['turnaround_time_minutes'] = $test->ordered_at ? $test->ordered_at->diffInMinutes(now()) : null;

        return $result;
    }

    /**
     * Generate plausible test results using Gemini when no template is configured.
     * Produces random-but-normal-bounded outputs based on case context and test type.
     */
    private function generateViaGemini(SessionOrderedTest $test): array
    {
        // Skip when API key is not configured
        if (empty(config('services.gemini.api_key'))) {
            return [];
        }

        try {
            $gemini = app(\App\Services\GeminiService::class);
            $case = $test->osceSession->osceCase;
            $context = [
                'case_title' => $case->title,
                'clinical_setting' => $case->clinical_setting,
                'age' => $case->patient_age ?? null,
                'sex' => $case->patient_sex ?? null,
                'summary' => $case->summary ?? ($case->description ?? null),
            ];

            $schema = [
                'type' => 'object',
                'properties' => [
                    'status' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                    'values' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'value' => ['type' => ['string','number']],
                                'unit' => ['type' => 'string'],
                                'reference' => ['type' => 'string'],
                                'flag' => ['type' => 'string'],
                            ],
                            'required' => ['name','value'],
                        ],
                    ],
                ],
                'required' => ['status','message'],
                'additionalProperties' => true,
            ];

            $type = $test->test_type ?: ($test->medicalTest->type ?? 'lab');
            $name = $test->test_name ?: ($test->medicalTest->name ?? '');

            $prompt = "You generate realistic, concise clinical test results in JSON.\n".
                "Task: Produce results for a {$type} test named '{$name}'.\n".
                "Constraints: Values must be random but within normal physiological ranges unless the case context strongly suggests mild abnormality. Keep outputs brief and clinically plausible.\n".
                "Output fields: status (completed/abnormal), message (1 short line), values[] (array of measurements or findings).\n".
                "If {$type} is 'imaging' or 'procedure', 'values' can be a few key bullet findings with name and value as short strings.\n\n".
                'Case context (JSON): '.json_encode($context);

            $json = $gemini->generateJson($schema, $prompt, [
                'temperature' => 0.2,
                'topP' => 0.8,
            ]);
            if (!is_array($json) || empty($json)) {
                return [];
            }
            // Ensure expected shape
            $json['status'] = $json['status'] ?? 'completed';
            $json['message'] = $json['message'] ?? 'Auto-generated results';
            if (!isset($json['values'])) {
                $json['values'] = [];
            }
            return $json;
        } catch (\Throwable $e) {
            \Log::warning('Gemini generation for test results failed', [
                'error' => $e->getMessage(),
                'test_id' => $test->id,
            ]);
            return [];
        }
    }
}
