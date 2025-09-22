<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\UniversalAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;
use Mockery;
use Tests\TestCase;

class OsceCaseGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_case_from_uploaded_files(): void
    {
        $this->withoutMiddleware(ValidateSessionWithWorkOS::class);

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $aiResponse = [
            'title' => 'Acute Coronary Syndrome Simulation',
            'description' => 'Emergency assessment and management of an adult with suspected ACS.',
            'difficulty' => 'hard',
            'duration_minutes' => 20,
            'scenario' => 'A 58-year-old presents with crushing substernal chest pain radiating to the left arm, diaphoresis, and nausea.',
            'objectives' => 'Rapidly assess chest pain, recognise STEMI, initiate guideline-directed therapy, and coordinate transfer for PCI.',
            'stations' => ['Initial Assessment', 'Focused History', 'Physical Examination', 'Therapy Initiation'],
            'checklist' => ['Assess ABCs', 'Attach cardiac monitor', 'Obtain 12-lead ECG', 'Administer aspirin'],
            'ai_patient_profile' => '58-year-old male office worker with hypertension and dyslipidemia, smoker.',
            'ai_patient_vitals' => [
                'Blood Pressure' => '90/56 mmHg',
                'Heart Rate' => '112 bpm',
                'Respiratory Rate' => '24/min',
                'Oxygen Saturation' => '92%',
                'Temperature' => '37.1°C',
            ],
            'ai_patient_symptoms' => ['Crushing chest pain', 'Shortness of breath', 'Diaphoresis'],
            'ai_patient_instructions' => 'Speak in short sentences between breaths, clutch chest when pain worsens, appear anxious but cooperative.',
            'ai_patient_responses' => [
                'onset' => 'It started about 30 minutes ago while I was carrying boxes.',
                'character' => "It's like an elephant sitting on my chest.",
                'radiation' => 'It shoots down my left arm and up into my jaw.',
            ],
            'expected_anamnesis_questions' => ['Time of onset', 'Radiation of pain', 'Associated symptoms', 'Cardiac risk factors'],
            'red_flags' => ['Hypotension', 'Persistent ST elevation', 'Shortness of breath with diaphoresis'],
            'common_differentials' => ['STEMI', 'NSTEMI', 'Pulmonary embolism'],
            'clinical_setting' => 'emergency',
            'urgency_level' => 5,
            'setting_limitations' => ['cardiac_catheterization' => false, 'thrombolytics_available' => true],
            'case_budget' => 2500.00,
            'highly_appropriate_tests' => ['12-lead ECG', 'Cardiac Troponin I'],
            'appropriate_tests' => ['Chest X-ray'],
            'acceptable_tests' => ['Basic Metabolic Panel'],
            'inappropriate_tests' => ['Lumbar puncture'],
            'contraindicated_tests' => ['Exercise stress test'],
            'required_tests' => ['12-lead ECG'],
            'test_results_templates' => [
                '12-lead ECG' => 'ST elevation in II, III, aVF with reciprocal depression in I and aVL.',
                'Cardiac Troponin I' => 'Elevated at 1.2 ng/mL (normal < 0.04).',
            ],
        ];

        $mock = Mockery::mock(UniversalAIService::class);
        $this->app->instance(UniversalAIService::class, $mock);

        $mock->shouldReceive('generateJson')
            ->once()
            ->withArgs(function (array $schema, string $prompt, array $options) {
                $this->assertArrayHasKey('properties', $schema);
                $this->assertStringContainsString('Source #1', $prompt);
                $this->assertSame(0.2, $options['temperature']);
                $this->assertSame(2048, $options['maxOutputTokens']);
                return true;
            })
            ->andReturn($aiResponse);

        $caseNotes = UploadedFile::fake()->createWithContent('case-notes.txt', 'Chest pain assessment algorithm with vitals and risk factors.');
        $template = UploadedFile::fake()->createWithContent('template.md', '# ACS station\n- Focused history\n- Emergent management');

        $response = $this->withSession(['_token' => 'test-token'])
            ->actingAs($admin)
            ->post(
                route('admin.osce-cases.generate'),
                ['sources' => [$caseNotes, $template], '_token' => 'test-token'],
                ['Accept' => 'application/json', 'X-CSRF-TOKEN' => 'test-token']
            );

        $response->assertOk()
            ->assertJsonPath('data.title', 'Acute Coronary Syndrome Simulation')
            ->assertJsonPath('data.clinical_setting', 'emergency')
            ->assertJsonStructure([
                'data' => [
                    'title',
                    'description',
                    'difficulty',
                    'duration_minutes',
                    'scenario',
                    'objectives',
                    'stations',
                    'checklist',
                    'ai_patient_profile',
                    'ai_patient_vitals',
                    'ai_patient_symptoms',
                    'ai_patient_instructions',
                    'ai_patient_responses',
                    'expected_anamnesis_questions',
                    'red_flags',
                    'common_differentials',
                    'clinical_setting',
                    'urgency_level',
                    'setting_limitations',
                    'case_budget',
                    'highly_appropriate_tests',
                    'appropriate_tests',
                    'acceptable_tests',
                    'inappropriate_tests',
                    'contraindicated_tests',
                    'required_tests',
                    'test_results_templates',
                ],
            ]);
    }

    public function test_non_admin_cannot_generate_cases(): void
    {
        $this->withoutMiddleware(ValidateSessionWithWorkOS::class);

        $user = User::factory()->create(['is_admin' => false]);
        $file = UploadedFile::fake()->createWithContent('notes.txt', 'Sample OSCE content.');

        $response = $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->post(
                route('admin.osce-cases.generate'),
                ['sources' => [$file], '_token' => 'test-token'],
                ['Accept' => 'application/json', 'X-CSRF-TOKEN' => 'test-token']
            );

        $response->assertForbidden();
    }

    public function test_returns_validation_error_when_ai_returns_empty_payload(): void
    {
        $this->withoutMiddleware(ValidateSessionWithWorkOS::class);

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $file = UploadedFile::fake()->createWithContent('notes.txt', 'Respiratory distress case outline.');

        $mock = Mockery::mock(UniversalAIService::class);
        $this->app->instance(UniversalAIService::class, $mock);

        $mock->shouldReceive('generateJson')->once()->andReturn([]);

        $response = $this->withSession(['_token' => 'test-token'])
            ->actingAs($admin)
            ->post(
                route('admin.osce-cases.generate'),
                ['sources' => [$file], '_token' => 'test-token'],
                ['Accept' => 'application/json', 'X-CSRF-TOKEN' => 'test-token']
            );

        $response->assertStatus(422)
            ->assertJsonPath('message', 'The AI provider did not return any OSCE case data.');
    }
}
