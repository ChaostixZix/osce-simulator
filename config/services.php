<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        // Default to a widely available public model unless overridden
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'gemini'),
    ],

    'openai_azure' => [
        'api_key' => env('OPENAI_AZURE_API_KEY'),
        'endpoint' => env('OPENAI_AZURE_ENDPOINT'),
        'deployment' => env('OPENAI_AZURE_DEPLOYMENT', 'gpt-4.1-nano'),
        'timeout' => env('OPENAI_AZURE_TIMEOUT', 30),
    ],

    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'key' => env('SUPABASE_ANON_KEY'),
        'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'redirect_url' => env('SUPABASE_REDIRECT_URL', env('APP_URL') . '/auth/supabase/callback'),
        'providers' => [
            'google' => env('SUPABASE_GOOGLE_ENABLED', false),
            'github' => env('SUPABASE_GITHUB_ENABLED', false),
            'facebook' => env('SUPABASE_FACEBOOK_ENABLED', false),
            'twitter' => env('SUPABASE_TWITTER_ENABLED', false),
        ],
    ],

];
