<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MedicalTest;

class MedicalTestSeeder extends Seeder
{
    public function run(): void
    {
        MedicalTest::updateOrCreate(
            ['name' => 'Troponin I'],
            [
                'category' => 'Cardiac Enzymes',
                'type' => 'lab',
                'description' => 'Highly sensitive marker for myocardial cell damage. Essential for MI diagnosis.',
                'indications' => [
                    'Chest pain suspicious for acute coronary syndrome',
                    'Shortness of breath with cardiac symptoms',
                    'Post-cardiac procedure monitoring',
                    'Unexplained heart failure'
                ],
                'contraindications' => [
                    'Clearly non-cardiac chest pain (e.g., trauma, musculoskeletal)',
                    'Routine screening without symptoms'
                ],
                'cost' => 45.00,
                'turnaround_minutes' => 60,
                'available_settings' => ['emergency', 'inpatient', 'outpatient'],
                'requires_consent' => false,
                'risk_level' => 1,
                'is_active' => true,
            ]
        );

        MedicalTest::updateOrCreate(
            ['name' => 'Electrocardiogram (ECG)'],
            [
                'category' => 'Cardiology',
                'type' => 'procedure',
                'description' => 'Non-invasive test recording electrical activity of the heart.',
                'indications' => [
                    'Chest pain evaluation',
                    'Arrhythmia assessment',
                    'Syncope workup'
                ],
                'contraindications' => [],
                'cost' => 100.00,
                'turnaround_minutes' => 10,
                'available_settings' => ['all'],
                'requires_consent' => false,
                'risk_level' => 1,
                'is_active' => true,
            ]
        );

        MedicalTest::updateOrCreate(
            ['name' => 'Chest X-Ray'],
            [
                'category' => 'Imaging',
                'type' => 'imaging',
                'description' => 'Radiographic imaging of the chest to evaluate lungs, heart, and bones.',
                'indications' => [
                    'Chest pain', 'Shortness of breath', 'Trauma'
                ],
                'contraindications' => ['Pregnancy (relative)'],
                'cost' => 150.00,
                'turnaround_minutes' => 45,
                'available_settings' => ['emergency', 'inpatient', 'outpatient'],
                'requires_consent' => false,
                'risk_level' => 2,
                'is_active' => true,
            ]
        );

        MedicalTest::updateOrCreate(
            ['name' => 'Complete Blood Count (CBC)'],
            [
                'category' => 'Hematology',
                'type' => 'lab',
                'description' => 'Comprehensive blood cell analysis including WBC, RBC, platelets.',
                'indications' => [
                    'Infection screening', 'Anemia evaluation', 'Bleeding assessment', 'Medication monitoring'
                ],
                'contraindications' => [],
                'cost' => 25.00,
                'turnaround_minutes' => 45,
                'available_settings' => ['all'],
                'requires_consent' => false,
                'risk_level' => 1,
                'is_active' => true,
            ]
        );

        MedicalTest::updateOrCreate(
            ['name' => 'Exercise Stress Test'],
            [
                'category' => 'Cardiac Function Tests',
                'type' => 'procedure',
                'description' => 'Evaluates cardiac function under physical stress.',
                'indications' => [
                    'Stable chest pain evaluation', 'Pre-operative cardiac assessment', 'Follow-up after cardiac intervention'
                ],
                'contraindications' => [
                    'Acute myocardial infarction', 'Unstable angina', 'Severe heart failure', 'Significant arrhythmias'
                ],
                'cost' => 850.00,
                'turnaround_minutes' => 180,
                'available_settings' => ['outpatient', 'cardiology_clinic'],
                'requires_consent' => true,
                'risk_level' => 4,
                'is_active' => true,
            ]
        );
    }
}

