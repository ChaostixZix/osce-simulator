<?php

return [
    'enabled' => env('APPWRITE_ENABLED', false),

    'endpoint' => env('APPWRITE_ENDPOINT'),

    'project_id' => env('APPWRITE_PROJECT_ID'),

    'api_key' => env('APPWRITE_API_KEY'),

    'self_signed' => env('APPWRITE_SELF_SIGNED', false),

    'database_id' => env('APPWRITE_DATABASE_ID', 'vibe-primary'),

    'database_name' => env('APPWRITE_DATABASE_NAME', 'Vibe Kanban Tables'),

    'migrations_collection_id' => env('APPWRITE_MIGRATIONS_COLLECTION_ID', 'appwrite_migrations'),

    'migrations_collection_name' => env('APPWRITE_MIGRATIONS_COLLECTION_NAME', 'Appwrite Migrations'),

    'permissions' => [
        'read' => array_values(array_filter(array_map('trim', explode(',', env('APPWRITE_COLLECTION_READ_ROLES', 'users'))))),
        'create' => array_values(array_filter(array_map('trim', explode(',', env('APPWRITE_COLLECTION_CREATE_ROLES', 'users'))))),
        'update' => array_values(array_filter(array_map('trim', explode(',', env('APPWRITE_COLLECTION_UPDATE_ROLES', 'users'))))),
        'delete' => array_values(array_filter(array_map('trim', explode(',', env('APPWRITE_COLLECTION_DELETE_ROLES', 'users'))))),
    ],

    'migrations' => [
        'path' => env('APPWRITE_MIGRATIONS_PATH', database_path('migrations/appwrite')),
    ],
];
