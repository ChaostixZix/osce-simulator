<?php

use App\Appwrite\Migrations\Migration;
use App\Services\AppwriteService;
use Appwrite\Enums\IndexType;

return new class extends Migration
{
    public function up(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $collectionId = 'osce_sessions';

        $appwrite->ensureCollection($databaseId, $collectionId, 'OSCE Sessions');
        
        // Essential session attributes only
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'user_id', 50, true);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'osce_case_id', 50, true);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'status', 50, true);
        $appwrite->ensureDatetimeAttribute($databaseId, $collectionId, 'started_at', false);
        $appwrite->ensureDatetimeAttribute($databaseId, $collectionId, 'completed_at', false);
        $appwrite->ensureIntegerAttribute($databaseId, $collectionId, 'score', false);
        $appwrite->ensureIntegerAttribute($databaseId, $collectionId, 'max_score', false);
        $appwrite->ensureIntegerAttribute($databaseId, $collectionId, 'current_remaining_seconds', false);

        // Indexes
        $appwrite->ensureIndex($databaseId, $collectionId, 'sessions_user_status_index', IndexType::KEY(), ['user_id', 'status']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'sessions_status_index', IndexType::KEY(), ['status']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'sessions_started_at_index', IndexType::KEY(), ['started_at']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'sessions_paused_at_index', IndexType::KEY(), ['paused_at']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'sessions_status_paused_index', IndexType::KEY(), ['status', 'paused_at']);
    }

    public function down(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $appwrite->deleteCollection($databaseId, 'osce_sessions');
    }
};