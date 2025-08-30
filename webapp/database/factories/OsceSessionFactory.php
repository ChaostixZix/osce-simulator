<?php

namespace Database\Factories;

use App\Models\OsceSession;
use App\Models\OsceCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OsceSession>
 */
class OsceSessionFactory extends Factory
{
    protected $model = OsceSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'osce_case_id' => OsceCase::factory(),
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(rand(1, 30)),
            'completed_at' => null,
            'score' => null,
            'max_score' => null,
            'time_extended' => 0,
            'clinical_reasoning_score' => null,
            'total_test_cost' => 0,
            'evaluation_feedback' => [],
            'responses' => [],
            'feedback' => [],
            'assessor_payload' => null,
            'assessor_output' => null,
            'assessed_at' => null,
            'assessor_model' => null,
            'rubric_version' => null,
        ];
    }

    /**
     * Indicate that the session is completed.
     */
    public function completed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'completed_at' => now()->subMinutes(rand(1, 60)),
                'score' => rand(60, 100),
                'max_score' => 100,
            ];
        });
    }

    /**
     * Indicate that the session is still in progress.
     */
    public function inProgress(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'in_progress',
                'completed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the session is assessed.
     */
    public function assessed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'completed_at' => now()->subMinutes(rand(30, 120)),
                'score' => rand(60, 100),
                'max_score' => 100,
                'assessed_at' => now()->subMinutes(rand(1, 30)),
                'assessor_model' => 'gemini-2.5-flash',
                'assessor_output' => [
                    'assessment_type' => 'test_assessment',
                    'total_score' => rand(60, 100),
                    'areas' => []
                ],
            ];
        });
    }
}