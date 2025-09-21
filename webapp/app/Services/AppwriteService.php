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
     * Lightweight connectivity probe to validate credentials.
     *
     * @return array<string, mixed>
     */
    public function testConnectivity(): array
    {
        $this->ensureClient();

        $databases = $this->databases->list();

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

        foreach (['endpoint', 'project_id', 'api_key', 'database_id', 'migrations_collection_id'] as $key) {
            if (empty($this->config[$key])) {
                throw new RuntimeException("Appwrite configuration [{$key}] is missing.");
            }
        }
    }

    private function collectionPermissions(): array
    {
        $permissions = [];

        foreach ($this->config['permissions']['read'] ?? [] as $role) {
            $permissions[] = Permission::read($role);
        }

        foreach ($this->config['permissions']['create'] ?? [] as $role) {
            $permissions[] = Permission::create($role);
        }

        foreach ($this->config['permissions']['update'] ?? [] as $role) {
            $permissions[] = Permission::update($role);
        }

        foreach ($this->config['permissions']['delete'] ?? [] as $role) {
            $permissions[] = Permission::delete($role);
        }

        return array_values(array_unique(array_filter($permissions)));
    }

    private function isNotFound(AppwriteException $exception): bool
    {
        return (int) $exception->getCode() === 404;
    }

    private function wrapException(string $message, AppwriteException $exception): RuntimeException
    {
        return new RuntimeException("{$message}: {$exception->getMessage()}", $exception->getCode(), $exception);
    }
}
