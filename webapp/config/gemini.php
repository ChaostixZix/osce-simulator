<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gemini API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Gemini API with web search grounding.
    | Used for evidence-based medical evaluation in OSCE rationalization.
    |
    */

    'api_key' => env('GEMINI_API_KEY'),

    'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),

    'timeout' => env('GEMINI_TIMEOUT', 30),

    'rate_limit' => [
        'requests_per_minute' => env('GEMINI_RATE_LIMIT', 60),
        'max_concurrent' => env('GEMINI_MAX_CONCURRENT', 5),
    ],

    'fallback' => [
        'enabled' => env('GEMINI_FALLBACK_ENABLED', true),
        'score' => 4, // Default fallback score out of 10
    ],

    'models' => [
        'gemini-2.5-flash' => [
            'supports_grounding' => true,
            'max_tokens' => 8192,
            'temperature_range' => [0.0, 2.0],
        ],
        'gemini-2.0-flash' => [
            'supports_grounding' => true,
            'max_tokens' => 8192,
            'temperature_range' => [0.0, 2.0],
        ],
        'gemini-1.5-pro' => [
            'supports_grounding' => true,
            'max_tokens' => 2097152,
            'temperature_range' => [0.0, 2.0],
        ],
        'gemini-1.5-flash' => [
            'supports_grounding' => true,
            'max_tokens' => 1048576,
            'temperature_range' => [0.0, 2.0],
        ],
    ],
];
