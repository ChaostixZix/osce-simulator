<?php

use App\Appwrite\Migrations\Migration;
use App\Services\AppwriteService;
use Appwrite\Enums\IndexType;

return new class extends Migration
{
    public function up(AppwriteService $appwrite): void
    {
        $appwrite->ensureBaseline();

        $databaseId = config('appwrite.database_id');
        $collectionId = 'health_checks';

        $appwrite->ensureCollection($databaseId, $collectionId, 'Health Checks');
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'status', 32, true);
        $appwrite->ensureDatetimeAttribute($databaseId, $collectionId, 'checked_at', true);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'details', 1024, false);

        $appwrite->ensureIndex(
            $databaseId,
            $collectionId,
            'health_checks_status_index',
            IndexType::KEY(),
            ['status']
        );

        $appwrite->ensureIndex(
            $databaseId,
            $collectionId,
            'health_checks_checked_at_index',
            IndexType::KEY(),
            ['checked_at']
        );
    }

    public function down(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');

        $appwrite->deleteCollection($databaseId, 'health_checks');
    }
};
