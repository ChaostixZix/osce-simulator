<?php

return [
    'rubric_version' => 'RUBRIC_V1.0',

    'criteria' => [
        ['key' => 'history', 'label' => 'History-taking', 'max' => 20],
        ['key' => 'exam', 'label' => 'Physical Exam', 'max' => 15],
        ['key' => 'investigations', 'label' => 'Investigations', 'max' => 20],
        ['key' => 'diagnosis', 'label' => 'Diagnosis & Reasoning', 'max' => 20],
        ['key' => 'management', 'label' => 'Management Plan', 'max' => 15],
        ['key' => 'communication', 'label' => 'Communication/Professionalism', 'max' => 5],
        ['key' => 'safety', 'label' => 'Time Use/Safety', 'max' => 5],
    ],

    'penalties' => [
        'contraindicated_test' => 5,
        'inappropriate_test' => 2,
        'missed_required_test' => 3,
        'over_budget' => 2,
        'unsafe_statement' => 3,
    ],

    // Scoring weights for different aspects
    'weights' => [
        'history' => [
            'appropriate_questions' => 0.6,
            'thoroughness' => 0.3,
            'efficiency' => 0.1,
        ],
        'exam' => [
            'relevant_examinations' => 0.7,
            'technique' => 0.3,
        ],
        'investigations' => [
            'appropriate_tests' => 0.5,
            'cost_effectiveness' => 0.3,
            'timing' => 0.2,
        ],
        'diagnosis' => [
            'accuracy' => 0.6,
            'differential' => 0.4,
        ],
        'management' => [
            'appropriateness' => 0.7,
            'safety' => 0.3,
        ],
        'communication' => [
            'clarity' => 0.5,
            'empathy' => 0.3,
            'professionalism' => 0.2,
        ],
        'safety' => [
            'time_management' => 0.6,
            'critical_actions' => 0.4,
        ],
    ],
];
