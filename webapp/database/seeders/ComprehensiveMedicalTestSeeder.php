<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MedicalTest;

class ComprehensiveMedicalTestSeeder extends Seeder
{
    public function run(): void
    {
        $tests = [
            [
                'name' => 'Basic Metabolic Panel (BMP)',
                'category' => 'Chemistry',
                'type' => 'lab',
                'description' => 'Measures glucose, calcium, and electrolytes. Assesses kidney function.',
                'cost' => 35.00,
                'turnaround_minutes' => 60,
            ],
            [
                'name' => 'Comprehensive Metabolic Panel (CMP)',
                'category' => 'Chemistry',
                'type' => 'lab',
                'description' => 'Includes BMP plus liver function tests and protein levels.',
                'cost' => 45.00,
                'turnaround_minutes' => 60,
            ],
            [
                'name' => 'Lipid Panel',
                'category' => 'Chemistry',
                'type' => 'lab',
                'description' => 'Measures cholesterol and triglycerides to assess cardiovascular risk.',
                'cost' => 40.00,
                'turnaround_minutes' => 120,
            ],
            [
                'name' => 'C-reactive Protein (CRP)',
                'category' => 'Inflammatory Markers',
                'type' => 'lab',
                'description' => 'Measures the level of C-reactive protein to detect inflammation.',
                'cost' => 30.00,
                'turnaround_minutes' => 90,
            ],
            [
                'name' => 'Thyroid-Stimulating Hormone (TSH)',
                'category' => 'Endocrinology',
                'type' => 'lab',
                'description' => 'Evaluates thyroid gland function.',
                'cost' => 50.00,
                'turnaround_minutes' => 120,
            ],
            [
                'name' => 'Hemoglobin A1c (HbA1c)',
                'category' => 'Endocrinology',
                'type' => 'lab',
                'description' => 'Measures average blood sugar levels over the past 2-3 months.',
                'cost' => 35.00,
                'turnaround_minutes' => 120,
            ],
            [
                'name' => 'Prothrombin Time (PT)',
                'category' => 'Coagulation',
                'type' => 'lab',
                'description' => 'Measures how long it takes for a blood clot to form.',
                'cost' => 25.00,
                'turnaround_minutes' => 60,
            ],
            [
                'name' => 'Activated Partial Thromboplastin Time (PTT)',
                'category' => 'Coagulation',
                'type' => 'lab',
                'description' => 'Evaluates the time it takes for blood to clot.',
                'cost' => 25.00,
                'turnaround_minutes' => 60,
            ],
            [
                'name' => 'Urinalysis (UA)',
                'category' => 'Urine',
                'type' => 'lab',
                'description' => 'Examines the visual, chemical, and microscopic aspects of urine.',
                'cost' => 20.00,
                'turnaround_minutes' => 30,
            ],
            [
                'name' => 'Prostate-Specific Antigen (PSA)',
                'category' => 'Tumor Markers',
                'type' => 'lab',
                'description' => 'Screens for prostate cancer.',
                'cost' => 60.00,
                'turnaround_minutes' => 120,
            ],
            [
                'name' => 'Erythrocyte Sedimentation Rate (ESR)',
                'category' => 'Inflammatory Markers',
                'type' => 'lab',
                'description' => 'Reveals inflammatory activity in the body.',
                'cost' => 20.00,
                'turnaround_minutes' => 60,
            ]
        ];

        foreach ($tests as $test) {
            MedicalTest::updateOrCreate(
                ['name' => $test['name']],
                [
                    'category' => $test['category'],
                    'type' => $test['type'],
                    'description' => $test['description'],
                    'indications' => [],
                    'contraindications' => [],
                    'cost' => $test['cost'],
                    'turnaround_minutes' => $test['turnaround_minutes'],
                    'available_settings' => ['all'],
                    'requires_consent' => false,
                    'risk_level' => 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
