<?php

namespace App\Console\Commands;

use App\Appwrite\Migrations\Migration as AppwriteMigration;
use App\Services\AppwriteService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AppwriteMigrateCommand extends Command
{
    protected $signature = 'appwrite:migrate
        {action=up : Operation to perform (up|down|refresh|status|test)}
        {--steps=1 : Number of migrations to rollback when running the down action}
        {--path=* : Additional directories containing Appwrite migrations}
        {--force : Force operations that would otherwise require confirmation}
        {--dry-run : Simulate the action without persisting changes}';

    protected $description = 'Run and manage Appwrite TablesDB migrations.';

    public function __construct(private readonly AppwriteService $appwrite)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->appwrite->isEnabled()) {
            $this->error('Appwrite integration is disabled. Enable it by setting APPWRITE_ENABLED=true in your environment.');

            return self::FAILURE;
        }

        $action = strtolower((string) $this->argument('action'));

        try {
            return match ($action) {
                'up' => $this->handleUp(),
                'down' => $this->handleDown(),
                'refresh' => $this->handleRefresh(),
                'status' => $this->handleStatus(),
                'test', 'verify' => $this->handleTest(),
                default => $this->invalidAction($action),
            };
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    protected function handleUp(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $available = $this->availableMigrations();
        if (empty($available)) {
            $this->info('No Appwrite migrations were found.');

            return self::SUCCESS;
        }

        $ran = $this->appwrite->listRanMigrations();
        $ranNames = array_map(static fn (array $record): string => $record['name'], $ran);

        $pending = array_filter(
            $available,
            static fn (string $path, string $name): bool => ! in_array($name, $ranNames, true),
            ARRAY_FILTER_USE_BOTH
        );

        if (empty($pending)) {
            $this->info('Appwrite is already up to date.');

            return self::SUCCESS;
        }

        $batch = $this->determineNextBatch($ran);

        foreach ($pending as $name => $path) {
            if ($dryRun) {
                $this->line("[dry-run] Would run migration: {$name}");
                continue;
            }

            $this->components->task("Running {$name}", function () use ($path, $name, $batch): void {
                // Check again if migration was already run (prevents race conditions)
                $currentRan = $this->appwrite->listRanMigrations();
                $currentRanNames = array_map(static fn (array $record): string => $record['name'], $currentRan);
                
                if (in_array($name, $currentRanNames, true)) {
                    $this->warn("Migration {$name} was already run by another process, skipping.");
                    return;
                }
                
                $migration = $this->resolveMigration($path, $name);
                $migration->up($this->appwrite);
                $this->appwrite->markMigrationRan($name, $batch);
            });
        }

        if (! $dryRun) {
            $this->info('Appwrite migrations completed successfully.');
        }

        return self::SUCCESS;
    }

    protected function handleDown(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $steps = max(1, (int) $this->option('steps'));

        $ran = $this->appwrite->listRanMigrations();
        if (empty($ran)) {
            $this->info('No Appwrite migrations have been executed.');

            return self::SUCCESS;
        }

        $records = $this->sliceForRollback($ran, $steps);
        if (empty($records)) {
            $this->info('Nothing to rollback.');

            return self::SUCCESS;
        }

        if (! $dryRun && ! $this->option('force')) {
            $names = implode(', ', array_map(static fn (array $record): string => $record['name'], $records));
            if (! $this->confirm("Rollback Appwrite migrations: {$names}?")) {
                $this->line('Rollback cancelled.');

                return self::SUCCESS;
            }
        }

        $this->runRollbackRecords($records, $dryRun);

        if (! $dryRun) {
            $this->info('Rollback finished.');
        }

        return self::SUCCESS;
    }

    protected function handleRefresh(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->option('force') && ! $this->confirm('This will rollback and re-run all Appwrite migrations. Continue?')) {
            $this->line('Refresh cancelled.');

            return self::SUCCESS;
        }

        $ran = $this->appwrite->listRanMigrations();
        $this->runRollbackRecords($this->sliceForRollback($ran, count($ran)), $dryRun);

        if ($dryRun) {
            $this->line('[dry-run] Would clear Appwrite migration log and re-run all migrations.');

            return self::SUCCESS;
        }

        $this->appwrite->resetMigrationLog();

        return $this->handleUp();
    }

    protected function handleStatus(): int
    {
        $ran = $this->appwrite->listRanMigrations();
        $available = $this->availableMigrations();

        if (empty($ran)) {
            $this->info('No Appwrite migrations have been executed.');

            return self::SUCCESS;
        }

        $rows = array_map(function (array $record) use ($available): array {
            $ranAt = $record['ran_at'] ?? null;

            return [
                $record['batch'],
                $record['name'],
                $ranAt ? Carbon::parse($ranAt)->toDateTimeString() : '—',
                isset($available[$record['name']]) ? 'Yes' : 'Missing',
            ];
        }, $ran);

        $this->table(['Batch', 'Migration', 'Ran At', 'File Present'], $rows);

        return self::SUCCESS;
    }

    protected function handleTest(): int
    {
        if ($this->option('dry-run')) {
            $this->line('[dry-run] Would attempt an Appwrite connectivity test.');

            return self::SUCCESS;
        }

        $results = $this->appwrite->testConnectivity();

        foreach ($results as $key => $value) {
            $label = Str::headline((string) $key);
            $this->line(sprintf('%s: %s', $label, $value ?? 'n/a'));
        }

        $this->info('Appwrite connectivity verified.');

        return self::SUCCESS;
    }

    protected function invalidAction(string $action): int
    {
        $this->error("Unknown action '{$action}'. Expected one of: up, down, refresh, status, test.");

        return self::INVALID;
    }

    /**
     * @return array<string, string>
     */
    protected function availableMigrations(): array
    {
        $files = [];

        foreach ($this->migrationPaths() as $path) {
            if (! is_dir($path)) {
                continue;
            }

            foreach (glob(rtrim($path, DIRECTORY_SEPARATOR).'/*.php') ?: [] as $file) {
                $files[$this->migrationName($file)] = $file;
            }
        }

        ksort($files);

        return $files;
    }

    /**
     * @return array<int, string>
     */
    protected function migrationPaths(): array
    {
        $paths = [config('appwrite.migrations.path')];
        $extra = array_filter((array) $this->option('path'));

        foreach ($extra as $path) {
            $paths[] = $this->normalizePath($path);
        }

        return array_values(array_unique(array_filter($paths)));
    }

    protected function normalizePath(string $path): string
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) ? $path : base_path($path);
    }

    protected function migrationName(string $file): string
    {
        return basename($file, '.php');
    }

    protected function determineNextBatch(array $ran): int
    {
        if (empty($ran)) {
            return 1;
        }

        $max = max(array_map(static fn (array $record): int => (int) $record['batch'], $ran));

        return $max + 1;
    }

    /**
     * @param array<int, array{name: string, batch: int}> $ran
     * @return array<int, array{name: string, batch: int}>
     */
    protected function sliceForRollback(array $ran, int $steps): array
    {
        if ($steps <= 0) {
            return [];
        }

        $ordered = array_reverse($ran);

        return array_slice($ordered, 0, $steps);
    }

    protected function runRollbackRecords(array $records, bool $dryRun): void
    {
        if (empty($records)) {
            return;
        }

        $available = $this->availableMigrations();

        foreach ($records as $record) {
            $name = $record['name'];
            $path = $available[$name] ?? null;

            if (! $path || ! is_file($path)) {
                $this->warn("Missing migration file for {$name}; removing from log only.");

                if (! $dryRun) {
                    $this->appwrite->forgetMigration($name);
                }

                continue;
            }

            if ($dryRun) {
                $this->line("[dry-run] Would rollback migration: {$name}");
                continue;
            }

            $this->components->task("Rolling back {$name}", function () use ($path, $name): void {
                $migration = $this->resolveMigration($path, $name);
                $migration->down($this->appwrite);
                $this->appwrite->forgetMigration($name);
            });
        }
    }

    protected function resolveMigration(string $file, string $name): AppwriteMigration
    {
        $resolved = require $file;

        if (is_string($resolved) && class_exists($resolved)) {
            $resolved = app($resolved);
        }

        if ($resolved instanceof AppwriteMigration) {
            return $resolved;
        }

        if (is_object($resolved) && is_subclass_of($resolved::class, AppwriteMigration::class)) {
            /** @var AppwriteMigration $resolved */
            return $resolved;
        }

        throw new RuntimeException("Migration [{$name}] must return an instance of " . AppwriteMigration::class . '.');
    }
}
