<?php

use App\Appwrite\Migrations\Migration;
use App\Services\AppwriteService;
use Appwrite\Enums\IndexType;

return new class extends Migration
{
    public function up(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $collectionId = 'notifications';

        $appwrite->ensureCollection($databaseId, $collectionId, 'Notifications');
        
        // References
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'user_id', 50, true);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'from_user_id', 50, false);
        
        // Notification data
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'type', 100, true);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'data', 10000, false);
        
        // Read status
        $appwrite->ensureIntegerAttribute($databaseId, $collectionId, 'read', false, 0, 1, 0);
        $appwrite->ensureDatetimeAttribute($databaseId, $collectionId, 'read_at', false);

        // Indexes
        $appwrite->ensureIndex($databaseId, $collectionId, 'notifications_user_read_index', IndexType::KEY(), ['user_id', 'read', '$createdAt']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'notifications_type_index', IndexType::KEY(), ['type']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'notifications_from_user_index', IndexType::KEY(), ['from_user_id', '$createdAt']);
    }

    public function down(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $appwrite->deleteCollection($databaseId, 'notifications');
    }
};