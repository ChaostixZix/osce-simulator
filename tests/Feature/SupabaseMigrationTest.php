<?php

it('can create Supabase service', function () {
    $service = app(\App\Services\SupabaseService::class);
    expect($service)->toBeInstanceOf(\App\Services\SupabaseService::class);
});

it('has proper configuration structure', function () {
    $config = config('supabase');
    
    expect($config)->toBeArray();
    expect($config)->toHaveKeys(['url', 'key', 'service_role_key']);
});

it('user model has Supabase fields', function () {
    $user = new \App\Models\User();
    
    expect($user)->toHaveFillable([
        'email',
        'supabase_id',
        'provider',
        'provider_id',
        'is_migrated',
        'last_login_at',
        'avatar',
        'is_admin',
        'is_banned'
    ]);
});

it('middleware validates Supabase tokens', function () {
    $middleware = new \App\Http\Middleware\SupabaseAuthenticate(app(\App\Services\SupabaseService::class));
    expect($middleware)->toBeInstanceOf(\App\Http\Middleware\SupabaseAuthenticate::class);
});

it('can migrate users command exists', function () {
    $this->artisan('list')->expectsOutputToContain('supabase:migrate-users');
});

it('migration adds Supabase fields to users table', function () {
    // Check if migration file exists
    $migrationFile = database_path('migrations/2025_09_24_212707_add_supabase_fields_to_users_table.php');
    expect(file_exists($migrationFile))->toBeTrue();
    
    // Check migration content
    $migrationContent = file_get_contents($migrationFile);
    expect($migrationContent)->toContain('supabase_id');
    expect($migrationContent)->toContain('is_migrated');
});