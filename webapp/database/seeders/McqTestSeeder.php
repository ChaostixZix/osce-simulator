<?php

namespace Database\Seeders;

use App\Models\McqTest;
use App\Models\McqQuestion;
use App\Models\McqOption;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class McqTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Cardiology 1 test with questions
        $cardiology1 = McqTest::create([
            'title' => 'Cardiology 1',
            'description' => 'Basic cardiovascular medicine concepts and common cardiac conditions.',
        ]);

        // Question 1 for Cardiology 1
        $question1 = McqQuestion::create([
            'mcq_test_id' => $cardiology1->id,
            'question' => 'Which of the following is the most common cause of heart failure with reduced ejection fraction?',
            'order' => 1,
        ]);

        McqOption::create([
            'mcq_question_id' => $question1->id,
            'option_text' => 'Coronary artery disease',
            'is_correct' => true,
            'order' => 1,
        ]);

        McqOption::create([
            'mcq_question_id' => $question1->id,
            'option_text' => 'Hypertension',
            'is_correct' => false,
            'order' => 2,
        ]);

        McqOption::create([
            'mcq_question_id' => $question1->id,
            'option_text' => 'Valvular heart disease',
            'is_correct' => false,
            'order' => 3,
        ]);

        McqOption::create([
            'mcq_question_id' => $question1->id,
            'option_text' => 'Cardiomyopathy',
            'is_correct' => false,
            'order' => 4,
        ]);

        // Question 2 for Cardiology 1
        $question2 = McqQuestion::create([
            'mcq_test_id' => $cardiology1->id,
            'question' => 'What is the first-line treatment for acute ST-elevation myocardial infarction (STEMI)?',
            'order' => 2,
        ]);

        McqOption::create([
            'mcq_question_id' => $question2->id,
            'option_text' => 'Thrombolytic therapy',
            'is_correct' => false,
            'order' => 1,
        ]);

        McqOption::create([
            'mcq_question_id' => $question2->id,
            'option_text' => 'Primary percutaneous coronary intervention (PCI)',
            'is_correct' => true,
            'order' => 2,
        ]);

        McqOption::create([
            'mcq_question_id' => $question2->id,
            'option_text' => 'Coronary artery bypass grafting',
            'is_correct' => false,
            'order' => 3,
        ]);

        McqOption::create([
            'mcq_question_id' => $question2->id,
            'option_text' => 'Medical management only',
            'is_correct' => false,
            'order' => 4,
        ]);

        // Question 3 for Cardiology 1
        $question3 = McqQuestion::create([
            'mcq_test_id' => $cardiology1->id,
            'question' => 'Which medication class is considered the cornerstone of heart failure management?',
            'order' => 3,
        ]);

        McqOption::create([
            'mcq_question_id' => $question3->id,
            'option_text' => 'Beta-blockers',
            'is_correct' => false,
            'order' => 1,
        ]);

        McqOption::create([
            'mcq_question_id' => $question3->id,
            'option_text' => 'ACE inhibitors/ARBs',
            'is_correct' => true,
            'order' => 2,
        ]);

        McqOption::create([
            'mcq_question_id' => $question3->id,
            'option_text' => 'Diuretics',
            'is_correct' => false,
            'order' => 3,
        ]);

        McqOption::create([
            'mcq_question_id' => $question3->id,
            'option_text' => 'Calcium channel blockers',
            'is_correct' => false,
            'order' => 4,
        ]);

        // Create other test categories without questions for now
        McqTest::create([
            'title' => 'Cardiology 2',
            'description' => 'Advanced cardiovascular medicine including complex arrhythmias and interventional cardiology.',
        ]);

        McqTest::create([
            'title' => 'Cardiology 3',
            'description' => 'Specialized cardiovascular topics including heart failure management and cardiac imaging.',
        ]);
    }
}
