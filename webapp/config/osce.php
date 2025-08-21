<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Physical Exam Catalog
    |--------------------------------------------------------------------------
    |
    | This catalog defines all available physical examination categories and types
    | that can be performed during OSCE sessions. This serves as the canonical
    | list regardless of what's configured in individual case templates.
    |
    */
    'exam_catalog' => [
        'general' => [
            'inspection',
            'palpation'
        ],
        'cardiovascular' => [
            'inspection',
            'palpation',
            'auscultation'
        ],
        'respiratory' => [
            'inspection',
            'palpation',
            'percussion',
            'auscultation'
        ],
        'abdomen' => [
            'inspection',
            'palpation',
            'percussion',
            'auscultation'
        ],
        'neurological' => [
            'mental_status',
            'cranial_nerves',
            'motor',
            'sensory',
            'reflexes',
            'gait'
        ],
        'musculoskeletal' => [
            'inspection',
            'palpation',
            'range_of_motion'
        ],
        'skin' => [
            'inspection'
        ],
        'heent' => [
            'inspection'
        ]
    ]
];