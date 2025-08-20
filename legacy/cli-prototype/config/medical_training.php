<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Medical Training System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration settings for the medical training
    | system including AI integration, OSCE settings, and system parameters.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the AI service settings including API credentials, model
    | selection, and request parameters.
    |
    */
    'ai' => [
        'api_url' => env('MEDICAL_AI_API_URL', 'https://openrouter.ai/api/v1/chat/completions'),
        'api_key' => env('MEDICAL_AI_API_KEY'),
        'model' => env('MEDICAL_AI_MODEL', 'anthropic/claude-3.5-sonnet'),
        'max_retries' => env('MEDICAL_AI_MAX_RETRIES', 3),
        'timeout' => env('MEDICAL_AI_TIMEOUT', 30),
        'max_tokens' => env('MEDICAL_AI_MAX_TOKENS', 4000),
        'temperature' => env('MEDICAL_AI_TEMPERATURE', 0.7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the chat functionality including history management
    | and message processing.
    |
    */
    'chat' => [
        'max_history_length' => env('MEDICAL_CHAT_MAX_HISTORY', 10),
        'summarize_threshold' => env('MEDICAL_CHAT_SUMMARIZE_THRESHOLD', 6),
        'enable_auto_summarization' => env('MEDICAL_CHAT_AUTO_SUMMARIZE', true),
        'export_formats' => ['txt', 'json', 'csv'],
        'max_message_length' => env('MEDICAL_CHAT_MAX_MESSAGE_LENGTH', 2000),
    ],

    /*
    |--------------------------------------------------------------------------
    | OSCE Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for OSCE (Objective Structured Clinical Examination)
    | functionality including case management and scoring.
    |
    */
    'osce' => [
        'default_case_duration' => env('MEDICAL_OSCE_DEFAULT_DURATION', 1200), // 20 minutes in seconds
        'max_session_duration' => env('MEDICAL_OSCE_MAX_DURATION', 3600), // 60 minutes in seconds
        'auto_complete_threshold' => env('MEDICAL_OSCE_AUTO_COMPLETE_THRESHOLD', 90), // percentage
        'scoring' => [
            'default_weights' => [
                'history' => 3,
                'examination' => 2,
                'investigations' => 2,
                'diagnosis' => 3,
                'management' => 2
            ],
            'passing_score' => env('MEDICAL_OSCE_PASSING_SCORE', 60),
            'excellent_score' => env('MEDICAL_OSCE_EXCELLENT_SCORE', 85),
        ],
        'checklist' => [
            'enable_auto_detection' => env('MEDICAL_OSCE_AUTO_DETECTION', true),
            'keyword_matching' => env('MEDICAL_OSCE_KEYWORD_MATCHING', true),
            'case_sensitivity' => env('MEDICAL_OSCE_CASE_SENSITIVE', false),
        ],
        'patient_simulation' => [
            'response_delay' => env('MEDICAL_OSCE_RESPONSE_DELAY', 500), // milliseconds
            'personality_consistency' => env('MEDICAL_OSCE_PERSONALITY_CONSISTENCY', true),
            'emotion_simulation' => env('MEDICAL_OSCE_EMOTION_SIMULATION', true),
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Management
    |--------------------------------------------------------------------------
    |
    | Configuration for session handling, cleanup, and statistics.
    |
    */
    'session' => [
        'default_duration' => env('MEDICAL_SESSION_DURATION', 7200), // 2 hours in seconds
        'cleanup_days' => env('MEDICAL_SESSION_CLEANUP_DAYS', 30),
        'enable_statistics' => env('MEDICAL_SESSION_ENABLE_STATS', true),
        'track_performance' => env('MEDICAL_SESSION_TRACK_PERFORMANCE', true),
        'auto_end_inactive' => env('MEDICAL_SESSION_AUTO_END', true),
        'inactive_timeout' => env('MEDICAL_SESSION_INACTIVE_TIMEOUT', 1800), // 30 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for system health monitoring and diagnostics.
    |
    */
    'health' => [
        'enable_monitoring' => env('MEDICAL_HEALTH_MONITORING', true),
        'check_interval' => env('MEDICAL_HEALTH_CHECK_INTERVAL', 300), // 5 minutes in seconds
        'api_timeout_threshold' => env('MEDICAL_HEALTH_API_TIMEOUT', 5000), // milliseconds
        'error_rate_threshold' => env('MEDICAL_HEALTH_ERROR_RATE', 10), // percentage
        'response_time_threshold' => env('MEDICAL_HEALTH_RESPONSE_TIME', 3000), // milliseconds
        'enable_auto_recovery' => env('MEDICAL_HEALTH_AUTO_RECOVERY', false),
        'log_retention_days' => env('MEDICAL_HEALTH_LOG_RETENTION', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for the medical training system.
    |
    */
    'security' => [
        'rate_limiting' => [
            'chat_requests_per_minute' => env('MEDICAL_RATE_LIMIT_CHAT', 60),
            'osce_requests_per_minute' => env('MEDICAL_RATE_LIMIT_OSCE', 30),
            'api_requests_per_minute' => env('MEDICAL_RATE_LIMIT_API', 100),
        ],
        'enable_request_logging' => env('MEDICAL_SECURITY_LOG_REQUESTS', true),
        'log_sensitive_data' => env('MEDICAL_SECURITY_LOG_SENSITIVE', false),
        'enable_ip_filtering' => env('MEDICAL_SECURITY_IP_FILTERING', false),
        'allowed_ips' => explode(',', env('MEDICAL_SECURITY_ALLOWED_IPS', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Toggle features on/off for testing or gradual rollout.
    |
    */
    'features' => [
        'enable_chat' => env('MEDICAL_FEATURE_CHAT', true),
        'enable_osce' => env('MEDICAL_FEATURE_OSCE', true),
        'enable_analytics' => env('MEDICAL_FEATURE_ANALYTICS', true),
        'enable_export' => env('MEDICAL_FEATURE_EXPORT', true),
        'enable_feedback' => env('MEDICAL_FEATURE_FEEDBACK', true),
        'enable_tutorials' => env('MEDICAL_FEATURE_TUTORIALS', true),
        'enable_advanced_scoring' => env('MEDICAL_FEATURE_ADVANCED_SCORING', false),
        'enable_ai_feedback' => env('MEDICAL_FEATURE_AI_FEEDBACK', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | Language and localization settings.
    |
    */
    'localization' => [
        'default_language' => env('MEDICAL_DEFAULT_LANGUAGE', 'id'), // Indonesian
        'supported_languages' => ['id', 'en'],
        'enable_auto_translation' => env('MEDICAL_AUTO_TRANSLATION', false),
        'patient_language_preference' => env('MEDICAL_PATIENT_LANGUAGE', 'id'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for optimizing system performance.
    |
    */
    'performance' => [
        'enable_caching' => env('MEDICAL_ENABLE_CACHING', true),
        'cache_ttl' => env('MEDICAL_CACHE_TTL', 3600), // 1 hour in seconds
        'enable_compression' => env('MEDICAL_ENABLE_COMPRESSION', true),
        'max_concurrent_sessions' => env('MEDICAL_MAX_CONCURRENT_SESSIONS', 100),
        'enable_response_caching' => env('MEDICAL_ENABLE_RESPONSE_CACHING', false),
        'database_query_timeout' => env('MEDICAL_DB_QUERY_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development & Testing
    |--------------------------------------------------------------------------
    |
    | Settings for development and testing environments.
    |
    */
    'development' => [
        'enable_debug_mode' => env('MEDICAL_DEBUG_MODE', false),
        'mock_ai_responses' => env('MEDICAL_MOCK_AI', false),
        'enable_test_cases' => env('MEDICAL_ENABLE_TEST_CASES', false),
        'log_level' => env('MEDICAL_LOG_LEVEL', 'info'),
        'enable_profiling' => env('MEDICAL_ENABLE_PROFILING', false),
    ]
];