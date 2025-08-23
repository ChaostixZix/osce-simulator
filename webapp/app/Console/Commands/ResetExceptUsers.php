<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class ResetExceptUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:reset-except-users {--seed : Run seeders after reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all migrations except users table, preserving user accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('⚠️  This will reset ALL data except user accounts!');
        $this->warn('📧 User authentication will be preserved.');

        if (!$this->confirm('Are you sure you want to continue? This action cannot be undone.')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $this->info('🔄 Starting database reset (preserving users)...');

        // Step 1: Backup users table data
        $this->info('💾 Backing up users table data...');
        $usersBackup = DB::table('users')->get()->toArray();
        $this->line("   ✓ Backed up " . count($usersBackup) . " users");

        // Step 2: Run fresh migration (this will drop everything)
        $this->info('🔄 Running fresh migrations...');

        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
            $this->line("   ✓ All migrations refreshed");
        } catch (\Exception $e) {
            $this->error("   ❌ Migration failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Step 3: Restore users table data
        if (!empty($usersBackup)) {
            $this->info('🔄 Restoring users table data...');
            foreach ($usersBackup as $user) {
                try {
                    DB::table('users')->insert((array) $user);
                } catch (\Exception $e) {
                    $this->warn("   ⚠️  Could not restore user: " . ($user->email ?? $user->id ?? 'unknown'));
                }
            }
            $this->line("   ✓ Users restored");
        }

        // Step 4: Run seeders if requested
        if ($this->option('seed')) {
            $this->info('🌱 Running seeders...');

            // Get available seeders
            $seeders = [
                'OsceCaseSeeder',
                'MedicalTestSeeder',
                'ForumSeeder',
                'MCQSeeder'
            ];

            foreach ($seeders as $seeder) {
                try {
                    Artisan::call('db:seed', ['--class' => $seeder]);
                    $this->line("   ✓ Seeded: {$seeder}");
                } catch (\Exception $e) {
                    $this->error("   ❌ Failed: {$seeder} - " . $e->getMessage());
                }
            }
        }

        // Step 5: Verify users table is intact
        $userCount = DB::table('users')->count();
        $this->info("✅ Reset complete! {$userCount} user accounts preserved.");

        if ($userCount > 0) {
            $this->info('🔐 You can still authenticate with your existing account.');
        }

        return Command::SUCCESS;
    }


}
