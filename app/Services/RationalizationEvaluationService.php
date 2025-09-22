<?php

namespace App\Services;

use App\Models\AnamnesisRationalizationCard;
use App\Models\OsceDiagnosisEntry;
use App\Models\OsceSessionRationalization;
use App\Models\RationalizationEvaluation;
use Illuminate\Support\Facades\Log;

/**
 * Service for evaluating OSCE rationalization submissions.
 * Orchestrates the complete evaluation process using Gemini API.
 */
class RationalizationEvaluationService
{
    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Process complete evaluation for a rationalization session
     */
    public function evaluateComplete(OsceSessionRationalization $rationalization): array
    {
        Log::info('Starting complete rationalization evaluation', [
            'rationalization_id' => $rationalization->id,
            'session_id' => $rationalization->osce_session_id,
        ]);

        $results = [
            'anamnesis' => $this->evaluateAnamnesisCards($rationalization),
            'investigations' => $this->evaluateInvestigationCards($rationalization),
            'diagnosis' => $this->evaluateDiagnoses($rationalization),
            'plan' => $this->evaluateCarePlan($rationalization),
        ];

        // Calculate overall scores and generate summary
        $overallResults = $this->generateOverallEvaluation($rationalization, $results);

        // Update rationalization with final scores
        $this->updateRationalizationScores($rationalization, $overallResults);

        Log::info('Completed rationalization evaluation', [
            'rationalization_id' => $rationalization->id,
            'total_score' => $overallResults['total_score'],
            'performance_band' => $overallResults['performance_band'],
        ]);

        return $overallResults;
    }

    /**
     * Evaluate anamnesis rationalization cards
     */
    public function evaluateAnamnesisCards(OsceSessionRationalization $rationalization): array
    {
        $cards = $rationalization->cards()->whereIn('card_type', ['asked_question', 'negative_anamnesis'])->get();
        $results = [];
        $totalScore = 0;
        $maxScore = 0;

        foreach ($cards as $card) {
            if ($card->is_answered && ! $card->marked_as_forgot && $card->user_rationale) {
                $evaluation = $this->evaluateCard($card, $rationalization);
                $this->updateCardEvaluation($card, $evaluation);

                $results[] = $evaluation;
                $totalScore += $evaluation['evaluation']['total_score'];
            } elseif ($card->marked_as_forgot) {
                // Handle "forgot" cards with teaching feedback
                $this->handleForgotCard($card, $rationalization);
            }

            $maxScore += 10; // Each card is worth 10 points maximum
        }

        $sectionScore = $maxScore > 0 ? round(($totalScore / $maxScore) * 10, 1) : 0;

        // Create section evaluation record
        $sectionEvaluation = $this->createSectionEvaluation(
            $rationalization,
            'anamnesis',
            'Anamnesis Rationalization',
            $sectionScore,
            $results
        );

        return [
            'section_score' => $sectionScore,
            'total_cards' => $cards->count(),
            'evaluated_cards' => count($results),
            'evaluation_id' => $sectionEvaluation->id,
            'results' => $results,
        ];
    }

    /**
     * Evaluate investigation cards
     */
    public function evaluateInvestigationCards(OsceSessionRationalization $rationalization): array
    {
        $cards = $rationalization->cards()->where('card_type', 'investigation')->get();
        $results = [];
        $totalScore = 0;
        $maxScore = 0;

        foreach ($cards as $card) {
            if ($card->user_rationale) {
                $evaluation = $this->evaluateInvestigationCard($card, $rationalization);
                $this->updateCardEvaluation($card, $evaluation);

                $results[] = $evaluation;
                $totalScore += $evaluation['evaluation']['total_score'];
            }

            $maxScore += 10;
        }

        $sectionScore = $maxScore > 0 ? round(($totalScore / $maxScore) * 10, 1) : 0;

        $sectionEvaluation = $this->createSectionEvaluation(
            $rationalization,
            'investigations',
            'Investigation Rationalization',
            $sectionScore,
            $results
        );

        return [
            'section_score' => $sectionScore,
            'total_cards' => $cards->count(),
            'evaluated_cards' => count($results),
            'evaluation_id' => $sectionEvaluation->id,
            'results' => $results,
        ];
    }

    /**
     * Evaluate diagnosis entries
     */
    public function evaluateDiagnoses(OsceSessionRationalization $rationalization): array
    {
        $diagnosisEntries = $rationalization->diagnosisEntries;
        $results = [];
        $totalScore = 0;
        $maxScore = 0;

        foreach ($diagnosisEntries as $entry) {
            $evaluation = $this->evaluateDiagnosisEntry($entry, $rationalization);
            $this->updateDiagnosisEvaluation($entry, $evaluation);

            $results[] = $evaluation;
            $totalScore += $evaluation['evaluation']['total_score'];
            $maxScore += 10;
        }

        $sectionScore = $maxScore > 0 ? round(($totalScore / $maxScore) * 10, 1) : 0;

        $sectionEvaluation = $this->createSectionEvaluation(
            $rationalization,
            'diagnosis',
            'Diagnosis & Differential',
            $sectionScore,
            $results
        );

        return [
            'section_score' => $sectionScore,
            'total_entries' => $diagnosisEntries->count(),
            'evaluated_entries' => count($results),
            'evaluation_id' => $sectionEvaluation->id,
            'results' => $results,
        ];
    }

    /**
     * Evaluate care plan
     */
    public function evaluateCarePlan(OsceSessionRationalization $rationalization): array
    {
        if (! $rationalization->care_plan) {
            return ['section_score' => 0, 'evaluation_id' => null, 'results' => []];
        }

        $evaluation = $this->evaluateCarePlanContent($rationalization);

        $sectionEvaluation = $this->createSectionEvaluation(
            $rationalization,
            'plan',
            'Care Plan',
            $evaluation['evaluation']['total_score'],
            [$evaluation]
        );

        return [
            'section_score' => $evaluation['evaluation']['total_score'],
            'evaluation_id' => $sectionEvaluation->id,
            'results' => [$evaluation],
        ];
    }

    /**
     * Evaluate individual anamnesis/negative anamnesis card
     */
    private function evaluateCard(AnamnesisRationalizationCard $card, OsceSessionRationalization $rationalization): array
    {
        $osceCase = $rationalization->osceSession->osceCase;

        $systemPrompt = $this->buildAnamnesisSystemPrompt($card, $osceCase);
        $context = $this->buildContextString($rationalization);

        return $this->geminiService->evaluateWithGrounding(
            $systemPrompt,
            $card->user_rationale,
            $context
        );
    }

    /**
     * Evaluate investigation card with complete clinical context
     */
    private function evaluateInvestigationCard(AnamnesisRationalizationCard $card, OsceSessionRationalization $rationalization): array
    {
        $osceCase = $rationalization->osceSession->osceCase;
        $session = $rationalization->osceSession;

        // Find the actual test order for context
        $orderedTest = $session->orderedTests()->where('test_name', $card->question_text)->first();

        // Get all ordered tests for complete context
        $allOrderedTests = $session->orderedTests;
        $testNames = $allOrderedTests->pluck('test_name')->toArray();

        Log::info('Evaluating investigation card with context', [
            'card_id' => $card->id,
            'test_name' => $card->question_text,
            'all_ordered_tests' => $testNames,
            'total_tests' => count($testNames)
        ]);

        $systemPrompt = $this->buildInvestigationSystemPromptWithContext($card, $osceCase, $orderedTest, $testNames);
        $context = $this->buildContextString($rationalization);

        $result = $this->geminiService->evaluateWithGrounding(
            $systemPrompt,
            $card->user_rationale,
            $context
        );

        Log::info('Investigation evaluation completed', [
            'card_id' => $card->id,
            'test_name' => $card->question_text,
            'verdict' => $result['evaluation']['verdict'] ?? 'unknown',
            'has_citations' => !empty($result['evaluation']['citations']),
            'citation_count' => count($result['evaluation']['citations'] ?? [])
        ]);

        return $result;
    }

    /**
     * Evaluate diagnosis entry
     */
    private function evaluateDiagnosisEntry(OsceDiagnosisEntry $entry, OsceSessionRationalization $rationalization): array
    {
        $osceCase = $rationalization->osceSession->osceCase;
        $systemPrompt = $this->buildDiagnosisSystemPrompt($entry, $osceCase);
        $context = $this->buildContextString($rationalization);

        return $this->geminiService->evaluateWithGrounding(
            $systemPrompt,
            $entry->reasoning,
            $context
        );
    }

    /**
     * Evaluate care plan content
     */
    private function evaluateCarePlanContent(OsceSessionRationalization $rationalization): array
    {
        $osceCase = $rationalization->osceSession->osceCase;
        $systemPrompt = $this->buildCarePlanSystemPrompt($osceCase);
        $context = $this->buildContextString($rationalization);

        return $this->geminiService->evaluateWithGrounding(
            $systemPrompt,
            $rationalization->care_plan,
            $context
        );
    }

    /**
     * Build system prompt for anamnesis evaluation
     */
    private function buildAnamnesisSystemPrompt(AnamnesisRationalizationCard $card, $osceCase): string
    {
        $caseTitle = $osceCase->title;
        $cardType = $card->card_type === 'asked_question' ? 'asked during the session' : 'expected but not asked';

        return "Evaluate the clinical reasoning for this anamnesis question in the context of case: '{$caseTitle}'. ".
               "The question '{$card->question_text}' was {$cardType}. ".
               'Assess the rationale for relevance to likely pathology, evidence accuracy against medical guidelines, '.
               'completeness of reasoning, patient safety considerations, and appropriate prioritization. '.
               'Consider red flags, differential diagnosis, and clinical decision rules. '.
               'Provide evidence-based feedback with authoritative medical sources.';
    }

    /**
     * Build system prompt for investigation evaluation with complete context
     */
    private function buildInvestigationSystemPromptWithContext(AnamnesisRationalizationCard $card, $osceCase, $orderedTest = null, array $allOrderedTests = []): string
    {
        $caseTitle = $osceCase->title;
        $testName = $card->question_text;
        $costInfo = $orderedTest ? ' (Cost: $'.number_format($orderedTest->cost, 2).')' : '';

        // Build context of all tests ordered
        $allTestsContext = '';
        if (!empty($allOrderedTests)) {
            $allTestsContext = ' The student ordered the following complete set of investigations: ' . implode(', ', $allOrderedTests) . '. ';
            $allTestsContext .= 'Consider how this specific test fits into the overall diagnostic strategy rather than evaluating it in isolation.';
        }

        return "Evaluate the clinical reasoning for ordering '{$testName}'{$costInfo} in case: '{$caseTitle}'. ".
               $allTestsContext .
               'Assess appropriateness vs pretest probability, evidence-based indications, cost-effectiveness, '.
               'timing and sequencing, potential harms/risks, and contribution to diagnosis/management. '.
               'Consider current medical guidelines, risk-benefit analysis, and resource utilization. '.
               'IMPORTANT: If this test was already covered by another investigation ordered, or if the overall test strategy '.
               'adequately addresses this clinical question, do not criticize it as redundant. '.
               'Provide evidence-based feedback with authoritative medical sources.';
    }

    /**
     * Build system prompt for investigation evaluation (legacy method)
     */
    private function buildInvestigationSystemPrompt(AnamnesisRationalizationCard $card, $osceCase, $orderedTest = null): string
    {
        $caseTitle = $osceCase->title;
        $testName = $card->question_text;
        $costInfo = $orderedTest ? ' (Cost: $'.number_format($orderedTest->cost, 2).')' : '';

        return "Evaluate the clinical reasoning for ordering '{$testName}'{$costInfo} in case: '{$caseTitle}'. ".
               'Assess appropriateness vs pretest probability, evidence-based indications, cost-effectiveness, '.
               'timing and sequencing, potential harms/risks, and contribution to diagnosis/management. '.
               'Consider current medical guidelines, risk-benefit analysis, and resource utilization. '.
               'Flag any contraindications or inappropriate usage patterns.';
    }

    /**
     * Build system prompt for diagnosis evaluation
     */
    private function buildDiagnosisSystemPrompt(OsceDiagnosisEntry $entry, $osceCase): string
    {
        $caseTitle = $osceCase->title;
        $diagnosisType = $entry->diagnosis_type;
        $diagnosisName = $entry->diagnosis_name;

        return "Evaluate the {$diagnosisType} diagnosis '{$diagnosisName}' for case: '{$caseTitle}'. ".
               'Assess clinical coherence with presentation, supporting/refuting evidence, '.
               'appropriate use of diagnostic criteria, consideration of differential diagnoses, '.
               'and safety implications. For differential diagnoses, evaluate discriminating features '.
               'and logical reasoning for inclusion. Reference current medical guidelines and '.
               'evidence-based diagnostic approaches.';
    }

    /**
     * Build system prompt for care plan evaluation
     */
    private function buildCarePlanSystemPrompt($osceCase): string
    {
        $caseTitle = $osceCase->title;
        $setting = $osceCase->clinical_setting ?? 'emergency department';
        $urgency = $osceCase->urgency_level ?? 'standard';

        return "Evaluate this structured care plan for case: '{$caseTitle}' in {$setting} setting ".
               "(urgency level: {$urgency}). Assess completeness across all sections ".
               '(immediate actions, diagnostics, therapeutics, monitoring, disposition, counseling, safety netting), '.
               'appropriateness of interventions, evidence-based recommendations, patient safety measures, '.
               'logical sequencing, and practical feasibility. Consider clinical guidelines, '.
               'resource utilization, and continuity of care.';
    }

    /**
     * Build context string with case and session information
     */
    private function buildContextString(OsceSessionRationalization $rationalization): string
    {
        $session = $rationalization->osceSession;
        $case = $session->osceCase;

        $context = "OSCE Case: {$case->title}\n";
        $context .= "Clinical Setting: {$case->clinical_setting}\n";
        $context .= "Case Description: {$case->description}\n";

        if ($case->ai_patient_symptoms) {
            $symptoms = is_array($case->ai_patient_symptoms)
                ? implode(', ', $case->ai_patient_symptoms)
                : $case->ai_patient_symptoms;
            $context .= "Patient Symptoms: {$symptoms}\n";
        }

        return $context;
    }

    /**
     * Update card with evaluation results
     */
    private function updateCardEvaluation(AnamnesisRationalizationCard $card, array $evaluation): void
    {
        $evalData = $evaluation['evaluation'] ?? [];

        $card->update([
            'evaluation_summary' => $evalData['user_rationale_summary'] ?? '',
            'verdict' => $evalData['verdict'] ?? 'partially_correct',
            'feedback_why' => $evalData['feedback_why'] ?? '',
            'score' => $evalData['total_score'] ?? 0,
            'citations' => $evalData['citations'] ?? [],
            'relevance_score' => $evalData['score_breakdown']['relevance'] ?? 0,
            'evidence_accuracy_score' => $evalData['score_breakdown']['evidence_accuracy'] ?? 0,
            'completeness_score' => $evalData['score_breakdown']['completeness'] ?? 0,
            'safety_score' => $evalData['score_breakdown']['safety'] ?? 0,
            'prioritization_score' => $evalData['score_breakdown']['prioritization'] ?? 0,
            'evaluated_at' => now(),
        ]);
    }

    /**
     * Update diagnosis entry with evaluation results
     */
    private function updateDiagnosisEvaluation(OsceDiagnosisEntry $entry, array $evaluation): void
    {
        $evalData = $evaluation['evaluation'] ?? [];

        $entry->update([
            'evaluation_summary' => $evalData['user_rationale_summary'] ?? '',
            'verdict' => $evalData['verdict'] ?? 'partially_correct',
            'feedback_why' => $evalData['feedback_why'] ?? '',
            'score' => $evalData['total_score'] ?? 0,
            'citations' => $evalData['citations'] ?? [],
            'relevance_score' => $evalData['score_breakdown']['relevance'] ?? 0,
            'evidence_accuracy_score' => $evalData['score_breakdown']['evidence_accuracy'] ?? 0,
            'completeness_score' => $evalData['score_breakdown']['completeness'] ?? 0,
            'safety_score' => $evalData['score_breakdown']['safety'] ?? 0,
            'prioritization_score' => $evalData['score_breakdown']['prioritization'] ?? 0,
            'evaluated_at' => now(),
        ]);
    }

    /**
     * Handle forgotten cards with teaching feedback
     */
    private function handleForgotCard(AnamnesisRationalizationCard $card, OsceSessionRationalization $rationalization): void
    {
        // For forgotten cards, provide teaching feedback without scoring
        $card->update([
            'evaluation_summary' => 'Question was not asked during the session',
            'verdict' => 'incorrect',
            'feedback_why' => 'This question was expected for this case type. Consider reviewing the importance of systematic history taking.',
            'score' => 0,
            'evaluated_at' => now(),
        ]);
    }

    /**
     * Create section evaluation record
     */
    private function createSectionEvaluation(
        OsceSessionRationalization $rationalization,
        string $evaluationType,
        string $sectionName,
        float $sectionScore,
        array $results
    ): RationalizationEvaluation {
        // Extract strengths, gaps, and fixes from results
        $strengths = [];
        $gaps = [];
        $topFixes = [];

        // Analyze results to generate section feedback
        foreach ($results as $result) {
            $evaluation = $result['evaluation'] ?? [];
            if (($evaluation['total_score'] ?? 0) >= 7) {
                $strengths[] = $evaluation['feedback_why'] ?? '';
            } elseif (($evaluation['total_score'] ?? 0) <= 4) {
                $gaps[] = $evaluation['feedback_why'] ?? '';
            }
        }

        return RationalizationEvaluation::create([
            'session_rationalization_id' => $rationalization->id,
            'evaluation_type' => $evaluationType,
            'section_name' => $sectionName,
            'section_score' => $sectionScore,
            'strengths' => array_slice(array_unique(array_filter($strengths)), 0, 3),
            'gaps' => array_slice(array_unique(array_filter($gaps)), 0, 3),
            'top_fixes' => $topFixes, // Could be enhanced with specific recommendations
            'grounding_metadata' => $results[0]['grounding_metadata'] ?? null,
            'search_queries' => [], // Could extract from grounding data
            'model_used' => $results[0]['model_used'] ?? config('services.gemini.model', 'gemini-1.5-flash'),
            'evaluation_started_at' => now()->subMinutes(1), // Approximate
            'evaluation_completed_at' => now(),
            'has_citations' => count($results) > 0 && ! empty($results[0]['evaluation']['citations'] ?? []),
            'citation_count' => array_sum(array_map(fn ($r) => count($r['evaluation']['citations'] ?? []), $results)),
        ]);
    }

    /**
     * Generate overall evaluation summary
     */
    private function generateOverallEvaluation(OsceSessionRationalization $rationalization, array $sectionResults): array
    {
        $sectionScores = [
            'anamnesis' => $sectionResults['anamnesis']['section_score'],
            'investigations' => $sectionResults['investigations']['section_score'],
            'diagnosis' => $sectionResults['diagnosis']['section_score'],
            'plan' => $sectionResults['plan']['section_score'],
        ];

        $totalScore = array_sum($sectionScores) / count($sectionScores);
        $performanceBand = $this->calculatePerformanceBand($totalScore);

        return [
            'section_scores' => $sectionScores,
            'total_score' => round($totalScore, 1),
            'performance_band' => $performanceBand,
            'section_results' => $sectionResults,
        ];
    }

    /**
     * Update rationalization with final scores
     */
    private function updateRationalizationScores(OsceSessionRationalization $rationalization, array $overallResults): void
    {
        $sectionScores = $overallResults['section_scores'];

        $rationalization->update([
            'anamnesis_score' => $sectionScores['anamnesis'],
            'investigations_score' => $sectionScores['investigations'],
            'diagnosis_score' => $sectionScores['diagnosis'],
            'plan_score' => $sectionScores['plan'],
            'total_score' => $overallResults['total_score'],
            'performance_band' => $overallResults['performance_band'],
            'status' => 'completed',
        ]);
    }

    /**
     * Calculate performance band from total score
     */
    private function calculatePerformanceBand(float $totalScore): string
    {
        if ($totalScore >= 8) {
            return 'strong';
        } elseif ($totalScore >= 6) {
            return 'satisfactory';
        } else {
            return 'needs_work';
        }
    }
}
