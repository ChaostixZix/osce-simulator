<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Prompt Assessment Configuration
    |--------------------------------------------------------------------------
    |
    | Controls whether to use the new multi-prompt assessment approach or
    | fall back to the original single-prompt method for each clinical area.
    |
    */
    
    'use_multi_prompt' => env('USE_MULTI_PROMPT_ASSESSMENT', true),
    
    /*
    |--------------------------------------------------------------------------
    | Aspect Assessment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for individual aspect assessments within each clinical area.
    |
    */
    
    'aspects' => [
        'enable_detailed_feedback' => true,
        'save_aspect_results' => true,
        'max_aspect_retries' => 2,
        'aspect_timeout' => 60, // seconds
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Thresholds
    |--------------------------------------------------------------------------
    |
    | Percentage thresholds for different performance levels.
    |
    */
    
    'thresholds' => [
        'acceptable' => 60, // 60% for acceptable performance
        'good' => 80,       // 80% for good performance
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for when to fall back to rubric-based scoring.
    |
    */
    
    'fallback' => [
        'enabled' => true,
        'max_total_retries' => 3,
        'use_rubric_on_ai_failure' => true,
        'default_aspect_scores' => [
            'history' => [
                'systematic_approach' => 4,
                'question_quality' => 3,
                'thoroughness' => 4,
            ],
            'exam' => [
                'technique' => 3,
                'systematic_approach' => 3,
                'critical_exams' => 3,
            ],
            // Add defaults for other areas...
        ]
    ]
];