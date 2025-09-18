<?php

namespace App\Services;

use App\Models\AiAssessmentRun;
use App\Models\AiAssessmentAreaResult;
use Illuminate\Support\Facades\Log;

class ResultReducer
{
    /**
     * Aggregate area results into a final assessment
     */
    public function aggregateResults(AiAssessmentRun $assessmentRun): array
    {
        Log::info('ResultReducer starting aggregation', [
            'run_id' => $assessmentRun->id,
            'session_id' => $assessmentRun->osce_session_id
        ]);

        $areaResults = $assessmentRun->areaResults;
        
        if ($areaResults->isEmpty()) {
            Log::error('No area results found for aggregation', [
                'run_id' => $assessmentRun->id
            ]);
            
            return $this->createEmptyResult();
        }

        // Calculate totals
        $totalScore = $areaResults->sum('score');
        $maxPossibleScore = $areaResults->sum('max_score');
        
        // Build clinical areas array
        $clinicalAreas = [];
        foreach ($areaResults as $result) {
            $outline = [];
            $citations = [];
            // Try to parse extra fields (outline, citations) from raw_response JSON
            try {
                $rawText = is_array($result->raw_response) ? ($result->raw_response['text'] ?? null) : null;
                if ($rawText && json_validate($rawText)) {
                    $decoded = json_decode($rawText, true);
                    if (isset($decoded['outline']) && is_array($decoded['outline'])) {
                        // Normalize outline to strings
                        $outline = array_values(array_filter(array_map('strval', $decoded['outline'])));
                    }
                    if (isset($decoded['citations']) && is_array($decoded['citations'])) {
                        $citations = $this->normalizeCitations($decoded['citations']);
                    }
                }
            } catch (\Throwable $e) {
                // Ignore parse errors silently; outline/citations remain empty
            }

            $clinicalAreas[] = [
                'area' => $result->area_display_name,
                'key' => $result->clinical_area,
                'score' => $result->score ?? 0,
                'max_score' => $result->max_score,
                'justification' => $result->justification ?? 'No assessment available',
                'outline' => $outline,
                'citations' => $citations,
                'status' => $result->status,
                'badge_color' => $result->badge_color,
                'badge_text' => $result->badge_text,
                'was_repaired' => $result->was_repaired,
                'attempts' => $result->attempts,
                'method' => $this->getAssessmentMethod($result),
            ];
        }

        // Sort clinical areas by a predefined order
        usort($clinicalAreas, function ($a, $b) {
            $order = ['history', 'exam', 'investigations', 'differential_diagnosis', 'management'];
            $aIndex = array_search($a['key'], $order);
            $bIndex = array_search($b['key'], $order);
            return $aIndex <=> $bIndex;
        });

        // Generate overall feedback
        $overallFeedback = $this->generateOverallFeedback($areaResults, $totalScore, $maxPossibleScore);
        
        // Generate safety concerns
        $safetyConcerns = $this->generateSafetyConcerns($areaResults);
        
        // Generate recommendations
        $recommendations = $this->generateRecommendations($areaResults);

        // Apply sanity checks
        $this->applySanityChecks($clinicalAreas, $totalScore, $maxPossibleScore);

        $result = [
            'total_score' => $totalScore,
            'max_possible_score' => $maxPossibleScore,
            'assessment_type' => 'detailed_clinical_areas_assessment',
            'clinical_areas' => $clinicalAreas,
            'overall_feedback' => $overallFeedback,
            'safety_concerns' => $safetyConcerns,
            'recommendations' => $recommendations,
            'model_info' => [
                'name' => config('services.gemini.model', 'gemini-1.5-flash'),
                'temperature' => 0.1,
                'assessment_approach' => 'map_reduce_clinical_areas',
                'total_areas' => $areaResults->count(),
                'ai_areas' => $areaResults->where('status', 'completed')->count(),
                'fallback_areas' => $areaResults->where('status', 'fallback')->count(),
                'failed_areas' => $areaResults->where('status', 'failed')->count(),
                'repaired_areas' => $areaResults->where('was_repaired', true)->count(),
            ],
            'processing_metadata' => [
                'run_id' => $assessmentRun->id,
                'processing_time' => $assessmentRun->completed_at?->diffInSeconds($assessmentRun->started_at),
                'has_fallbacks' => $areaResults->where('status', 'fallback')->isNotEmpty(),
                'success_rate' => $areaResults->count() > 0 ? 
                    round(($areaResults->whereIn('status', ['completed', 'fallback'])->count() / $areaResults->count()) * 100, 1) : 0,
            ]
        ];

        Log::info('ResultReducer aggregation completed', [
            'run_id' => $assessmentRun->id,
            'total_score' => $totalScore,
            'max_possible_score' => $maxPossibleScore,
            'success_rate' => $result['processing_metadata']['success_rate'],
            'has_fallbacks' => $result['processing_metadata']['has_fallbacks']
        ]);

        return $result;
    }

    /**
     * Create empty result for error cases
     */
    private function createEmptyResult(): array
    {
        return [
            'total_score' => 0,
            'max_possible_score' => 85,
            'assessment_type' => 'detailed_clinical_areas_assessment',
            'clinical_areas' => [],
            'overall_feedback' => 'Assessment could not be completed due to processing errors.',
            'safety_concerns' => ['Assessment system failure - results may not reflect actual performance'],
            'recommendations' => ['Please retry the assessment or contact support'],
            'model_info' => [
                'name' => 'error',
                'assessment_approach' => 'failed',
            ],
            'processing_metadata' => [
                'has_fallbacks' => false,
                'success_rate' => 0,
            ]
        ];
    }

    /**
     * Generate overall feedback based on area results
     */
    private function generateOverallFeedback(
        $areaResults, 
        int $totalScore, 
        int $maxPossibleScore
    ): string {
        $percentage = $maxPossibleScore > 0 ? round(($totalScore / $maxPossibleScore) * 100, 1) : 0;
        
        $completedAreas = $areaResults->where('status', 'completed')->count();
        $fallbackAreas = $areaResults->where('status', 'fallback')->count();
        $failedAreas = $areaResults->where('status', 'failed')->count();
        
        $feedback = "Overall performance: {$totalScore}/{$maxPossibleScore} ({$percentage}%). ";
        
        if ($completedAreas > 0) {
            $feedback .= "AI analysis completed for {$completedAreas} clinical areas. ";
        }
        
        if ($fallbackAreas > 0) {
            $feedback .= "Rubric-based assessment used for {$fallbackAreas} areas due to AI processing limitations. ";
        }
        
        if ($failedAreas > 0) {
            $feedback .= "Assessment failed for {$failedAreas} areas. ";
        }

        // Add performance tier feedback
        if ($percentage >= 85) {
            $feedback .= "Excellent overall performance across clinical areas.";
        } elseif ($percentage >= 75) {
            $feedback .= "Good performance with some areas for improvement.";
        } elseif ($percentage >= 65) {
            $feedback .= "Adequate performance with several areas needing attention.";
        } else {
            $feedback .= "Performance below expectations - significant improvement needed.";
        }

        return $feedback;
    }

    /**
     * Generate safety concerns from area results
     */
    private function generateSafetyConcerns($areaResults): array
    {
        $concerns = [];
        
        // Check for very low scores in critical areas
        foreach ($areaResults as $result) {
            if (!$result->score || $result->max_score === 0) {
                continue;
            }
            
            $percentage = ($result->score / $result->max_score) * 100;
            
            if ($percentage < 40) {
                $concerns[] = "Critical concern in {$result->area_display_name}: Very low performance ({$result->score}/{$result->max_score})";
            }
        }
        
        // Check for failed assessments
        $failedAreas = $areaResults->where('status', 'failed');
        foreach ($failedAreas as $failed) {
            $concerns[] = "Assessment system failure in {$failed->area_display_name} - manual review required";
        }
        
        if (empty($concerns)) {
            // Add generic safety check based on overall performance
            $totalScore = $areaResults->sum('score');
            $maxScore = $areaResults->sum('max_score');
            if ($maxScore > 0 && ($totalScore / $maxScore) < 0.5) {
                $concerns[] = "Overall performance below safe practice threshold - comprehensive review recommended";
            }
        }
        
        return $concerns;
    }

    /**
     * Generate recommendations based on area results
     */
    private function generateRecommendations($areaResults): array
    {
        $recommendations = [];
        
        // Area-specific recommendations based on performance
        foreach ($areaResults as $result) {
            if (!$result->score || $result->max_score === 0) {
                continue;
            }
            
            $percentage = ($result->score / $result->max_score) * 100;
            
            if ($percentage < 60) {
                $recommendations[] = $this->getAreaRecommendation($result->clinical_area, 'poor');
            } elseif ($percentage < 80) {
                $recommendations[] = $this->getAreaRecommendation($result->clinical_area, 'needs_improvement');
            }
        }
        
        // Add fallback recommendations
        $fallbackCount = $areaResults->where('status', 'fallback')->count();
        if ($fallbackCount > 0) {
            $recommendations[] = "Some assessments used fallback scoring - consider retrying for more detailed AI feedback";
        }
        
        if (empty($recommendations)) {
            $recommendations[] = "Continue practicing to maintain and improve clinical skills";
        }
        
        return array_unique($recommendations);
    }

    /**
     * Get specific recommendations for each clinical area
     */
    private function getAreaRecommendation(string $area, string $level): string
    {
        $recommendations = [
            'history' => [
                'poor' => 'Focus on systematic history-taking approach and comprehensive questioning techniques',
                'needs_improvement' => 'Practice more thorough history-taking with attention to key clinical details'
            ],
            'exam' => [
                'poor' => 'Review physical examination techniques and systematic approach to clinical examination',
                'needs_improvement' => 'Improve examination completeness and focus on critical examination skills'
            ],
            'investigations' => [
                'poor' => 'Study appropriate investigation selection and cost-effective testing strategies',
                'needs_improvement' => 'Refine investigation ordering with better clinical reasoning and cost awareness'
            ],
            'differential_diagnosis' => [
                'poor' => 'Strengthen diagnostic reasoning skills and differential diagnosis development',
                'needs_improvement' => 'Improve clinical reasoning and systematic approach to diagnosis'
            ],
            'management' => [
                'poor' => 'Review management principles and therapeutic decision-making processes',
                'needs_improvement' => 'Enhance management planning with focus on comprehensive patient care'
            ],
        ];
        
        return $recommendations[$area][$level] ?? "Focus on improving performance in {$area}";
    }

    /**
     * Apply sanity checks and corrections
     */
    private function applySanityChecks(array &$clinicalAreas, int &$totalScore, int $maxPossibleScore): void
    {
        $corrected = false;
        
        foreach ($clinicalAreas as &$area) {
            // Clamp scores to valid ranges
            if ($area['score'] < 0) {
                $area['score'] = 0;
                $corrected = true;
            }
            
            if ($area['score'] > $area['max_score']) {
                $area['score'] = $area['max_score'];
                $corrected = true;
            }
        }
        
        // Recalculate total if corrections were made
        if ($corrected) {
            $totalScore = array_sum(array_column($clinicalAreas, 'score'));
            
            Log::warning('Sanity check corrections applied', [
                'corrected_total_score' => $totalScore,
                'max_possible_score' => $maxPossibleScore
            ]);
        }
    }

    /**
     * Determine assessment method for result
     */
    private function getAssessmentMethod(AiAssessmentAreaResult $result): string
    {
        return match ($result->status) {
            'completed' => $result->was_repaired ? 'ai_repaired' : 'ai',
            'fallback' => 'rubric',
            'failed' => 'failed',
            default => 'unknown'
        };
    }

    /**
     * Convert citation strings into clickable objects when possible
     */
    private function normalizeCitations(array $citations): array
    {
        $normalized = [];
        foreach ($citations as $c) {
            if (!is_string($c)) {
                continue;
            }
            $title = $c;
            $source = 'session';
            $url = null;

            // test:<name> -> #test-<slug>
            if (preg_match('/^test\s*:\s*(.+)$/i', $c, $m)) {
                $name = trim($m[1]);
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
                $url = '#test-' . trim($slug, '-');
                $source = 'test';
                $title = $name;
            }

            // msg#<n> -> #msg-<n>
            if (!$url && preg_match('/^msg#(\d+)$/i', $c, $m)) {
                $url = '#msg-' . $m[1];
                $source = 'message';
            }

            // exam:<name> -> #exam-<slug>
            if (!$url && preg_match('/^exam\s*:\s*(.+)$/i', $c, $m)) {
                $name = trim($m[1]);
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
                $url = '#exam-' . trim($slug, '-');
                $source = 'examination';
                $title = $name;
            }

            $normalized[] = [
                'title' => $title,
                'source' => $source,
                'url' => $url,
            ];
        }

        return $normalized;
    }
}
