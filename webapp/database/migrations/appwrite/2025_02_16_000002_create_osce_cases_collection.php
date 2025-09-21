<?php

use App\Appwrite\Migrations\Migration;
use App\Services\AppwriteService;
use Appwrite\Enums\IndexType;

return new class extends Migration
{
    public function up(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $collectionId = 'osce_cases';

        $appwrite->ensureCollection($databaseId, $collectionId, 'OSCE Cases');
        
        // Essential case attributes only
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'title', 255, true);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'description', 2000, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'difficulty', 50, false);
        $appwrite->ensureIntegerAttribute($databaseId, $collectionId, 'duration_minutes', false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'clinical_setting', 100, false);
        $appwrite->ensureIntegerAttribute($databaseId, $collectionId, 'urgency_level', false, 1, 5);
        $appwrite->ensureIntegerAttribute($databaseId, $collectionId, 'is_active', false, 0, 1, 1);

        // Indexes
        $appwrite->ensureIndex($databaseId, $collectionId, 'osce_cases_difficulty_index', IndexType::KEY(), ['difficulty']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'osce_cases_is_active_index', IndexType::KEY(), ['is_active']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'osce_cases_clinical_setting_index', IndexType::KEY(), ['clinical_setting']);
    }

    public function down(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $appwrite->deleteCollection($databaseId, 'osce_cases');
    }
};