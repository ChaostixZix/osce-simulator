<?php

use App\Appwrite\Migrations\Migration;
use App\Services\AppwriteService;
use Appwrite\Enums\IndexType;

return new class extends Migration
{
    public function up(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $collectionId = 'users';

        $appwrite->ensureCollection($databaseId, $collectionId, 'Users');
        
        // User attributes
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'name', 255, true);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'email', 255, true);
        $appwrite->ensureDatetimeAttribute($databaseId, $collectionId, 'email_verified_at', false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'workos_id', 255, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'remember_token', 100, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'avatar', 2048, false);
        $appwrite->ensureIntegerAttribute($databaseId, $collectionId, 'is_admin', false, 0, 1, 0);
        $appwrite->ensureIntegerAttribute($databaseId, $collectionId, 'is_banned', false, 0, 1, 0);

        // Indexes
        $appwrite->ensureIndex($databaseId, $collectionId, 'users_email_unique', IndexType::UNIQUE(), ['email']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'users_workos_id_unique', IndexType::UNIQUE(), ['workos_id']);
    }

    public function down(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $appwrite->deleteCollection($databaseId, 'users');
    }
};