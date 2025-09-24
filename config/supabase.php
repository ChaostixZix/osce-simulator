<?php

return [
    'url' => env('SUPABASE_URL'),
    'key' => env('SUPABASE_ANON_KEY'),
    'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
    'jwt_secret' => env('SUPABASE_JWT_SECRET'),
    'redirect_url' => env('SUPABASE_REDIRECT_URL', env('APP_URL').'/auth/callback'),
    'providers' => [
        'google' => env('SUPABASE_GOOGLE_ENABLED', true),
        'github' => env('SUPABASE_GITHUB_ENABLED', true),
        'twitter' => env('SUPABASE_TWITTER_ENABLED', false),
    ],
];