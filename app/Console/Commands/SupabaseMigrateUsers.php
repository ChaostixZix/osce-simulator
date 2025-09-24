<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SupabaseMigrateUsers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'supabase:migrate-users 
                           {--batch=50 : Number of users to migrate per batch}
                           {--start=0 : Starting user ID}
                           {--dry-run : Show what would be migrated without actually migrating}
                           {--force : Force migration even if user already has supabase_id}
                           {--email=* : Migrate specific users by email}';

    /**
     * The console command description.
     */
    protected $description = 'Migrate existing users from WorkOS to Supabase authentication';

    protected $supabase;
    protected $migrated = 0;
    protected $failed = 0;
    protected $skipped = 0;

    public function __construct(SupabaseService $supabase)
    {
        parent::__construct();
        $this->supabase = $supabase;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting user migration from WorkOS to Supabase...');
        
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No actual changes will be made');
        }

        // Get users to migrate
        $query = User::query()
            ->where(function ($q) {
                $q->whereNull('supabase_id')
                  ->orWhere('is_migrated', false);
            })
            ->whereNull('deleted_at');

        if ($emails = $this->option('email')) {
            $query->whereIn('email', $emails);
            $this->info("Migrating specific users: " . implode(', ', $emails));
        } else {
            $query->where('id', '>=', $this->option('start'));
        }

        $totalUsers = $query->count();
        $batchSize = (int) $this->option('batch');
        
        if ($totalUsers === 0) {
            $this->info('No users to migrate.');
            return 0;
        }

        $this->info("Found {$totalUsers} users to migrate...");
        $this->line("Processing in batches of {$batchSize}...");

        $progressBar = $this->output->createProgressBar($totalUsers);
        $progressBar->start();

        $query->chunk($batchSize, function ($users) use ($progressBar) {
            foreach ($users as $user) {
                $this->migrateUser($user);
                $progressBar->advance();
            }
            
            // Small delay to avoid rate limiting
            if (!$this->option('dry-run')) {
                usleep(100000); // 100ms delay
            }
        });

        $progressBar->finish();
        $this->line('');

        $this->info('Migration completed!');
        $this->info("Migrated: {$this->migrated}");
        $this->info("Failed: {$this->failed}");
        $this->info("Skipped: {$this->skipped}");

        return $this->failed === 0 ? 0 : 1;
    }

    /**
     * Migrate a single user to Supabase
     */
    protected function migrateUser(User $user)
    {
        // Skip if already migrated and not forced
        if ($user->is_migrated && $user->supabase_id && !$this->option('force')) {
            $this->skipped++;
            $this->line("SKIPPED: User {$user->email} (ID: {$user->id}) already migrated");
            return;
        }

        try {
            // Generate a secure random password
            $password = Str::random(24);
            
            if ($this->option('dry-run')) {
                $this->line("WOULD MIGRATE: User {$user->email} (ID: {$user->id})");
                $this->migrated++;
                return;
            }

            // Create user in Supabase
            $response = $this->supabase->createUser([
                'email' => $user->email,
                'password' => $password,
                'email_confirm' => true,
                'user_metadata' => [
                    'full_name' => $user->name,
                    'avatar_url' => $user->avatar,
                    'migrated_from' => 'workos',
                    'original_id' => $user->id,
                ]
            ]);

            if (isset($response['error'])) {
                $this->handleMigrationError($user, $response['error']);
                return;
            }

            // Update local user record
            DB::transaction(function () use ($user, $response) {
                $user->update([
                    'supabase_id' => $response['id'],
                    'is_migrated' => true,
                    'last_login_at' => $user->last_login_at, // Preserve existing value
                ]);

                // Log migration
                Log::info('User migrated to Supabase', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'supabase_id' => $response['id'],
                ]);
            });

            $this->migrated++;
            $this->line("MIGRATED: User {$user->email} (ID: {$user->id}) → Supabase ID: {$response['id']}");
            
            // Store password for user notification (in a real implementation)
            // This should be emailed to the user securely
            $this->storeGeneratedPassword($user, $password);

        } catch (\Exception $e) {
            $this->handleMigrationError($user, $e->getMessage());
        }
    }

    /**
     * Handle migration errors
     */
    protected function handleMigrationError(User $user, $error)
    {
        $this->failed++;
        $errorMessage = is_array($error) ? json_encode($error) : $error;
        
        $this->error("FAILED: User {$user->email} (ID: {$user->id}) - {$errorMessage}");
        
        Log::error('User migration failed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'error' => $errorMessage,
        ]);
    }

    /**
     * Store generated password for user (placeholder for email notification)
     */
    protected function storeGeneratedPassword(User $user, string $password)
    {
        // In a real implementation, you would:
        // 1. Send an email to the user with their new password
        // 2. Store a hash that the user must change their password on first login
        // 3. Or use password reset flow instead of generating passwords
        
        Log::info('Generated password for migrated user', [
            'user_id' => $user->id,
            'email' => $user->email,
            'password_length' => strlen($password),
        ]);
        
        // For now, just log it - in production, implement secure email delivery
        $this->warn("Generated password for {$user->email}: {$password} (Please email this to the user)");
    }

    /**
     * Get migration statistics
     */
    protected function getMigrationStats()
    {
        $total = User::count();
        $migrated = User::where('is_migrated', true)->count();
        $pending = User::where('is_migrated', false)->orWhereNull('supabase_id')->count();

        return [
            'total' => $total,
            'migrated' => $migrated,
            'pending' => $pending,
            'percentage' => $total > 0 ? round(($migrated / $total) * 100, 2) : 0,
        ];
    }
}