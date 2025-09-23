<?php

namespace App\Services;

use App\Models\AiAssessmentAreaResult;
use App\Models\OsceSession;
use Illuminate\Support\Facades\Log;

class AssessmentPromptManager
{
    private array $aspectPrompts = [];
    
    public function __construct()
    {
        $this->initializeAspectPrompts();
    }
    
    /**
     * Initialize prompt templates for each assessment aspect
     */
    private function initializeAspectPrompts(): void
    {
        $this->aspectPrompts = [
            'history' => [
                'systematic_approach' => [
                    'prompt' => 'Evaluate the systematic approach to history-taking. Assess if the student followed a logical sequence (e.g., presenting complaint, history of present illness, past medical history, medications, allergies, family history, social history).',
                    'acceptable_criteria' => 'Covers at least 4 major history domains in some order',
                    'good_criteria' => 'Follows logical sequence covering all major domains',
                    'max_score' => 7
                ],
                'question_quality' => [
                    'prompt' => 'Evaluate the quality of questions asked. Assess use of open-ended vs closed questions, clarity, and relevance.',
                    'acceptable_criteria' => 'Asks relevant questions, mix of open/closed',
                    'good_criteria' => 'Excellent question selection, appropriate use of open-ended questions',
                    'max_score' => 6
                ],
                'thoroughness' => [
                    'prompt' => 'Evaluate thoroughness in covering key history points. Check for omission of critical information.',
                    'acceptable_criteria' => 'Covers 60% of key history points',
                    'good_criteria' => 'Covers 80%+ of key history points efficiently',
                    'max_score' => 7
                ]
            ],
            'exam' => [
                'technique' => [
                    'prompt' => 'Evaluate physical examination technique. Assess proper positioning, exposure, and examination methods.',
                    'acceptable_criteria' => 'Basic technique, some awkwardness but adequate',
                    'good_criteria' => 'Proper technique, smooth and confident',
                    'max_score' => 5
                ],
                'systematic_approach' => [
                    'prompt' => 'Evaluate systematic approach to examination. Assess if followed logical sequence (inspection, palpation, percussion, auscultation).',
                    'acceptable_criteria' => 'Examines relevant areas, some organization',
                    'good_criteria' => 'Highly organized, systematic examination',
                    'max_score' => 5
                ],
                'critical_exams' => [
                    'prompt' => 'Evaluate performance of critical examinations specific to this case. Did they miss any must-do examinations?',
                    'acceptable_criteria' => 'Performs 60% of critical exams',
                    'good_criteria' => 'Performs 80%+ of critical exams',
                    'max_score' => 5
                ]
            ],
            'investigations' => [
                'appropriateness' => [
                    'prompt' => 'Evaluate appropriateness of investigations ordered. Were tests indicated based on clinical presentation?',
                    'acceptable_criteria' => '60% of tests clinically indicated',
                    'good_criteria' => '80%+ of tests highly appropriate',
                    'max_score' => 7
                ],
                'cost_effectiveness' => [
                    'prompt' => 'Evaluate cost-effectiveness. Consider budget use and unnecessary tests.',
                    'acceptable_criteria' => 'Some wastage, stays within budget',
                    'good_criteria' => 'Highly cost-effective, minimal waste',
                    'max_score' => 6
                ],
                'sequencing' => [
                    'prompt' => 'Evaluate sequencing of investigations. Were urgent tests prioritized appropriately?',
                    'acceptable_criteria' => 'Logical order for most tests',
                    'good_criteria' => 'Excellent prioritization, urgent tests first',
                    'max_score' => 7
                ]
            ],
            'differential_diagnosis' => [
                'breadth' => [
                    'prompt' => 'Evaluate breadth of differential diagnosis. Consider number and appropriateness of diagnoses considered.',
                    'acceptable_criteria' => '2-3 reasonable diagnoses',
                    'good_criteria' => '4+ well-considered diagnoses',
                    'max_score' => 5
                ],
                'reasoning' => [
                    'prompt' => 'Evaluate diagnostic reasoning. Assess ability to support/refute hypotheses with evidence.',
                    'acceptable_criteria' => 'Basic reasoning present',
                    'good_criteria' => 'Excellent evidence-based reasoning',
                    'max_score' => 5
                ],
                'prioritization' => [
                    'prompt' => 'Evaluate prioritization of diagnoses. Were dangerous conditions ruled out appropriately?',
                    'acceptable_criteria' => 'Considers most likely diagnosis',
                    'good_criteria' => 'Excellent risk stratification',
                    'max_score' => 5
                ]
            ],
            'management' => [
                'immediate_actions' => [
                    'prompt' => 'Evaluate immediate management actions. Were urgent needs addressed?',
                    'acceptable_criteria' => 'Addresses immediate needs',
                    'good_criteria' => 'Excellent urgent management',
                    'max_score' => 5
                ],
                'treatment_plan' => [
                    'prompt' => 'Evaluate treatment planning. Consider appropriateness, dosing, and monitoring.',
                    'acceptable_criteria' => 'Reasonable treatment plan',
                    'good_criteria' => 'Comprehensive optimal treatment',
                    'max_score' => 5
                ],
                'follow_up' => [
                    'prompt' => 'Evaluate follow-up planning. Consider monitoring, referrals, and patient education.',
                    'acceptable_criteria' => 'Basic follow-up mentioned',
                    'good_criteria' => 'Detailed follow-up plan',
                    'max_score' => 5
                ]
            ],
            'communication' => [
                'clarity' => [
                    'prompt' => 'Evaluate communication clarity. Assess use of understandable language and explanations.',
                    'acceptable_criteria' => 'Generally clear communication',
                    'good_criteria' => 'Exceptionally clear explanations',
                    'max_score' => 3
                ],
                'empathy' => [
                    'prompt' => 'Evaluate empathy and patient-centered approach. Consider rapport building and emotional support.',
                    'acceptable_criteria' => 'Some empathetic statements',
                    'good_criteria' => 'Consistently empathetic',
                    'max_score' => 4
                ],
                'professionalism' => [
                    'prompt' => 'Evaluate professionalism. Consider demeanor, boundaries, and ethical conduct.',
                    'acceptable_criteria' => 'Professional throughout',
                    'good_criteria' => 'Exemplary professionalism',
                    'max_score' => 3
                ]
            ],
            'safety' => [
                'error_prevention' => [
                    'prompt' => 'Evaluate error prevention. Did they identify and mitigate risks?',
                    'acceptable_criteria' => 'Identifies major risks',
                    'good_criteria' => 'Comprehensive risk management',
                    'max_score' => 4
                ],
                'time_management' => [
                    'prompt' => 'Evaluate time management. Was the session paced appropriately?',
                    'acceptable_criteria' => 'Adequate time use',
                    'good_criteria' => 'Excellent time efficiency',
                    'max_score' => 3
                ],
                'documentation' => [
                    'prompt' => 'Evaluate documentation quality. Were findings and plans clearly recorded?',
                    'acceptable_criteria' => 'Basic documentation',
                    'good_criteria' => 'Thorough clear documentation',
                    'max_score' => 3
                ]
            ]
        ];
    }
    
    /**
     * Build a comprehensive prompt for a specific clinical area and aspect
     */
    public function buildAspectPrompt(
        OsceSession $session, 
        string $clinicalArea, 
        string $aspect,
        array $artifact
    ): array {
        $config = $this->aspectPrompts[$clinicalArea][$aspect] ?? null;
        
        if (!$config) {
            throw new \Exception("Unknown aspect: {$clinicalArea}.{$aspect}");
        }
        
        $artifactJson = json_encode($artifact, JSON_PRETTY_PRINT);
        
        $prompt = <<<PROMPT
You are an expert medical examiner evaluating a specific aspect of {$clinicalArea} performance in an OSCE session.

ASSESSMENT FOCUS: {$aspect}
EVALUATION CRITERIA: {$config['prompt']}

SESSION DATA:
{$artifactJson}

SCORING GUIDELINES:
- Acceptable Performance (60-79% of {$config['max_score']} points): {$config['acceptable_criteria']}
- Good Performance (80-100% of {$config['max_score']} points): {$config['good_criteria']}

ASSESSMENT INSTRUCTIONS:
1. Focus ONLY on the {$aspect} aspect of {$clinicalArea}
2. Review relevant session data (chat messages, tests, examinations)
3. Provide specific evidence from the session
4. Score based on the criteria above
5. Must return structured JSON response

REQUIRED JSON RESPONSE FORMAT:
{
  "aspect": "{$aspect}",
  "clinical_area": "{$clinicalArea}",
  "score": <integer 0-{$config['max_score']}>,
  "max_score": {$config['max_score']},
  "performance_level": "acceptable"|"good"|"needs_improvement",
  "feedback": "<specific feedback with evidence citations, max 800 chars>",
  "citations": ["msg#15", "test:CBC", "exam:cardiac"],
  "acceptable_evidence": ["evidence of meeting acceptable criteria"],
  "good_evidence": ["evidence of meeting good criteria"],
  "missing_elements": ["any missing elements for good performance"]
}

SCORING DECISION GUIDE:
- Needs Improvement: 0-59% (score 0-{$this->calculatePercentage(59, $config['max_score'])})
- Acceptable: 60-79% (score {$this->calculatePercentage(60, $config['max_score'])}-{$this->calculatePercentage(79, $config['max_score'])})
- Good: 80-100% (score {$this->calculatePercentage(80, $config['max_score'])}-{$config['max_score']})

Be specific and cite exact evidence from the session data.
PROMPT;

        return [
            'prompt' => $prompt,
            'config' => $config,
            'schema' => $this->getAspectSchema()
        ];
    }
    
    /**
     * Get all aspects for a clinical area
     */
    public function getAspectsForClinicalArea(string $clinicalArea): array
    {
        return array_keys($this->aspectPrompts[$clinicalArea] ?? []);
    }
    
    /**
     * Get all clinical areas with their aspects
     */
    public function getAllClinicalAreas(): array
    {
        return array_keys($this->aspectPrompts);
    }
    
    /**
     * Calculate score from percentage
     */
    private function calculatePercentage(int $percentage, int $maxScore): int
    {
        return round(($percentage / 100) * $maxScore);
    }
    
    /**
     * Get JSON schema for aspect assessment
     */
    private function getAspectSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'aspect' => ['type' => 'string'],
                'clinical_area' => ['type' => 'string'],
                'score' => ['type' => 'integer', 'minimum' => 0],
                'max_score' => ['type' => 'integer', 'minimum' => 1],
                'performance_level' => ['type' => 'string', 'enum' => ['acceptable', 'good', 'needs_improvement']],
                'feedback' => ['type' => 'string', 'maxLength' => 800],
                'citations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'acceptable_evidence' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'good_evidence' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'missing_elements' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ]
            ],
            'required' => ['aspect', 'clinical_area', 'score', 'max_score', 'performance_level', 'feedback']
        ];
    }
    
    /**
     * Calculate overall clinical area score from aspect scores
     */
    public function calculateOverallScore(array $aspectScores): array
    {
        $totalScore = 0;
        $totalMaxScore = 0;
        $aspectsAtGood = 0;
        $aspectsAtAcceptable = 0;
        $totalAspects = count($aspectScores);
        
        foreach ($aspectScores as $aspect => $result) {
            $totalScore += $result['score'];
            $totalMaxScore += $result['max_score'];
            
            if ($result['performance_level'] === 'good') {
                $aspectsAtGood++;
            } elseif ($result['performance_level'] === 'acceptable') {
                $aspectsAtAcceptable++;
            }
        }
        
        $overallPercentage = $totalMaxScore > 0 ? ($totalScore / $totalMaxScore) * 100 : 0;
        
        // Determine overall performance level
        if ($overallPercentage >= 80 || ($aspectsAtGood / $totalAspects) >= 0.8) {
            $overallLevel = 'good';
        } elseif ($overallPercentage >= 60 || ($aspectsAtAcceptable / $totalAspects) >= 0.6) {
            $overallLevel = 'acceptable';
        } else {
            $overallLevel = 'needs_improvement';
        }
        
        return [
            'total_score' => $totalScore,
            'max_score' => $totalMaxScore,
            'percentage' => $overallPercentage,
            'performance_level' => $overallLevel,
            'aspects_at_good' => $aspectsAtGood,
            'aspects_at_acceptable' => $aspectsAtAcceptable,
            'total_aspects' => $totalAspects
        ];
    }
    
    /**
     * Generate detailed feedback from aspect results
     */
    public function generateDetailedFeedback(array $aspectResults): string
    {
        $feedback = [];
        $strengths = [];
        $improvements = [];
        
        foreach ($aspectResults as $result) {
            if ($result['performance_level'] === 'good') {
                $strengths[] = $result['feedback'];
            } else {
                $improvements[] = $result['feedback'];
            }
        }
        
        if (!empty($strengths)) {
            $feedback[] = "Strengths:\n- " . implode("\n- ", $strengths);
        }
        
        if (!empty($improvements)) {
            $feedback[] = "\nAreas for Improvement:\n- " . implode("\n- ", $improvements);
        }
        
        return implode("\n\n", $feedback);
    }
}