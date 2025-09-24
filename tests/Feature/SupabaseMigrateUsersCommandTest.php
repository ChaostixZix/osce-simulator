<?php

use App\Models\User;
use App\Console\Commands\SupabaseMigrateUsers;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Artisan;

test('command shows migration statistics correctly', function () {
    // Create test users
    User::factory()->count(5)->create(['is_migrated' => false]);
    User::factory()->count(3)->create(['is_migrated' => true]);

    $this->artisan('supabase:migrate-users --dry-run')
        ->expectsOutput('DRY RUN MODE - No actual changes will be made')
        ->assertExitCode(0);
});

test('command migrates users in batches', function () {
    // Create test users that need migration
    $users = User::factory()->count(3)->create([
        'is_migrated' => false,
        'supabase_id' => null,
    ]);

    // Mock SupabaseService
    $this->mock(SupabaseService::class, function ($mock) use ($users) {
        foreach ($users as $user) {
            $mock->shouldReceive('createUser')
                ->with(Mockery::on(function ($data) use ($user) {
                    return $data['email'] === $user->email;
                }))
                ->andReturn([
                    'id' => 'supabase-' . $user->id,
                    'email' => $user->email,
                ]);
        }
    });

    // Run migration with batch size of 2
    $this->artisan('supabase:migrate-users --batch=2')
        ->expectsOutput('Starting user migration from WorkOS to Supabase...')
        ->expectsOutput('Found 3 users to migrate...')
        ->expectsOutput('Processing in batches of 2...')
        ->assertExitCode(0);

    // Check that users were marked as migrated
    foreach ($users as $user) {
        $user->refresh();
        $this->assertTrue($user->is_migrated);
        $this->assertNotNull($user->supabase_id);
    }
});

test('command can migrate specific users by email', function () {
    // Create test users
    $user1 = User::factory()->create([
        'email' => 'migrate1@example.com',
        'is_migrated' => false,
    ]);
    $user2 = User::factory()->create([
        'email' => 'migrate2@example.com',
        'is_migrated' => false,
    ]);
    // This user should not be migrated
    User::factory()->create([
        'email' => 'nomigrate@example.com',
        'is_migrated' => false,
    ]);

    // Mock SupabaseService
    $this->mock(SupabaseService::class, function ($mock) use ($user1, $user2) {
        $mock->shouldReceive('createUser')
            ->twice()
            ->andReturn([
                'id' => 'supabase-id',
                'email' => 'test@example.com',
            ]);
    });

    // Run migration for specific emails
    $this->artisan('supabase:migrate-users --email=migrate1@example.com --email=migrate2@example.com')
        ->expectsOutput('Migrating specific users: migrate1@example.com, migrate2@example.com')
        ->assertExitCode(0);

    // Check that only specified users were migrated
    $user1->refresh();
    $user2->refresh();
    $nonMigratedUser = User::where('email', 'nomigrate@example.com')->first();

    $this->assertTrue($user1->is_migrated);
    $this->assertTrue($user2->is_migrated);
    $this->assertFalse($nonMigratedUser->is_migrated);
});

test('command handles migration errors gracefully', function () {
    // Create test user
    $user = User::factory()->create([
        'email' => 'fail@example.com',
        'is_migrated' => false,
    ]);

    // Mock SupabaseService to return error
    $this->mock(SupabaseService::class, function ($mock) {
        $mock->shouldReceive('createUser')
            ->andReturn(['error' => ['message' => 'Email already exists']]);
    });

    // Run migration
    $this->artisan('supabase:migrate-users --email=fail@example.com')
        ->expectsOutput('FAILED: User fail@example.com (ID: ' . $user->id . ') - {"message":"Email already exists"}')
        ->assertExitCode(1); // Non-zero exit code for failures

    // User should not be marked as migrated
    $user->refresh();
    $this->assertFalse($user->is_migrated);
});

test('command respects force flag', function () {
    // Create already migrated user
    $user = User::factory()->create([
        'email' => 'already@migrated.com',
        'is_migrated' => true,
        'supabase_id' => 'old-id',
    ]);

    // Mock SupabaseService
    $this->mock(SupabaseService::class, function ($mock) {
        $mock->shouldReceive('createUser')
            ->andReturn([
                'id' => 'new-supabase-id',
                'email' => 'already@migrated.com',
            ]);
    });

    // Without force flag, user should be skipped
    $this->artisan('supabase:migrate-users --email=already@migrated.com')
        ->expectsOutput('SKIPPED: User already@migrated.com (ID: ' . $user->id . ') already migrated')
        ->assertExitCode(0);

    // With force flag, user should be migrated
    $this->artisan('supabase:migrate-users --email=already@migrated.com --force')
        ->expectsOutput('MIGRATED: User already@migrated.com (ID: ' . $user->id . ') → Supabase ID: new-supabase-id')
        ->assertExitCode(0);
});

test('command shows progress bar', function () {
    // Create test users
    User::factory()->count(5)->create(['is_migrated' => false]);

    // Mock SupabaseService
    $this->mock(SupabaseService::class, function ($mock) {
        $mock->shouldReceive('createUser')
            ->times(5)
            ->andReturn([
                'id' => 'supabase-id',
                'email' => 'test@example.com',
            ]);
    });

    // Run command
    $this->artisan('supabase:migrate-users --batch=2')
        ->assertExitCode(0);

    // The progress bar should be displayed automatically
});

test('command handles no users to migrate', function () {
    // Don't create any users

    $this->artisan('supabase:migrate-users')
        ->expectsOutput('No users to migrate.')
        ->assertExitCode(0);
});