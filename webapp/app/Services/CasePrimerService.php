<?php

namespace App\Services;

use App\Models\OsceCase;
use App\Models\CasePrimer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for generating adaptive case primers using AI.
 * Creates tailored briefings based on case complexity and user experience.
 */
class CasePrimerService
{
    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Generate or retrieve cached case primer
     */
    public function getCasePrimer(OsceCase $osceCase, array $options = []): array
    {
        $cacheKey = "case_primer_{$osceCase->id}_" . md5(json_encode($options));

        // Check cache first (valid for 24 hours)
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return array_merge($cached, ['cached' => true]);
        }

        // Generate new primer
        try {
            $primer = $this->generatePrimer($osceCase, $options);

            // Cache the result
            Cache::put($cacheKey, $primer, now()->addHours(24));

            // Save to database for analytics
            $this->savePrimerToDatabase($osceCase, $primer, $options);

            return array_merge($primer, ['cached' => false]);

        } catch (\Exception $e) {
            Log::error('Case primer generation failed', [
                'case_id' => $osceCase->id,
                'error' => $e->getMessage(),
                'options' => $options
            ]);

            // Return fallback primer
            return $this->getFallbackPrimer($osceCase);
        }
    }

    /**
     * Generate a comprehensive case primer using AI
     */
    private function generatePrimer(OsceCase $osceCase, array $options = []): array
    {
        $userLevel = $options['user_level'] ?? 'intermediate';
        $focusAreas = $options['focus_areas'] ?? [];
        $timeAllotted = $osceCase->duration_minutes ?? 30;

        $prompt = $this->buildPrimerPrompt($osceCase, $userLevel, $focusAreas, $timeAllotted);

        $schema = [
            'type' => 'object',
            'properties' => [
                'clinical_overview' => [
                    'type' => 'object',
                    'properties' => [
                        'likely_diagnoses' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Top 3-5 likely diagnoses'
                        ],
                        'red_flags' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Critical signs to watch for'
                        ],
                        'key_history_points' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Essential history questions'
                        ]
                    ]
                ],
                'clinical_reasoning' => [
                    'type' => 'object',
                    'properties' => [
                        'initial_assessment_approach' => [
                            'type' => 'string',
                            'description' => 'How to approach the initial assessment'
                        ],
                        'differential_thinking' => [
                            'type' => 'string',
                            'description' => 'How to think through differentials'
                        ],
                        'time_management_tips' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Tips for managing time effectively'
                        ]
                    ]
                ],
                'investigation_strategy' => [
                    'type' => 'object',
                    'properties' => [
                        'first_line_tests' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Most cost-effective initial tests'
                        ],
                        'second_line_tests' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Additional tests if needed'
                        ],
                        'physical_exam_priorities' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Most important physical exam components'
                        ]
                    ]
                ],
                'common_pitfalls' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'pitfall' => ['type' => 'string'],
                            'avoidance_tip' => ['type' => 'string']
                        ]
                    ],
                    'description' => 'Common mistakes and how to avoid them'
                ],
                'success_indicators' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Signs that the case is being handled well'
                ],
                'complexity_rating' => [
                    'type' => 'string',
                    'enum' => ['beginner', 'intermediate', 'advanced', 'expert'],
                    'description' => 'Difficulty level of this case'
                ]
            ],
            'required' => ['clinical_overview', 'clinical_reasoning', 'investigation_strategy', 'common_pitfalls', 'success_indicators', 'complexity_rating']
        ];

        $result = $this->geminiService->generateJson($schema, $prompt, [
            'temperature' => 0.2, // Lower temperature for more consistent medical advice
            'maxOutputTokens' => 2048
        ]);

        if (empty($result)) {
            throw new \Exception('Failed to generate case primer - empty response from AI');
        }

        return $result;
    }

    /**
     * Build the AI prompt for case primer generation
     */
    private function buildPrimerPrompt(OsceCase $osceCase, string $userLevel, array $focusAreas, int $timeAllotted): string
    {
        $focusText = empty($focusAreas) ? '' : ' Pay special attention to: ' . implode(', ', $focusAreas) . '.';

        return sprintf(
            "You are an expert medical educator creating a clinical case primer for a %s level medical student. " .
            "Generate a comprehensive but concise primer for the following OSCE case:\n\n" .
            "Case Title: %s\n" .
            "Chief Complaint: %s\n" .
            "Clinical Setting: %s\n" .
            "Time Allotted: %d minutes\n" .
            "Patient Demographics: %s\n\n" .
            "Context: %s\n\n" .
            "Create a structured primer that helps the student approach this case systematically. " .
            "Include evidence-based medical guidance appropriate for the %s level. " .
            "Focus on practical, actionable advice that can be applied within the %d-minute timeframe.%s " .
            "Ensure all medical advice follows current clinical guidelines and best practices.",
            $userLevel,
            $osceCase->title,
            $osceCase->chief_complaint,
            $osceCase->clinical_setting ?? 'General Clinical',
            $timeAllotted,
            $osceCase->patient_demographics ?? 'Not specified',
            $osceCase->clinical_context ?? $osceCase->description ?? 'Standard clinical scenario',
            $userLevel,
            $timeAllotted,
            $focusText
        );
    }

    /**
     * Save generated primer to database for analytics and caching
     */
    private function savePrimerToDatabase(OsceCase $osceCase, array $primer, array $options): void
    {
        try {
            CasePrimer::updateOrCreate(
                [
                    'osce_case_id' => $osceCase->id,
                    'options_hash' => md5(json_encode($options))
                ],
                [
                    'primer_data' => $primer,
                    'user_level' => $options['user_level'] ?? 'intermediate',
                    'focus_areas' => $options['focus_areas'] ?? [],
                    'generated_at' => now()
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to save case primer to database', [
                'case_id' => $osceCase->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get fallback primer when AI generation fails
     */
    private function getFallbackPrimer(OsceCase $osceCase): array
    {
        return [
            'clinical_overview' => [
                'likely_diagnoses' => ['Based on: ' . $osceCase->chief_complaint],
                'red_flags' => ['Monitor vital signs', 'Watch for clinical deterioration'],
                'key_history_points' => ['Symptom onset and duration', 'Associated symptoms', 'Past medical history']
            ],
            'clinical_reasoning' => [
                'initial_assessment_approach' => 'Start with a systematic history and focused physical examination based on the chief complaint.',
                'differential_thinking' => 'Consider common causes first, then rare but serious conditions.',
                'time_management_tips' => ['Allocate 1/3 time for history', 'Use focused physical exam', 'Order targeted investigations']
            ],
            'investigation_strategy' => [
                'first_line_tests' => ['Basic vital signs', 'Relevant blood work'],
                'second_line_tests' => ['Imaging if indicated', 'Specialist tests'],
                'physical_exam_priorities' => ['General appearance', 'Focused system examination']
            ],
            'common_pitfalls' => [
                ['pitfall' => 'Ordering too many tests', 'avoidance_tip' => 'Focus on clinical reasoning first'],
                ['pitfall' => 'Missing time management', 'avoidance_tip' => 'Keep an eye on the timer']
            ],
            'success_indicators' => ['Systematic approach', 'Clear clinical reasoning', 'Appropriate investigations'],
            'complexity_rating' => 'intermediate',
            'fallback' => true,
            'cached' => false
        ];
    }

    /**
     * Generate quick primer summary for onboarding
     */
    public function getQuickPrimer(OsceCase $osceCase): array
    {
        $cacheKey = "quick_primer_{$osceCase->id}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($osceCase) {
            $prompt = sprintf(
                "Create a quick 3-point primer for this OSCE case:\n" .
                "Title: %s\n" .
                "Chief Complaint: %s\n\n" .
                "Provide exactly 3 key points a medical student should know before starting this case. " .
                "Keep each point to 1-2 sentences and focus on practical, actionable guidance.",
                $osceCase->title,
                $osceCase->chief_complaint
            );

            $schema = [
                'type' => 'object',
                'properties' => [
                    'key_points' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'minItems' => 3,
                        'maxItems' => 3,
                        'description' => 'Three key points for case preparation'
                    ]
                ],
                'required' => ['key_points']
            ];

            $result = $this->geminiService->generateJson($schema, $prompt, [
                'temperature' => 0.3,
                'maxOutputTokens' => 512
            ]);

            return $result['key_points'] ?? [
                'Systematic history taking is crucial for this presentation',
                'Focus on targeted physical examination based on symptoms',
                'Consider both common and serious causes in your differential'
            ];
        });
    }
}