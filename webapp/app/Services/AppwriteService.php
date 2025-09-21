<?php

namespace App\Services;

use Appwrite\AppwriteException;
use Appwrite\Client;
use Appwrite\Enums\IndexType;
use Appwrite\ID;
use Appwrite\Permission;
use Appwrite\Query;
use Appwrite\Services\Databases;
use Illuminate\Support\Carbon;
use RuntimeException;

class AppwriteService
{
    private ?Client $client = null;

    private ?Databases $databases = null;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('appwrite', []);
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    public function ensureBaseline(): void
    {
        $this->ensureClient();

        $databaseId = $this->config['database_id'];
        $databaseName = $this->config['database_name'];

        $this->ensureDatabase($databaseId, $databaseName);

        $collectionId = $this->config['migrations_collection_id'];
        $collectionName = $this->config['migrations_collection_name'];

        $this->ensureCollection($databaseId, $collectionId, $collectionName, $this->collectionPermissions(), false);
        $this->ensureStringAttribute($databaseId, $collectionId, 'migration', 255, true);
        $this->ensureIntegerAttribute($databaseId, $collectionId, 'batch', true, 0, null, 1);
        $this->ensureDatetimeAttribute($databaseId, $collectionId, 'ran_at', true);
    }

    /**
     * @return array<int, array{name: string, batch: int, ran_at: string|null, id: string|null}>
     */
    public function listRanMigrations(): array
    {
        $this->ensureBaseline();

        $response = $this->databases->listDocuments(
            $this->config['database_id'],
            $this->config['migrations_collection_id'],
            [
                Query::orderAsc('batch'),
                Query::orderAsc('migration'),
            ]
        );

        $documents = $response['documents'] ?? [];

        return array_map(function (array $document): array {
            return [
                'name' => $document['migration'] ?? '',
                'batch' => (int) ($document['batch'] ?? 0),
                'ran_at' => $document['ran_at'] ?? null,
                'id' => $document['$id'] ?? null,
            ];
        }, $documents);
    }

    public function markMigrationRan(string $name, int $batch): void
    {
        $this->ensureBaseline();

        $this->databases->createDocument(
            $this->config['database_id'],
            $this->config['migrations_collection_id'],
            ID::unique(),
            [
                'migration' => $name,
                'batch' => $batch,
                'ran_at' => Carbon::now()->toIso8601String(),
            ]
        );
    }

    public function forgetMigration(string $name): void
    {
        $this->ensureBaseline();

        $documents = $this->databases->listDocuments(
            $this->config['database_id'],
            $this->config['migrations_collection_id'],
            [Query::equal('migration', $name)]
        );

        foreach ($documents['documents'] ?? [] as $document) {
            if (! isset($document['$id'])) {
                continue;
            }

            $this->databases->deleteDocument(
                $this->config['database_id'],
                $this->config['migrations_collection_id'],
                $document['$id']
            );
        }
    }

    public function resetMigrationLog(): void
    {
        $records = $this->listRanMigrations();

        foreach ($records as $record) {
            if (empty($record['id'])) {
                continue;
            }

            $this->databases->deleteDocument(
                $this->config['database_id'],
                $this->config['migrations_collection_id'],
                $record['id']
            );
        }
    }

    /**
     * Validate configuration without making network calls.
     */
    public function validateConfig(): bool
    {
        $this->guardConfigured();
        
        // Validate permissions by trying to create them
        $this->collectionPermissions();
        
        return true;
    }
    public function testConnectivity(): array
    {
        $this->ensureClient();

        $databases = $this->retryOperation(fn() => $this->databases->list());

        $this->ensureBaseline();
        $ran = $this->listRanMigrations();

        return [
            'databases_total' => $databases['total'] ?? null,
            'database_id' => $this->config['database_id'],
            'migrations_collection_id' => $this->config['migrations_collection_id'],
            'migrations_count' => count($ran),
        ];
    }

    public function ensureDatabase(string $databaseId, string $name, bool $enabled = true): array
    {
        $this->ensureClient();

        try {
            return $this->databases->get($databaseId);
        } catch (AppwriteException $exception) {
            if (! $this->isNotFound($exception)) {
                throw $this->wrapException('Unable to fetch Appwrite database', $exception);
            }
        }

        return $this->databases->create($databaseId, $name, $enabled);
    }

    public function ensureCollection(string $databaseId, string $collectionId, string $name, array $permissions = [], bool $documentSecurity = false, bool $enabled = true): array
    {
        $this->ensureClient();

        try {
            return $this->databases->getCollection($databaseId, $collectionId);
        } catch (AppwriteException $exception) {
            if (! $this->isNotFound($exception)) {
                throw $this->wrapException('Unable to fetch Appwrite collection', $exception);
            }
        }

        $permissions = empty($permissions) ? $this->collectionPermissions() : $permissions;

        return $this->databases->createCollection(
            $databaseId,
            $collectionId,
            $name,
            $permissions,
            $documentSecurity,
            $enabled
        );
    }

    public function deleteCollection(string $databaseId, string $collectionId): void
    {
        $this->ensureClient();

        try {
            $this->databases->deleteCollection($databaseId, $collectionId);
        } catch (AppwriteException $exception) {
            if (! $this->isNotFound($exception)) {
                throw $this->wrapException("Unable to delete Appwrite collection [{$collectionId}]", $exception);
            }
        }
    }

    public function deleteDatabase(string $databaseId): void
    {
        $this->ensureClient();

        try {
            $this->databases->delete($databaseId);
        } catch (AppwriteException $exception) {
            if (! $this->isNotFound($exception)) {
                throw $this->wrapException("Unable to delete Appwrite database [{$databaseId}]", $exception);
            }
        }
    }

    public function ensureStringAttribute(string $databaseId, string $collectionId, string $key, int $size, bool $required = false, ?string $default = null, bool $isArray = false, ?bool $encrypt = null): array
    {
        $this->ensureClient();

        try {
            return $this->databases->getAttribute($databaseId, $collectionId, $key);
        } catch (AppwriteException $exception) {
            if (! $this->isNotFound($exception)) {
                throw $this->wrapException("Unable to fetch Appwrite attribute [{$key}]", $exception);
            }
        }

        return $this->databases->createStringAttribute(
            $databaseId,
            $collectionId,
            $key,
            $size,
            $required,
            $required ? null : $default,
            $isArray ? true : null,
            $encrypt
        );
    }

    public function ensureIntegerAttribute(string $databaseId, string $collectionId, string $key, bool $required = false, ?int $min = null, ?int $max = null, ?int $default = null, bool $isArray = false): array
    {
        $this->ensureClient();

        try {
            return $this->databases->getAttribute($databaseId, $collectionId, $key);
        } catch (AppwriteException $exception) {
            if (! $this->isNotFound($exception)) {
                throw $this->wrapException("Unable to fetch Appwrite attribute [{$key}]", $exception);
            }
        }

        return $this->databases->createIntegerAttribute(
            $databaseId,
            $collectionId,
            $key,
            $required,
            $min,
            $max,
            $required ? null : $default,
            $isArray ? true : null
        );
    }

    public function ensureDatetimeAttribute(string $databaseId, string $collectionId, string $key, bool $required = false, ?string $default = null, bool $isArray = false): array
    {
        $this->ensureClient();

        try {
            return $this->databases->getAttribute($databaseId, $collectionId, $key);
        } catch (AppwriteException $exception) {
            if (! $this->isNotFound($exception)) {
                throw $this->wrapException("Unable to fetch Appwrite attribute [{$key}]", $exception);
            }
        }

        return $this->databases->createDatetimeAttribute(
            $databaseId,
            $collectionId,
            $key,
            $required,
            $required ? null : $default,
            $isArray ? true : null
        );
    }

    public function ensureIndex(string $databaseId, string $collectionId, string $key, IndexType $type, array $attributes, ?array $orders = null): array
    {
        $this->ensureClient();

        try {
            return $this->databases->getIndex($databaseId, $collectionId, $key);
        } catch (AppwriteException $exception) {
            if (! $this->isNotFound($exception)) {
                throw $this->wrapException("Unable to fetch Appwrite index [{$key}]", $exception);
            }
        }

        return $this->databases->createIndex($databaseId, $collectionId, $key, $type, $attributes, $orders);
    }

    private function ensureClient(): void
    {
        if ($this->databases instanceof Databases) {
            return;
        }

        $this->guardConfigured();

        $client = new Client();
        $client
            ->setEndpoint($this->config['endpoint'])
            ->setProject($this->config['project_id'])
            ->setKey($this->config['api_key']);

        if (! empty($this->config['self_signed'])) {
            $client->setSelfSigned(true);
        }

        $this->client = $client;
        $this->databases = new Databases($client);
    }

    private function guardConfigured(): void
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('Appwrite integration is disabled. Set APPWRITE_ENABLED=true to use TablesDB.');
        }

        $requiredKeys = ['endpoint', 'project_id', 'api_key', 'database_id', 'migrations_collection_id'];
        $missingKeys = [];

        foreach ($requiredKeys as $key) {
            if (empty($this->config[$key])) {
                $missingKeys[] = $key;
            }
        }

        if (!empty($missingKeys)) {
            throw new RuntimeException('Appwrite configuration is incomplete. Missing keys: [' . implode(', ', $missingKeys) . '].');
        }

        // Validate endpoint format
        $endpoint = $this->config['endpoint'];
        if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
            throw new RuntimeException("Invalid Appwrite endpoint URL: [{$endpoint}].");
        }

        // Validate project ID format (should be alphanumeric with limited special chars)
        $projectId = $this->config['project_id'];
        if (!preg_match('/^[a-zA-Z0-9_-]{3,36}$/', $projectId)) {
            throw new RuntimeException("Invalid Appwrite project ID format: [{$projectId}]. Should be 3-36 characters long and contain only alphanumeric characters, hyphens, and underscores.");
        }

        // Basic API key validation (should not be a placeholder)
        $apiKey = $this->config['api_key'];
        if (in_array(strtolower($apiKey), ['your_appwrite_api_key', 'changeme', 'placeholder', ''])) {
            throw new RuntimeException('Appwrite API key appears to be a placeholder. Please set a valid API key.');
        }
    }

    private function collectionPermissions(): array
    {
        $permissions = [];

        foreach ($this->config['permissions']['read'] ?? [] as $role) {
            $this->validateRole($role);
            $permissions[] = Permission::read($role);
        }

        foreach ($this->config['permissions']['create'] ?? [] as $role) {
            $this->validateRole($role);
            $permissions[] = Permission::create($role);
        }

        foreach ($this->config['permissions']['update'] ?? [] as $role) {
            $this->validateRole($role);
            $permissions[] = Permission::update($role);
        }

        foreach ($this->config['permissions']['delete'] ?? [] as $role) {
            $this->validateRole($role);
            $permissions[] = Permission::delete($role);
        }

        return array_values(array_unique(array_filter($permissions)));
    }

    private function validateRole(string $role): void
    {
        if (empty($role)) {
            throw new RuntimeException('Permission role cannot be empty.');
        }

        // Check for valid role formats (role:all, role:member, users, user:ID, team:ID, etc.)
        $validPatterns = [
            '/^role:(all|member|guest)$/',
            '/^users?$/',
            '/^user:[a-zA-Z0-9_-]+$/',
            '/^team:[a-zA-Z0-9_-]+$/',
            '/^label:[a-zA-Z0-9_-]+$/',
        ];

        $isValid = false;
        foreach ($validPatterns as $pattern) {
            if (preg_match($pattern, $role)) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid) {
            throw new RuntimeException("Invalid permission role format: [{$role}]. Expected formats: role:all, role:member, users, user:ID, team:ID, or label:ID.");
        }
    }

    private function isNotFound(AppwriteException $exception): bool
    {
        return (int) $exception->getCode() === 404;
    }

    private function wrapException(string $message, AppwriteException $exception): RuntimeException
    {
        return new RuntimeException("{$message}: {$exception->getMessage()}", $exception->getCode(), $exception);
    }

    /**
     * Retry an operation with exponential backoff for transient failures.
     */
    private function retryOperation(callable $operation, int $maxRetries = 3): mixed
    {
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $operation();
            } catch (AppwriteException $exception) {
                $lastException = $exception;
                
                // Don't retry for client errors (4xx)
                $statusCode = (int) $exception->getCode();
                if ($statusCode >= 400 && $statusCode < 500) {
                    throw $exception;
                }
                
                // Don't retry on the last attempt
                if ($attempt === $maxRetries) {
                    break;
                }
                
                // Exponential backoff: 1s, 2s, 4s
                $delay = pow(2, $attempt - 1);
                sleep($delay);
            }
        }
        
        throw $lastException;
    }
}
