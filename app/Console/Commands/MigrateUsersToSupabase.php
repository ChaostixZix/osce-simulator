<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MigrateUsersToSupabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supabase:migrate-users 
                           {--batch=100 : Number of users to migrate per batch}
                           {--force : Force migration without confirmation}
                           {--dry-run : Show what would be migrated without actual migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing users from WorkOS to Supabase';

    protected $supabase;
    protected $dryRun = false;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->supabase = app(SupabaseService::class);
        $this->dryRun = $this->option('dry-run');

        $batchSize = (int) $this->option('batch');
        $force = $this->option('force');

        $this->info("Starting user migration to Supabase...");
        
        if ($this->dryRun) {
            $this->warn("DRY RUN MODE - No actual changes will be made");
        }

        // Get users to migrate
        $query = User::whereNull('supabase_id')
                    ->orWhere('is_migrated', false);
        
        $totalUsers = $query->count();
        
        if ($totalUsers === 0) {
            $this->info("All users have already been migrated.");
            return 0;
        }

        $this->info("Found {$totalUsers} users to migrate.");

        if (!$force && !$this->dryRun) {
            if (!$this->confirm("Do you want to proceed with migrating {$batchSize} users?")) {
                $this->error("Migration cancelled.");
                return 1;
            }
        }

        // Get users for this batch
        $users = $query->take($batchSize)->get();
        
        $successCount = 0;
        $errorCount = 0;

        $this->line("\nMigrating users...");
        
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            try {
                if (!$this->dryRun) {
                    // Create user in Supabase
                    $response = $this->supabase->adminCreateUser([
                        'email' => $user->email,
                        'email_confirm' => true, // Auto-confirm email
                        'user_metadata' => [
                            'migrated_from' => 'workos',
                            'local_id' => $user->id,
                            'avatar' => $user->avatar,
                            'is_admin' => $user->is_admin,
                            'migrated_at' => now()->toISOString(),
                        ]
                    ]);

                    if (isset($response['error'])) {
                        throw new \Exception($response['error']['message'] ?? 'Unknown error');
                    }

                    // Update local user
                    $user->update([
                        'supabase_id' => $response['id'],
                        'is_migrated' => true,
                    ]);
                }

                $this->line("✓ {$user->email} (ID: {$user->id})");
                $successCount++;
                
            } catch (\Exception $e) {
                $this->error("✗ Failed to migrate {$user->email}: {$e->getMessage()}");
                $errorCount++;
                
                // Log detailed error
                Log::error("User migration failed", [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();

        $this->line("\n\nMigration batch completed:");
        $this->line("  Success: {$successCount} users");
        $this->line("  Errors: {$errorCount} users");
        
        if ($errorCount > 0) {
            $this->warn("Some users failed to migrate. Check logs for details.");
        }

        // Check if there are more users to migrate
        $remainingUsers = User::whereNull('supabase_id')
                              ->orWhere('is_migrated', false)
                              ->count();
        
        if ($remainingUsers > 0) {
            $this->info("\n{$remainingUsers} users remaining to migrate.");
            $this->info("Run the command again to continue migration.");
        } else {
            $this->info("\nAll users have been successfully migrated!");
        }

        return $errorCount > 0 ? 1 : 0;
    }

    /**
     * Generate a random password for migrated users
     */
    protected function generateRandomPassword()
    {
        return Str::random(16) . '!1A'; // Ensure complexity
    }

    /**
     * Validate Supabase configuration
     */
    protected function validateSupabaseConfig()
    {
        $url = config('services.supabase.url');
        $key = config('services.supabase.service_role_key');

        if (!$url || !$key) {
            $this->error("Supabase configuration is missing. Please check your .env file.");
            return false;
        }

        return true;
    }
}
