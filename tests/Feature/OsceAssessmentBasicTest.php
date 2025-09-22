<?php

use App\Jobs\AssessOsceSessionJob;
use App\Services\AiAssessorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('assessment service can be instantiated', function () {
    $service = app(AiAssessorService::class);
    expect($service)->toBeInstanceOf(AiAssessorService::class);
});

test('assessment job can be instantiated', function () {
    $job = new AssessOsceSessionJob(1);
    expect($job)->toBeInstanceOf(AssessOsceSessionJob::class);
});

test('scoring configuration is loaded correctly', function () {
    $config = config('osce_scoring');

    expect($config)->toBeArray();
    expect($config)->toHaveKeys(['rubric_version', 'criteria', 'penalties']);
    expect($config['rubric_version'])->toBe('RUBRIC_V1.0');
    expect($config['criteria'])->toHaveCount(7);

    foreach ($config['criteria'] as $criterion) {
        expect($criterion)->toHaveKeys(['key', 'label', 'max']);
    }
});

test('ai assessor service detects missing api key', function () {
    // Temporarily unset API key
    config(['services.gemini.api_key' => null]);

    $service = app(AiAssessorService::class);
    expect($service->isConfigured())->toBeFalse();
});
