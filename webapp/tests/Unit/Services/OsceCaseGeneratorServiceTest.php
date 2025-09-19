<?php

namespace Tests\Unit\Services;

use App\Services\OsceCaseGeneratorService;
use App\Services\UniversalAIService;
use Illuminate\Http\UploadedFile;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class OsceCaseGeneratorServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_from_uploads_normalizes_payload(): void
    {
        $aiResponse = [
            'title' => 'Sepsis Bundle Training',
            'description' => 'Recognise and manage early septic shock in the emergency department.',
            'difficulty' => 'medium',
            'duration_minutes' => '25',
            'scenario' => 'A 42-year-old presents with fever, rigors, confusion, and hypotension after a urinary tract infection.',
            'objectives' => 'Identify sepsis criteria, start broad-spectrum antibiotics, begin fluid resuscitation, escalate care.',
            'stations' => ['Primary Survey'],
            'checklist' => ['Measure lactate'],
            'ai_patient_profile' => '42-year-old female retail worker with type 2 diabetes.',
            'ai_patient_vitals' => [
                'Blood Pressure' => '86/52 mmHg',
                ['name' => 'Heart Rate', 'value' => '118 bpm'],
                ['label' => 'Temperature', 'value' => '38.9°C'],
            ],
            'ai_patient_symptoms' => ['Shaking chills', 'Confusion'],
            'ai_patient_instructions' => 'Speak slowly and appear lethargic; become more confused if hypotension persists.',
            'ai_patient_responses' => [
                ['label' => 'pain', 'value' => 'My whole body aches.'],
                'history' => 'I had a urinary tract infection last week.',
            ],
            'expected_anamnesis_questions' => ['Recent infections', 'Immunosuppression', 'Medication history'],
            'red_flags' => ['Mean arterial pressure below 65 mmHg'],
            'common_differentials' => ['Septic shock', 'Severe pneumonia'],
            'clinical_setting' => 'emergency',
            'urgency_level' => '4',
            'setting_limitations' => [
                ['name' => 'ct_imaging', 'value' => 'limited after hours'],
            ],
            'case_budget' => '1850.75',
            'highly_appropriate_tests' => ['Blood cultures', 'Serum lactate'],
            'appropriate_tests' => ['Chest X-ray'],
            'acceptable_tests' => [],
            'inappropriate_tests' => ['Elective lipid panel'],
            'contraindicated_tests' => [],
            'required_tests' => ['Serum lactate'],
            'test_results_templates' => [
                ['name' => 'Complete Blood Count', 'value' => 'WBC 19.2 x10^9/L with left shift.'],
            ],
        ];

        $mock = Mockery::mock(UniversalAIService::class);
        $mock->shouldReceive('generateJson')->once()->andReturn($aiResponse);

        $service = new OsceCaseGeneratorService($mock);

        $file = UploadedFile::fake()->createWithContent('sepsis.md', 'Sepsis protocol including vitals, resuscitation steps.');
        $result = $service->generateFromUploads([$file]);

        $this->assertSame('Sepsis Bundle Training', $result['title']);
        $this->assertSame(25, $result['duration_minutes']);
        $this->assertSame(4, $result['urgency_level']);
        $this->assertSame('medium', $result['difficulty']);
        $this->assertEquals([
            'Blood Pressure' => '86/52 mmHg',
            'Heart Rate' => '118 bpm',
            'Temperature' => '38.9°C',
        ], $result['ai_patient_vitals']);
        $this->assertEquals([
            'pain' => 'My whole body aches.',
            'history' => 'I had a urinary tract infection last week.',
        ], $result['ai_patient_responses']);
        $this->assertEquals([
            'ct_imaging' => 'limited after hours',
        ], $result['setting_limitations']);
        $this->assertEquals([
            'Complete Blood Count' => 'WBC 19.2 x10^9/L with left shift.',
        ], $result['test_results_templates']);
        $this->assertNotEmpty($result['stations']);
        $this->assertNotEmpty($result['checklist']);
    }

    public function test_throws_exception_when_no_content_can_be_extracted(): void
    {
        $mock = Mockery::mock(UniversalAIService::class);
        $service = new OsceCaseGeneratorService($mock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('One of the files did not contain readable text.');

        $empty = UploadedFile::fake()->createWithContent('empty.txt', '   ');
        $service->generateFromUploads([$empty]);
    }
}
