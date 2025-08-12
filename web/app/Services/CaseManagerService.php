<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CaseManagerService
{
    protected $casesDirectory = 'cases';
    protected $cases = [];
    protected $caseList = [];
    
    public function __construct()
    {
        // Loading statistics
        $this->loadingStats = [
            'totalAttempts' => 0,
            'successfulLoads' => 0,
            'failedLoads' => 0,
            'validationErrors' => 0
        ];
    }

    /**
     * Discover and load all available case files
     */
    public function loadAvailableCases()
    {
        $startTime = microtime(true);
        $this->loadingStats['totalAttempts']++;

        try {
            $this->caseList = [];
            $this->cases = [];

            Log::info('Starting case discovery and loading', ['directory' => $this->casesDirectory]);

            // Get all JSON files from the cases directory
            $files = Storage::files($this->casesDirectory);
            $jsonFiles = array_filter($files, function($file) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'json' && basename($file) !== 'case-schema.json';
            });

            if (empty($jsonFiles)) {
                Log::warning('No case files found in cases directory', ['directory' => $this->casesDirectory]);
                return [];
            }

            Log::info('Found ' . count($jsonFiles) . ' potential case files', ['files' => $jsonFiles]);

            $successfullyLoaded = [];
            foreach ($jsonFiles as $file) {
                try {
                    $caseData = $this->loadSingleCaseFile($file);
                    if ($caseData) {
                        $successfullyLoaded[] = $caseData;
                        $this->cases[$caseData['id']] = $caseData;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to load case file', ['file' => $file, 'error' => $e->getMessage()]);
                    $this->loadingStats['failedLoads']++;
                }
            }

            $this->caseList = $successfullyLoaded;
            $this->loadingStats['successfulLoads'] = count($successfullyLoaded);

            $duration = (microtime(true) - $startTime) * 1000;
            Log::info('Case loading completed', [
                'loaded' => count($successfullyLoaded),
                'failed' => $this->loadingStats['failedLoads'],
                'duration' => $duration . 'ms'
            ]);

            return $this->caseList;

        } catch (\Exception $error) {
            $duration = (microtime(true) - $startTime) * 1000;
            Log::error('Failed to load cases', [
                'error' => $error->getMessage(),
                'duration' => $duration . 'ms'
            ]);

            throw $error;
        }
    }

    /**
     * Load and validate a single case file
     */
    protected function loadSingleCaseFile($filePath)
    {
        try {
            $content = Storage::get($filePath);
            $caseData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }

            // Basic validation
            if (!isset($caseData['id']) || !isset($caseData['title'])) {
                throw new \Exception('Missing required fields: id or title');
            }

            Log::info('Successfully loaded case', ['id' => $caseData['id'], 'title' => $caseData['title']]);

            return [
                'id' => $caseData['id'],
                'title' => $caseData['title'],
                'description' => $caseData['description'] ?? '',
                'filePath' => $filePath,
                'data' => $caseData
            ];

        } catch (\Exception $error) {
            Log::error('Failed to load case file', ['file' => $filePath, 'error' => $error->getMessage()]);
            throw $error;
        }
    }

    /**
     * Get a specific case by ID
     */
    public function getCaseById($caseId)
    {
        if (!isset($this->cases[$caseId])) {
            // Try to load cases if not already loaded
            if (empty($this->cases)) {
                $this->loadAvailableCases();
            }
        }

        if (!isset($this->cases[$caseId])) {
            throw new \Exception("Case not found: {$caseId}");
        }

        return $this->cases[$caseId];
    }

    /**
     * Format case selection message
     */
    public function formatCaseSelectionMessage()
    {
        if (empty($this->caseList)) {
            return "No OSCE cases are currently available.";
        }

        $message = "🏥 Welcome to OSCE Medical Training System\n\n";
        $message .= "📚 Available Cases:\n";
        $message .= "═══════════════════════════════════════\n\n";

        foreach ($this->caseList as $case) {
            $message .= "🔹 Case ID: {$case['id']}\n";
            $message .= "   Title: {$case['title']}\n";
            $message .= "   Description: {$case['description']}\n\n";
        }

        $message .= "═══════════════════════════════════════\n";
        $message .= "📝 To start a case, type the Case ID\n";
        $message .= "💡 Type 'help' for additional commands\n";

        return $message;
    }

    /**
     * Get loading statistics
     */
    public function getLoadingStats()
    {
        return $this->loadingStats;
    }
}