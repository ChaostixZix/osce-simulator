<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OsceCaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\OsceCase::create([
            'title' => 'Cardiopulmonary Resuscitation (CPR)',
            'description' => 'Basic life support scenario - adult CPR',
            'difficulty' => 'medium',
            'duration_minutes' => 15,
            'scenario' => 'You are called to the emergency department where a 55-year-old male has collapsed. The patient is unresponsive and not breathing normally.',
            'objectives' => 'Demonstrate proper CPR technique, assess patient responsiveness, call for help, perform chest compressions and rescue breathing',
            'stations' => [
                'Assessment and Recognition',
                'Chest Compressions', 
                'Airway Management',
                'Team Communication'
            ],
            'checklist' => [
                'Check responsiveness',
                'Call for help',
                'Check pulse (no more than 10 seconds)',
                'Position hands correctly for compressions',
                'Perform compressions at correct rate (100-120/min)',
                'Allow complete chest recoil',
                'Minimize interruptions',
                'Provide rescue breaths'
            ],
            'ai_patient_profile' => '55-year-old male, John Smith, construction worker. No known medical history. Found unresponsive at construction site.',
            'ai_patient_vitals' => [
                'Blood Pressure' => 'Unmeasurable',
                'Heart Rate' => '0 bpm',
                'Respiratory Rate' => '0/min',
                'Temperature' => '36.8°C',
                'Oxygen Saturation' => 'Unmeasurable'
            ],
            'ai_patient_symptoms' => [
                'Unresponsive',
                'No breathing',
                'No pulse',
                'Pale skin',
                'Dilated pupils'
            ],
            'ai_patient_instructions' => 'Patient is completely unresponsive. No breathing or pulse detected. Respond as a patient who has experienced cardiac arrest and requires immediate CPR.',
            'ai_patient_responses' => [
                'pain' => 'I cannot feel anything. I am not conscious.',
                'breathing' => 'I am not breathing. I need immediate help.',
                'consciousness' => 'I am completely unconscious and unaware of my surroundings.'
            ]
        ]);

        \App\Models\OsceCase::create([
            'title' => 'Asthma Exacerbation Management',
            'description' => 'Acute asthma attack in emergency setting',
            'difficulty' => 'hard',
            'duration_minutes' => 20,
            'scenario' => 'A 12-year-old child presents to the emergency department with severe shortness of breath, wheezing, and difficulty speaking in full sentences.',
            'objectives' => 'Assess severity of asthma attack, provide appropriate treatment, monitor response to therapy',
            'stations' => [
                'Initial Assessment',
                'Medication Administration',
                'Monitoring and Reassessment',
                'Patient Education'
            ],
            'checklist' => [
                'Assess respiratory distress',
                'Check vital signs',
                'Administer bronchodilator',
                'Provide oxygen if needed',
                'Monitor response to treatment',
                'Educate on inhaler technique',
                'Assess need for corticosteroids',
                'Document findings'
            ],
            'ai_patient_profile' => '12-year-old female, Sarah Johnson, student. Known history of asthma since age 6. Uses albuterol inhaler as needed.',
            'ai_patient_vitals' => [
                'Blood Pressure' => '110/70 mmHg',
                'Heart Rate' => '120 bpm',
                'Respiratory Rate' => '32/min',
                'Temperature' => '37.2°C',
                'Oxygen Saturation' => '88%'
            ],
            'ai_patient_symptoms' => [
                'Severe shortness of breath',
                'Wheezing',
                'Difficulty speaking',
                'Chest tightness',
                'Anxiety',
                'Use of accessory muscles'
            ],
            'ai_patient_instructions' => 'Patient is experiencing severe asthma exacerbation. Respond as a frightened child who is struggling to breathe and speak. Be cooperative but anxious.',
            'ai_patient_responses' => [
                'breathing' => 'I can\'t breathe! It feels like someone is sitting on my chest!',
                'medication' => 'I used my inhaler but it didn\'t help much. I\'m scared!',
                'pain' => 'My chest feels tight and it hurts when I try to breathe.',
                'history' => 'I\'ve had asthma since I was little. This is the worst it\'s ever been.'
            ]
        ]);

        \App\Models\OsceCase::create([
            'title' => 'Blood Pressure Measurement',
            'description' => 'Proper technique for measuring blood pressure',
            'difficulty' => 'easy',
            'duration_minutes' => 10,
            'scenario' => 'You need to measure blood pressure for a routine health check on a 45-year-old patient.',
            'objectives' => 'Demonstrate proper blood pressure measurement technique using manual sphygmomanometer',
            'stations' => [
                'Equipment Preparation',
                'Patient Positioning',
                'Measurement Technique',
                'Documentation'
            ],
            'checklist' => [
                'Select appropriate cuff size',
                'Position patient correctly',
                'Locate brachial pulse',
                'Inflate cuff properly',
                'Deflate at correct rate (2-3 mmHg/sec)',
                'Identify systolic pressure',
                'Identify diastolic pressure',
                'Record measurement accurately'
            ],
            'ai_patient_profile' => '45-year-old female, Maria Rodriguez, office worker. No significant medical history. Slightly anxious about medical procedures.',
            'ai_patient_vitals' => [
                'Blood Pressure' => 'To be measured',
                'Heart Rate' => '78 bpm',
                'Respiratory Rate' => '16/min',
                'Temperature' => '36.9°C',
                'Oxygen Saturation' => '98%'
            ],
            'ai_patient_symptoms' => [
                'Mild anxiety',
                'No current symptoms',
                'Routine check-up'
            ],
            'ai_patient_instructions' => 'Patient is healthy but slightly nervous about medical procedures. Be cooperative and ask questions about the procedure. Respond naturally to questions.',
            'ai_patient_responses' => [
                'anxiety' => 'I\'m a little nervous about medical procedures. Will this hurt?',
                'health' => 'I feel fine overall. I exercise regularly and eat well.',
                'history' => 'No major health problems. I had my appendix removed when I was 20.',
                'medication' => 'I only take a daily vitamin. No prescription medications.'
            ]
        ]);

        \App\Models\OsceCase::create([
            'title' => 'Chest Pain Assessment',
            'description' => 'Evaluation of acute chest pain in emergency setting',
            'difficulty' => 'hard',
            'duration_minutes' => 25,
            'scenario' => 'A 62-year-old male presents to the emergency department with acute onset chest pain that started 2 hours ago.',
            'objectives' => 'Assess chest pain characteristics, perform focused physical exam, order appropriate tests, provide initial management',
            'stations' => [
                'Pain Assessment',
                'Physical Examination',
                'Diagnostic Testing',
                'Treatment Planning'
            ],
            'checklist' => [
                'Assess pain characteristics (PQRST)',
                'Check vital signs',
                'Perform focused cardiac exam',
                'Order ECG and cardiac enzymes',
                'Assess risk factors',
                'Provide pain relief',
                'Consider aspirin administration',
                'Document findings'
            ],
            'ai_patient_profile' => '62-year-old male, Robert Wilson, retired teacher. History of hypertension and high cholesterol. Current smoker (1 pack/day for 30 years).',
            'ai_patient_vitals' => [
                'Blood Pressure' => '160/95 mmHg',
                'Heart Rate' => '110 bpm',
                'Respiratory Rate' => '22/min',
                'Temperature' => '37.1°C',
                'Oxygen Saturation' => '95%'
            ],
            'ai_patient_symptoms' => [
                'Severe chest pain',
                'Pain radiating to left arm',
                'Shortness of breath',
                'Nausea',
                'Sweating',
                'Anxiety'
            ],
            'ai_patient_instructions' => 'Patient is experiencing severe chest pain consistent with possible myocardial infarction. Be in significant distress but cooperative. Describe pain vividly.',
            'ai_patient_responses' => [
                'pain' => 'The pain is crushing! Like an elephant sitting on my chest. It\'s the worst pain I\'ve ever felt!',
                'radiation' => 'It started in my chest and now it\'s going down my left arm. My jaw hurts too.',
                'onset' => 'It started about 2 hours ago while I was watching TV. It came on suddenly.',
                'history' => 'I have high blood pressure and cholesterol. My doctor warned me about this.'
            ],

            // New clinical reasoning fields
            'clinical_setting' => 'emergency',
            'urgency_level' => 5,
            'highly_appropriate_tests' => [
                'Troponin I',
                'Electrocardiogram (ECG)',
                'Chest X-Ray'
            ],
            'appropriate_tests' => [
                'Complete Blood Count (CBC)'
            ],
            'acceptable_tests' => [
            ],
            'inappropriate_tests' => [
                'Thyroid Stimulating Hormone',
                'Prostate Specific Antigen',
                'Stool Culture',
                'Pap Smear'
            ],
            'contraindicated_tests' => [
                'Exercise Stress Test'
            ],
            'required_tests' => [
                'Troponin I',
                'Electrocardiogram (ECG)'
            ],
            'setting_limitations' => [
                'cardiac_catheterization' => false,
                'advanced_imaging' => true,
                'specialist_availability' => ['emergency_medicine', 'cardiology_consult']
            ],
            'case_budget' => 2000.00,
            'test_results_templates' => [
                // These IDs will correspond after seeding medical tests; using names in controller resolution as fallback
            ]
        ]);
    }
}
