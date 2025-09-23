<?php

/**
 * Multi-Prompt Assessment System Test Results
 * 
 * This script demonstrates how the multi-prompt assessment system
 * evaluates each aspect of clinical performance with mock data.
 */

// Mock assessment results based on a test OSCE session
$testResults = [
    'history' => [
        'aspects' => [
            'systematic_approach' => [
                'score' => 6,
                'max_score' => 7,
                'percentage' => 85.7,
                'performance_level' => 'good',
                'feedback' => 'Excellent systematic approach following a logical sequence from presenting complaint to relevant history. Student started with open-ended question about chest pain, then systematically explored location, radiation, associated symptoms, and risk factors.',
                'evidence' => ['msg#2: "What brings you in today?"', 'msg#4: "Where exactly is it and does it go anywhere else?"', 'msg#8: "Do you have any medical problems?"']
            ],
            'question_quality' => [
                'score' => 5,
                'max_score' => 6,
                'percentage' => 83.3,
                'performance_level' => 'good',
                'feedback' => 'Very good use of open-ended questions with appropriate follow-up. Student effectively used "What brings you in today?" as an opener and asked specific clarifying questions.',
                'evidence' => ['msg#2: Open-ended question about chest pain', 'msg#6: Specific question about associated symptoms']
            ],
            'thoroughness' => [
                'score' => 6,
                'max_score' => 7,
                'percentage' => 85.7,
                'performance_level' => 'good',
                'feedback' => 'Comprehensive coverage of key history points including pain characteristics, associated symptoms, risk factors (hypertension, smoking), and relevant negative (no diabetes). Missed family history of cardiac disease.',
                'evidence' => ['msg#4: Pain location and radiation', 'msg#6: Associated symptoms', 'msg#8: Medical history and risk factors']
            ]
        ],
        'overall' => [
            'total_score' => 17,
            'max_score' => 20,
            'percentage' => 85.0,
            'performance_level' => 'good',
            'aspects_at_good' => 3,
            'aspects_at_acceptable' => 0,
            'total_aspects' => 3
        ]
    ],
    
    'exam' => [
        'aspects' => [
            'technique' => [
                'score' => 4,
                'max_score' => 5,
                'percentage' => 80.0,
                'performance_level' => 'good',
                'feedback' => 'Good examination technique with proper systematic approach. Student performed inspection, pulse, BP, and auscultation in logical sequence. Could improve on patient positioning.',
                'evidence' => ['exam:General Inspection', 'exam:Pulse', 'exam:Blood Pressure', 'exam:Heart Sounds']
            ],
            'systematic_approach' => [
                'score' => 4,
                'max_score' => 5,
                'percentage' => 80.0,
                'performance_level' => 'good',
                'feedback' => 'Very systematic examination following cardiovascular sequence: inspection → pulse → BP → auscultation. Also included respiratory auscultation showing integrated thinking.',
                'evidence' => ['Cardiovascular examination completed in order', 'Respiratory examination added appropriately']
            ],
            'critical_exams' => [
                'score' => 4,
                'max_score' => 5,
                'percentage' => 80.0,
                'performance_level' => 'good',
                'feedback' => 'Performed most critical examinations for chest pain: vital signs, cardiovascular exam. Missed JVP examination but covered all other essential components.',
                'evidence' => ['BP measured: 160/100', 'Heart sounds assessed', 'Pulse: 110 bpm', 'Missed: JVP examination']
            ]
        ],
        'overall' => [
            'total_score' => 12,
            'max_score' => 15,
            'percentage' => 80.0,
            'performance_level' => 'good',
            'aspects_at_good' => 3,
            'aspects_at_acceptable' => 0,
            'total_aspects' => 3
        ]
    ],
    
    'investigations' => [
        'aspects' => [
            'appropriateness' => [
                'score' => 7,
                'max_score' => 7,
                'percentage' => 100.0,
                'performance_level' => 'good',
                'feedback' => 'Excellent selection of appropriate investigations for chest pain. ECG, troponin, and CXR are all indicated and appropriately chosen.',
                'evidence' => ['test:ECG - Essential for chest pain', 'test:Cardiac Troponin - Required for rule out ACS', 'test:Chest X-ray - Appropriate for chest pain differential']
            ],
            'cost_effectiveness' => [
                'score' => 6,
                'max_score' => 6,
                'percentage' => 100.0,
                'performance_level' => 'good',
                'feedback' => 'Highly cost-effective approach. Total cost of $250 represents only 25% of available budget. All tests ordered are essential for diagnosis.',
                'evidence' => ['Total cost: $250', 'Budget utilization: 25%', 'Essential tests only, no unnecessary investigations']
            ],
            'sequencing' => [
                'score' => 6,
                'max_score' => 7,
                'percentage' => 85.7,
                'performance_level' => 'good',
                'feedback' => 'Very good sequencing of investigations. ECG and troponin ordered first as most urgent for acute chest pain. CXR added appropriately. Could consider ordering all simultaneously.',
                'evidence' => ['ECG ordered first', 'Troponin ordered second', 'CXR ordered third', 'Proper prioritization of cardiac tests']
            ]
        ],
        'overall' => [
            'total_score' => 19,
            'max_score' => 20,
            'percentage' => 95.0,
            'performance_level' => 'good',
            'aspects_at_good' => 3,
            'aspects_at_acceptable' => 0,
            'total_aspects' => 3
        ]
    ],
    
    'differential_diagnosis' => [
        'aspects' => [
            'breadth' => [
                'score' => 4,
                'max_score' => 5,
                'percentage' => 80.0,
                'performance_level' => 'good',
                'feedback' => 'Good consideration of differential diagnoses. Based on history, likely considered: ACS, aortic dissection, pulmonary embolism, pericarditis, musculoskeletal pain. Could explicitly state differentials.',
                'evidence' => ['Explored pain characteristics suggestive of cardiac origin', 'Considered risk factors', 'Appropriate investigation choices reflect differential thinking']
            ],
            'reasoning' => [
                'score' => 3,
                'max_score' => 5,
                'percentage' => 60.0,
                'performance_level' => 'acceptable',
                'feedback' => 'Basic diagnostic reasoning present. Student recognized high-risk features (sudden onset, radiation to arm, diaphoresis) and ordered appropriate tests. Could improve on explicit hypothesis testing.',
                'evidence' => ['Recognized concerning features', 'Appropriate urgency in investigation', 'Limited explicit discussion of possibilities']
            ],
            'prioritization' => [
                'score' => 4,
                'max_score' => 5,
                'percentage' => 80.0,
                'performance_level' => 'good',
                'feedback' => 'Good prioritization focusing on life-threatening conditions first. ACS appropriately ruled out first with ECG and troponin. Other considerations appropriately addressed.',
                'evidence' => ['Immediate focus on cardiac evaluation', 'Appropriate concern for ACS', 'Good safety netting with investigations']
            ]
        ],
        'overall' => [
            'total_score' => 11,
            'max_score' => 15,
            'percentage' => 73.3,
            'performance_level' => 'acceptable',
            'aspects_at_good' => 2,
            'aspects_at_acceptable' => 1,
            'total_aspects' => 3
        ]
    ],
    
    'management' => [
        'aspects' => [
            'immediate_actions' => [
                'score' => 4,
                'max_score' => 5,
                'percentage' => 80.0,
                'performance_level' => 'good',
                'feedback' => 'Good immediate actions recognized need for urgent cardiac evaluation. Appropriate vital sign assessment and urgent investigations ordered. Could mention aspirin.',
                'evidence' => ['Prompt vital sign assessment', 'Urgent ECG and troponin', 'Recognition of high-risk presentation']
            ],
            'treatment_plan' => [
                'score' => 3,
                'max_score' => 5,
                'percentage' => 60.0,
                'performance_level' => 'acceptable',
                'feedback' => 'Basic treatment plan evident through investigation choices. No explicit discussion of medications (aspirin, nitrates, morphine) or monitoring. Treatment approach inferred rather than stated.',
                'evidence' => ['Appropriate diagnostic pathway', 'No explicit medication plan', 'No mention of monitoring requirements']
            ],
            'follow_up' => [
                'score' => 2,
                'max_score' => 5,
                'percentage' => 40.0,
                'performance_level' => 'needs_improvement',
                'feedback' => 'Limited follow-up planning. No discussion of what to do if tests are negative, discharge planning, or cardiac risk factor modification. Important gap in management.',
                'evidence' => ['No discharge planning discussed', 'No mention of cardiac rehabilitation', 'No risk factor modification plan']
            ]
        ],
        'overall' => [
            'total_score' => 9,
            'max_score' => 15,
            'percentage' => 60.0,
            'performance_level' => 'acceptable',
            'aspects_at_good' => 1,
            'aspects_at_acceptable' => 1,
            'total_aspects' => 3
        ]
    ],
    
    'communication' => [
        'aspects' => [
            'clarity' => [
                'score' => 3,
                'max_score' => 3,
                'percentage' => 100.0,
                'performance_level' => 'good',
                'feedback' => 'Excellent clarity in communication. All questions were clear, concise, and easily understandable. Good use of non-medical language when appropriate.',
                'evidence' => ['Clear opening question', 'Simple, direct questions', 'Easy to understand language']
            ],
            'empathy' => [
                'score' => 3,
                'max_score' => 4,
                'percentage' => 75.0,
                'performance_level' => 'acceptable',
                'feedback' => 'Some empathetic statements present ("really bad pain"), but could show more concern for patient\'s distress. Limited emotional support beyond clinical questions.',
                'evidence' => ['Acknowledged pain severity', 'Limited emotional support', 'Focus primarily on clinical aspects']
            ],
            'professionalism' => [
                'score' => 3,
                'max_score' => 3,
                'percentage' => 100.0,
                'performance_level' => 'good',
                'feedback' => 'Excellent professionalism throughout. Appropriate introduction, maintained professional demeanor, focused on relevant clinical issues.',
                'evidence' => ['Professional introduction', 'Maintained appropriate boundaries', 'Clinical focus throughout']
            ]
        ],
        'overall' => [
            'total_score' => 9,
            'max_score' => 10,
            'percentage' => 90.0,
            'performance_level' => 'good',
            'aspects_at_good' => 2,
            'aspects_at_acceptable' => 1,
            'total_aspects' => 3
        ]
    ],
    
    'safety' => [
        'aspects' => [
            'error_prevention' => [
                'score' => 3,
                'max_score' => 4,
                'percentage' => 75.0,
                'performance_level' => 'acceptable',
                'feedback' => 'Good awareness of patient safety. Recognized high-risk presentation and acted appropriately. Could improve on explicit safety netting and contingency planning.',
                'evidence' => ['Recognized emergency nature', 'Appropriate urgent response', 'Limited explicit safety planning']
            ],
            'time_management' => [
                'score' => 2,
                'max_score' => 3,
                'percentage' => 66.7,
                'performance_level' => 'acceptable',
                'feedback' => 'Acceptable time management. History took 7 minutes (slightly long), examination 5 minutes, investigation planning 3 minutes. Overall efficient but could be more streamlined.',
                'evidence' => ['History: 7 minutes', 'Examination: 5 minutes', 'Planning: 3 minutes', 'Total: 15 minutes']
            ],
            'documentation' => [
                'score' => 2,
                'max_score' => 3,
                'percentage' => 66.7,
                'performance_level' => 'acceptable',
                'feedback' => 'Basic documentation evident through examination findings and test orders. Could improve on systematic documentation of thought process and rationale.',
                'evidence' => ['Examination findings documented', 'Tests ordered appropriately', 'Limited documentation of reasoning']
            ]
        ],
        'overall' => [
            'total_score' => 7,
            'max_score' => 10,
            'percentage' => 70.0,
            'performance_level' => 'acceptable',
            'aspects_at_good' => 0,
            'aspects_at_acceptable' => 3,
            'total_aspects' => 3
        ]
    ]
];

// Display results
echo "=== MULTI-PROMPT ASSESSMENT TEST RESULTS ===\n\n";
echo "Test Case: 65-year-old male with acute chest pain\n";
echo "Session Duration: 15 minutes\n";
echo "Total Test Cost: \$250\n\n";

$overallTotal = 0;
$overallMax = 0;

foreach ($testResults as $area => $data) {
    $overallTotal += $data['overall']['total_score'];
    $overallMax += $data['overall']['max_score'];
    
    echo strtoupper(str_replace('_', ' ', $area)) . " ASSESSMENT\n";
    echo str_repeat('-', 40) . "\n";
    echo "Overall Score: {$data['overall']['total_score']}/{$data['overall']['max_score']} (" . round($data['overall']['percentage'], 1) . "%)\n";
    echo "Performance Level: {$data['overall']['performance_level']}\n\n";
    
    echo "Aspect Breakdown:\n";
    foreach ($data['aspects'] as $aspect => $result) {
        $badge = $result['performance_level'] === 'good' ? '🟢' : 
                 ($result['performance_level'] === 'acceptable' ? '🟡' : '🔴');
        echo "{$badge} " . ucfirst(str_replace('_', ' ', $aspect)) . ": {$result['score']}/{$result['max_score']} ({$result['percentage']}%)\n";
    }
    
    echo "\nDetailed Feedback:\n";
    foreach ($data['aspects'] as $aspect => $result) {
        echo "• " . ucfirst(str_replace('_', ' ', $aspect)) . ": {$result['feedback']}\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
}

$overallPercentage = ($overallTotal / $overallMax) * 100;
echo "FINAL OVERALL ASSESSMENT\n";
echo str_repeat("=", 40) . "\n";
echo "Total Score: {$overallTotal}/{$overallMax}\n";
echo "Overall Percentage: " . round($overallPercentage, 1) . "%\n\n";

echo "PERFORMANCE SUMMARY\n";
echo str_repeat("-", 30) . "\n";
$excellentCount = 0;
$acceptableCount = 0;
$needsImprovementCount = 0;

foreach ($testResults as $area => $data) {
    if ($data['overall']['performance_level'] === 'good') {
        $excellentCount++;
        echo "🟢 " . ucfirst(str_replace('_', ' ', $area)) . ": Excellent\n";
    } elseif ($data['overall']['performance_level'] === 'acceptable') {
        $acceptableCount++;
        echo "🟡 " . ucfirst(str_replace('_', ' ', $area)) . ": Acceptable\n";
    } else {
        $needsImprovementCount++;
        echo "🔴 " . ucfirst(str_replace('_', ' ', $area)) . ": Needs Improvement\n";
    }
}

echo "\n";
echo "Top Strengths:\n";
foreach ($testResults as $area => $data) {
    foreach ($data['aspects'] as $aspect => $result) {
        if ($result['performance_level'] === 'good') {
            echo "• Outstanding " . str_replace('_', ' ', $aspect) . " in " . str_replace('_', ' ', $area) . "\n";
        }
    }
}

echo "\nAreas for Development:\n";
foreach ($testResults as $area => $data) {
    foreach ($data['aspects'] as $aspect => $result) {
        if ($result['performance_level'] !== 'good') {
            echo "• Improve " . str_replace('_', ' ', $aspect) . " in " . str_replace('_', ' ', $area) . "\n";
        }
    }
}

echo "\n=== BENEFITS OF MULTI-PROMPT APPROACH ===\n";
echo "1. ✅ No token limit issues - Each aspect assessed separately\n";
echo "2. ✅ Deeper analysis - Focused evaluation of specific skills\n";
echo "3. ✅ Better feedback - Specific guidance per aspect\n";
echo "4. ✅ Clear criteria - Defined thresholds for performance levels\n";
echo "5. ✅ Flexible - Can adjust individual aspect prompts\n";
echo "6. ✅ Detailed tracking - Progress monitoring per skill\n";