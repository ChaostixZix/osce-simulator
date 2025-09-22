<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OsceSession;
use App\Services\AiAssessorService;
use App\Services\AreaAssessor;
use App\Models\AiAssessmentAreaResult;

class ProbeGeminiForSession extends Command
{
    protected $signature = 'ai:probe {sessionId} {--areas : Probe each clinical area instead of holistic}';

    protected $description = 'Probe Gemini API for a given OSCE session and print raw responses or errors.';

    public function handle(): int
    {
        $sessionId = (int) $this->argument('sessionId');
        $byAreas = (bool) $this->option('areas');

        $session = OsceSession::with(['osceCase','chatMessages','orderedTests.medicalTest','examinations'])->find($sessionId);
        if (!$session) {
            $this->error("Session {$sessionId} not found");
            return self::FAILURE;
        }

        $this->info("Probing Gemini for session {$sessionId} (case: " . ($session->osceCase->title ?? 'unknown') . ")");

        // Build artifact once
        $assessor = app(AiAssessorService::class);
        $artifact = $assessor->buildArtifact($session);

        if ($byAreas) {
            $this->line('Mode: per-area schema probes');
            $areaAssessor = app(AreaAssessor::class);
            $areas = array_keys(AiAssessmentAreaResult::CLINICAL_AREAS);
            foreach ($areas as $area) {
                $this->newLine();
                $this->info("Area: {$area}");
                try {
                    $r = (new \ReflectionClass($areaAssessor))->getMethod('callGeminiForArea');
                    $r->setAccessible(true);
                    $response = $r->invoke($areaAssessor, $artifact, $area);
                    $text = $response['text'] ?? '';
                    $len = strlen($text);
                    $this->line("HTTP Status: ".$response['status_code']);
                    $this->line("Response length: {$len}");
                    $this->line("Preview: ".substr($text, 0, 400));
                } catch (\Throwable $e) {
                    $this->error('Error: '.$e->getMessage());
                }
            }
        } else {
            $this->line('Mode: holistic session scoring probe');
            try {
                $m = (new \ReflectionClass($assessor))->getMethod('callGeminiForSessionScoring');
                $m->setAccessible(true);
                $decoded = $m->invoke($assessor, $artifact, $session);
                $this->line('Decoded keys: '.implode(', ', array_keys($decoded)));
                $this->line('total_score: '.($decoded['total_score'] ?? 'n/a').' / '.($decoded['max_possible_score'] ?? 'n/a'));
            } catch (\Throwable $e) {
                $this->error('Error: '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}

