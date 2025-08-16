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
            ]
        ]);
    }
}
