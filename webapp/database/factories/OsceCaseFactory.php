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
            'patient_age' => $this->faker->numberBetween(18, 80),
            'patient_gender' => $this->faker->randomElement(['male', 'female']),
            'chief_complaint' => $this->faker->sentence(6),
            'duration_minutes' => 25,
            'difficulty_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'is_active' => true,
            'clinical_setting' => $this->faker->randomElement(['emergency', 'outpatient', 'inpatient']),
            'case_budget' => 1000.00,
            'ai_patient_prompt' => $this->faker->paragraph(5),
            'patient_background' => $this->faker->paragraph(3),
            'vital_signs' => [
                'temperature' => $this->faker->randomFloat(1, 36.0, 39.0),
                'heart_rate' => $this->faker->numberBetween(60, 120),
                'blood_pressure' => $this->faker->numberBetween(90, 160).'/'.$this->faker->numberBetween(60, 100),
                'respiratory_rate' => $this->faker->numberBetween(12, 24),
                'oxygen_saturation' => $this->faker->numberBetween(92, 100),
            ],
            'physical_exam_findings' => [
                'general' => ['alert and oriented', 'no acute distress'],
                'cardiovascular' => ['regular rate and rhythm', 'no murmurs'],
                'respiratory' => ['clear to auscultation bilaterally'],
                'abdominal' => ['soft, non-tender, no masses'],
            ],
            'highly_appropriate_tests' => ['Complete Blood Count', 'Basic Metabolic Panel'],
            'appropriate_tests' => ['Chest X-ray', 'ECG'],
            'acceptable_tests' => ['Urinalysis'],
            'inappropriate_tests' => ['MRI Brain'],
            'contraindicated_tests' => [],
            'required_tests' => ['Complete Blood Count'],
        ];
    }
}
