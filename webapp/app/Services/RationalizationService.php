<?php

namespace App\Services;

use App\Models\OsceSession;
use App\Models\OsceSessionRationalization;
use App\Models\AnamnesisRationalizationCard;
use App\Models\OsceDiagnosisEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing OSCE rationalization process.
 * Handles the gating logic, card generation, and evaluation flow.
 */
class RationalizationService
{
    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Initialize rationalization for an OSCE session
     */
    public function initializeRationalization(OsceSession $session): OsceSessionRationalization
    {
        // Check if rationalization already exists
        if ($rationalization = $session->rationalization) {
            return $rationalization;
        }

        $rationalization = OsceSessionRationalization::create([
            'osce_session_id' => $session->id,
            'status' => 'pending',
            'started_at' => now()
        ]);

        // Generate anamnesis rationalization cards
        $this->generateAnamnesisCards($rationalization, $session);

        Log::info('Rationalization initialized', [
            'session_id' => $session->id,
            'rationalization_id' => $rationalization->id
        ]);

        return $rationalization;
    }

    /**
     * Check if results can be unlocked for a session
     */
    public function canUnlockResults(OsceSession $session): bool
    {
        $rationalization = $session->rationalization;
        
        if (!$rationalization) {
            return false; // Must complete rationalization first
        }

        return $rationalization->canUnlockResults();
    }

    /**
     * Generate rationalization cards for anamnesis questions
     */
    private function generateAnamnesisCards(OsceSessionRationalization $rationalization, OsceSession $session): void
    {
        $orderIndex = 0;

        // Generate cards for questions asked by the user
        $askedQuestions = $this->extractUniqueQuestions($session);
        foreach ($askedQuestions as $question) {
            AnamnesisRationalizationCard::create([
                'session_rationalization_id' => $rationalization->id,
                'card_type' => 'asked_question',
                'question_text' => $question,
                'prompt_text' => "Why did you ask: '{$question}'?",
                'order_index' => $orderIndex++
            ]);
        }

        // Generate cards for expected questions not asked (negative anamnesis)
        $expectedQuestions = $session->osceCase->expected_anamnesis_questions ?? [];
        $missedQuestions = $this->findMissedQuestions($askedQuestions, $expectedQuestions);
        
        foreach ($missedQuestions as $missedQuestion) {
            AnamnesisRationalizationCard::create([
                'session_rationalization_id' => $rationalization->id,
                'card_type' => 'negative_anamnesis',
                'question_text' => $missedQuestion,
                'prompt_text' => "You did not ask: '{$missedQuestion}'. Why?",
                'order_index' => $orderIndex++
            ]);
        }

        // Generate cards for investigations (reuse existing rationales if available)
        $investigations = $session->orderedTests()->whereNotNull('clinical_reasoning')->get();
        foreach ($investigations as $test) {
            AnamnesisRationalizationCard::create([
                'session_rationalization_id' => $rationalization->id,
                'card_type' => 'investigation',
                'question_text' => $test->test_name,
                'prompt_text' => "Evaluation of your rationale for: '{$test->test_name}'",
                'user_rationale' => $test->clinical_reasoning,
                'is_answered' => true, // Already have rationale from session
                'answered_at' => $test->ordered_at,
                'order_index' => $orderIndex++
            ]);
        }
    }

    /**
     * Extract unique questions from chat messages
     */
    private function extractUniqueQuestions(OsceSession $session): array
    {
        $chatMessages = $session->chatMessages()
            ->where('sender_type', 'user')
            ->get();

        $questions = [];
        foreach ($chatMessages as $message) {
            $text = trim($message->message);
            
            // Simple heuristics to identify questions
            if (str_ends_with($text, '?') || $this->isLikelyQuestion($text)) {
                $normalized = $this->normalizeQuestion($text);
                if (!in_array($normalized, $questions)) {
                    $questions[] = $normalized;
                }
            }
        }

        // Remove semantic duplicates
        return $this->deduplicateQuestions($questions);
    }

    /**
     * Simple heuristics to identify questions without question marks
     */
    private function isLikelyQuestion(string $text): bool
    {
        $questionWords = [
            'what', 'when', 'where', 'why', 'how', 'who', 'which',
            'do you', 'did you', 'have you', 'can you', 'will you',
            'are you', 'were you', 'is there', 'was there',
            'tell me about', 'describe', 'explain'
        ];

        $lowerText = strtolower($text);
        foreach ($questionWords as $word) {
            if (str_contains($lowerText, $word)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize question text for comparison
     */
    private function normalizeQuestion(string $question): string
    {
        return trim(preg_replace('/\s+/', ' ', $question));
    }

    /**
     * Remove semantically similar questions
     */
    private function deduplicateQuestions(array $questions): array
    {
        $unique = [];
        
        foreach ($questions as $question) {
            $isDuplicate = false;
            
            foreach ($unique as $existingQuestion) {
                if ($this->areQuestionsSimilar($question, $existingQuestion)) {
                    $isDuplicate = true;
                    break;
                }
            }
            
            if (!$isDuplicate) {
                $unique[] = $question;
            }
        }

        return $unique;
    }

    /**
     * Check if two questions are semantically similar
     */
    private function areQuestionsSimilar(string $q1, string $q2): bool
    {
        $similarity = similar_text(strtolower($q1), strtolower($q2), $percent);
        return $percent > 75; // 75% similarity threshold
    }

    /**
     * Find expected questions that were not asked
     */
    private function findMissedQuestions(array $askedQuestions, array $expectedQuestions): array
    {
        $missed = [];
        
        foreach ($expectedQuestions as $expected) {
            $wasAsked = false;
            
            foreach ($askedQuestions as $asked) {
                if ($this->areQuestionsSimilar($expected, $asked)) {
                    $wasAsked = true;
                    break;
                }
            }
            
            if (!$wasAsked) {
                $missed[] = $expected;
            }
        }

        return $missed;
    }

    /**
     * Submit diagnosis entries for rationalization
     */
    public function submitDiagnoses(
        OsceSessionRationalization $rationalization,
        string $primaryDiagnosis,
        string $primaryReasoning,
        array $differentialDiagnoses
    ): void {
        // Update primary diagnosis in rationalization record
        $rationalization->update([
            'primary_diagnosis' => $primaryDiagnosis,
            'primary_diagnosis_reasoning' => $primaryReasoning
        ]);

        // Create primary diagnosis entry
        OsceDiagnosisEntry::create([
            'session_rationalization_id' => $rationalization->id,
            'diagnosis_name' => $primaryDiagnosis,
            'reasoning' => $primaryReasoning,
            'diagnosis_type' => 'primary',
            'order_index' => 0,
            'submitted_at' => now()
        ]);

        // Create differential diagnosis entries
        foreach ($differentialDiagnoses as $index => $differential) {
            OsceDiagnosisEntry::create([
                'session_rationalization_id' => $rationalization->id,
                'diagnosis_name' => $differential['diagnosis'],
                'reasoning' => $differential['reasoning'],
                'diagnosis_type' => 'differential',
                'order_index' => $index + 1,
                'submitted_at' => now()
            ]);
        }
    }

    /**
     * Submit care plan for rationalization
     */
    public function submitCarePlan(OsceSessionRationalization $rationalization, string $carePlan): void
    {
        $rationalization->update([
            'care_plan' => $carePlan
        ]);
    }

    /**
     * Check if rationalization is ready for evaluation
     */
    public function isReadyForEvaluation(OsceSessionRationalization $rationalization): bool
    {
        $allCardsAnswered = $rationalization->cards()->where('is_answered', false)->count() === 0;
        $hasPrimaryDiagnosis = !empty($rationalization->primary_diagnosis);
        $hasCarePlan = !empty($rationalization->care_plan);
        $hasMinimumDifferentials = $rationalization->diagnosisEntries()
            ->where('diagnosis_type', 'differential')
            ->count() >= 1;

        return $allCardsAnswered && $hasPrimaryDiagnosis && $hasCarePlan && $hasMinimumDifferentials;
    }

    /**
     * Get completion progress for the rationalization
     */
    public function getCompletionProgress(OsceSessionRationalization $rationalization): array
    {
        return $rationalization->getCompletionProgress();
    }

    /**
     * Mark rationalization as completed and unlock results
     */
    public function completeRationalization(OsceSessionRationalization $rationalization): void
    {
        $rationalization->update([
            'status' => 'completed',
            'results_unlocked' => true,
            'completed_at' => now()
        ]);

        Log::info('Rationalization completed', [
            'rationalization_id' => $rationalization->id,
            'session_id' => $rationalization->osce_session_id
        ]);
    }
}