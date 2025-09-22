<?php

namespace Database\Factories;

use App\Models\OsceCase;
use Illuminate\Database\Eloquent\Factories\Factory;

class OsceCaseFactory extends Factory
{
    protected $model = OsceCase::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(3),
            'difficulty' => $this->faker->randomElement(['easy', 'medium', 'hard']),
            'duration_minutes' => 25,
            'scenario' => $this->faker->paragraph(4),
            'objectives' => $this->faker->paragraph(3),
            'stations' => null,
            'checklist' => null,
            'is_active' => true,
            'ai_patient_profile' => $this->faker->paragraph(5),
            'ai_patient_vitals' => [
                'temperature' => $this->faker->randomFloat(1, 36.0, 39.0),
                'heart_rate' => $this->faker->numberBetween(60, 120),
                'blood_pressure' => $this->faker->numberBetween(90, 160).'/'.$this->faker->numberBetween(60, 100),
                'respiratory_rate' => $this->faker->numberBetween(12, 24),
                'oxygen_saturation' => $this->faker->numberBetween(92, 100),
            ],
            'ai_patient_symptoms' => [
                'chief_complaint' => $this->faker->sentence(6),
                'duration' => $this->faker->randomElement(['1 hour', '2 days', '1 week', '2 months']),
                'severity' => $this->faker->numberBetween(1, 10),
            ],
            'ai_patient_instructions' => $this->faker->paragraph(3),
            'ai_patient_responses' => [
                'pain_description' => 'Sharp, stabbing pain',
                'location' => 'Right lower quadrant',
                'aggravating_factors' => 'Movement, coughing',
            ],
            'highly_appropriate_tests' => ['Complete Blood Count', 'Basic Metabolic Panel'],
            'appropriate_tests' => ['Chest X-ray', 'ECG'],
            'acceptable_tests' => ['Urinalysis'],
            'inappropriate_tests' => ['MRI Brain'],
            'contraindicated_tests' => [],
            'required_tests' => ['Complete Blood Count'],
            'clinical_setting' => $this->faker->randomElement(['emergency', 'outpatient', 'inpatient']),
            'urgency_level' => $this->faker->numberBetween(1, 5),
            'setting_limitations' => null,
            'case_budget' => $this->faker->randomFloat(2, 500, 2000),
            'test_results_templates' => null,
            'expected_anamnesis_questions' => [
                'When did the pain start?',
                'Can you describe the pain?',
                'Have you had any nausea or vomiting?',
            ],
            'red_flags' => ['Severe abdominal pain', 'Fever > 38.5°C', 'Hypotension'],
            'common_differentials' => ['Appendicitis', 'Cholecystitis', 'Kidney stones'],
        ];
    }
}
