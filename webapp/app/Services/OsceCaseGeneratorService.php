<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class OsceCaseGeneratorService
{
    private const MAX_FILE_CONTENT_LENGTH = 8000;

    public function __construct(private readonly UniversalAIService $aiService)
    {
    }

    /**
     * Generate a structured OSCE case payload using uploaded reference files.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function generateFromUploads(array $files, ?string $instructions = null): array
    {
        $sources = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $content = $this->extractContent($file);
            $sources[] = [
                'name' => $file->getClientOriginalName() ?: $file->getFilename(),
                'content' => $content,
            ];
        }

        if (empty($sources)) {
            throw new RuntimeException('We could not read any usable content from the uploaded files.');
        }

        $prompt = $this->buildPrompt($sources, $instructions);
        $schema = $this->caseSchema();
        $options = [
            'temperature' => 0.2,
            'maxOutputTokens' => 2048,
        ];

        $payload = $this->aiService->generateJson($schema, $prompt, $options);

        if (empty($payload)) {
            throw new RuntimeException('The AI provider did not return any OSCE case data.');
        }

        return $this->normalizePayload($payload);
    }

    /**
     * @param  array{name: string, content: string}  $sources
     */
    private function buildPrompt(array $sources, ?string $instructions): string
    {
        $overview = <<<PROMPT
You are an experienced clinical educator building a complete Objective Structured Clinical Examination (OSCE) case for simulation training. Use the uploaded reference material to craft a coherent scenario.

Return **only** JSON that obeys the provided JSON schema. Populate every field with realistic, concise content. Ensure that:
- Vital signs and symptoms reflect the same clinical story.
- Physical examination findings align with the prompt and objectives.
- Tests are categorised into the correct appropriateness buckets.
- AI patient responses use short first-person phrases the virtual patient would speak.
PROMPT;

        if ($instructions) {
            $overview .= "\n\nAdditional author notes from the admin: {$instructions}";
        }

        $overview .= "\n\nJSON Schema (obey required types):\n".json_encode($this->caseSchema(), JSON_PRETTY_PRINT);

        $sourceText = collect($sources)
            ->map(fn ($source, $index) => 'Source #'.($index + 1).' - '.$source['name']."\n".$source['content'])
            ->implode("\n\n---\n\n");

        return $overview."\n\nSource Material:\n\n".$sourceText;
    }

    private function extractContent(UploadedFile $file): string
    {
        $mime = $file->getMimeType() ?? '';

        $content = match (true) {
            str_starts_with($mime, 'text/') || $mime === 'application/json' => $this->readTextFile($file),
            $mime === 'application/pdf' => $this->extractPdfText($file),
            default => throw new RuntimeException("Unsupported file type: {$mime}"),
        };

        $content = trim(preg_replace('/\s+/', ' ', $content));

        if ($content === '') {
            throw new RuntimeException('One of the files did not contain readable text.');
        }

        return Str::limit($content, self::MAX_FILE_CONTENT_LENGTH, ' […]');
    }

    private function readTextFile(UploadedFile $file): string
    {
        if (method_exists($file, 'getContent')) {
            $content = $file->getContent();
            if (is_string($content) && $content !== '') {
                return $content;
            }
        }

        if (method_exists($file, 'getStream')) {
            $stream = $file->getStream();
            if ($stream) {
                $data = stream_get_contents($stream);
                if ($data !== false && $data !== '') {
                    return $data;
                }
            }
        }

        $path = $file->getRealPath();
        if ($path && is_file($path)) {
            $data = file_get_contents($path);
            if ($data !== false) {
                return $data;
            }
        }

        return '';
    }

    private function extractPdfText(UploadedFile $file): string
    {
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file->getRealPath());

            return $pdf->getText();
        }

        throw new RuntimeException('PDF parsing requires the smalot/pdfparser package.');
    }

    private function normalizePayload(array $payload): array
    {
        $defaults = [
            'title' => '',
            'description' => '',
            'difficulty' => 'medium',
            'duration_minutes' => 15,
            'scenario' => '',
            'objectives' => '',
            'stations' => [],
            'checklist' => [],
            'ai_patient_profile' => '',
            'ai_patient_vitals' => [],
            'ai_patient_symptoms' => [],
            'ai_patient_instructions' => '',
            'ai_patient_responses' => [],
            'expected_anamnesis_questions' => [],
            'red_flags' => [],
            'common_differentials' => [],
            'clinical_setting' => 'clinic',
            'urgency_level' => 3,
            'setting_limitations' => [],
            'case_budget' => null,
            'highly_appropriate_tests' => [],
            'appropriate_tests' => [],
            'acceptable_tests' => [],
            'inappropriate_tests' => [],
            'contraindicated_tests' => [],
            'required_tests' => [],
            'test_results_templates' => [],
        ];

        $normalized = array_merge($defaults, Arr::only($payload, array_keys($defaults)));

        foreach ([
            'stations',
            'checklist',
            'ai_patient_symptoms',
            'expected_anamnesis_questions',
            'red_flags',
            'common_differentials',
            'highly_appropriate_tests',
            'appropriate_tests',
            'acceptable_tests',
            'inappropriate_tests',
            'contraindicated_tests',
            'required_tests',
        ] as $arrayKey) {
            $normalized[$arrayKey] = $this->stringArray($payload[$arrayKey] ?? []);
        }

        $normalized['ai_patient_vitals'] = $this->normalizeKeyValueArray($payload['ai_patient_vitals'] ?? []);
        $normalized['ai_patient_responses'] = $this->normalizeKeyValueArray($payload['ai_patient_responses'] ?? []);
        $normalized['setting_limitations'] = $this->normalizeKeyValueArray($payload['setting_limitations'] ?? []);
        $normalized['test_results_templates'] = $this->normalizeNestedTemplates($payload['test_results_templates'] ?? []);

        $normalized['title'] = (string) ($normalized['title'] ?: 'Untitled OSCE Case');
        $normalized['description'] = (string) $normalized['description'];
        $normalized['scenario'] = (string) $normalized['scenario'];
        $normalized['objectives'] = (string) $normalized['objectives'];
        $normalized['ai_patient_profile'] = (string) $normalized['ai_patient_profile'];
        $normalized['ai_patient_instructions'] = (string) $normalized['ai_patient_instructions'];
        $normalized['difficulty'] = in_array($normalized['difficulty'], ['easy', 'medium', 'hard'], true)
            ? $normalized['difficulty']
            : 'medium';
        $normalized['duration_minutes'] = (int) $normalized['duration_minutes'] ?: 15;
        $normalized['urgency_level'] = (int) $normalized['urgency_level'] ?: 3;

        return $normalized;
    }

    private function stringArray(mixed $value): array
    {
        return collect(Arr::wrap($value))
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn ($item) => trim((string) $item))
            ->values()
            ->all();
    }

    private function normalizeKeyValueArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];

        // Handle associative array directly
        foreach ($value as $key => $val) {
            if (is_string($key) && ! is_numeric($key)) {
                $result[trim((string) $key)] = is_scalar($val) ? trim((string) $val) : json_encode($val);
            }
        }

        // Handle list style [{"name": "", "value": ""}] or ["label" => ""]
        foreach ($value as $item) {
            if (is_array($item)) {
                $name = $item['name'] ?? $item['label'] ?? $item['key'] ?? null;
                $val = $item['value'] ?? $item['content'] ?? null;

                if ($name && $val) {
                    $result[trim((string) $name)] = trim((string) $val);
                }
            }
        }

        return $result;
    }

    private function normalizeNestedTemplates(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $templates = [];

        foreach ($value as $key => $template) {
            $name = is_string($key) && ! is_numeric($key)
                ? trim($key)
                : (is_array($template) ? ($template['name'] ?? $template['test'] ?? null) : null);

            if (! $name) {
                continue;
            }

            if (is_string($template)) {
                $templates[$name] = trim($template);
                continue;
            }

            if (is_array($template)) {
                $valueContent = $template['value'] ?? $template['result'] ?? $template['content'] ?? null;
                if (is_string($valueContent)) {
                    $templates[$name] = trim($valueContent);
                    continue;
                }

                $templates[$name] = array_map(
                    fn ($val) => is_string($val) ? trim($val) : $val,
                    $template
                );
                continue;
            }

            $templates[$name] = $template;
        }

        return $templates;
    }

    private function caseSchema(): array
    {
        return [
            'type' => 'object',
            'required' => [
                'title',
                'description',
                'difficulty',
                'duration_minutes',
                'scenario',
                'objectives',
                'ai_patient_profile',
                'ai_patient_vitals',
                'ai_patient_symptoms',
                'ai_patient_instructions',
                'ai_patient_responses',
                'expected_anamnesis_questions',
                'red_flags',
                'common_differentials',
                'highly_appropriate_tests',
                'appropriate_tests',
                'acceptable_tests',
                'inappropriate_tests',
                'contraindicated_tests',
                'required_tests',
            ],
            'properties' => [
                'title' => ['type' => 'string', 'minLength' => 8],
                'description' => ['type' => 'string', 'minLength' => 16],
                'difficulty' => ['type' => 'string', 'enum' => ['easy', 'medium', 'hard']],
                'duration_minutes' => ['type' => 'integer', 'minimum' => 5, 'maximum' => 60],
                'scenario' => ['type' => 'string'],
                'objectives' => ['type' => 'string'],
                'stations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'checklist' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'ai_patient_profile' => ['type' => 'string'],
                'ai_patient_vitals' => [
                    'type' => 'object',
                    'additionalProperties' => ['type' => 'string'],
                ],
                'ai_patient_symptoms' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'ai_patient_instructions' => ['type' => 'string'],
                'ai_patient_responses' => [
                    'type' => 'object',
                    'additionalProperties' => ['type' => 'string'],
                ],
                'expected_anamnesis_questions' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'red_flags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'common_differentials' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'clinical_setting' => ['type' => 'string'],
                'urgency_level' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5],
                'setting_limitations' => [
                    'type' => 'object',
                    'additionalProperties' => ['type' => ['string', 'boolean']],
                ],
                'case_budget' => ['type' => ['number', 'null']],
                'highly_appropriate_tests' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'appropriate_tests' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'acceptable_tests' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'inappropriate_tests' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'contraindicated_tests' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'required_tests' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'test_results_templates' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => ['string', 'object'],
                    ],
                ],
            ],
        ];
    }
}
