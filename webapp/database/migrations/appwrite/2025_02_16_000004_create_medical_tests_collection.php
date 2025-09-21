<?php

use App\Appwrite\Migrations\Migration;
use App\Services\AppwriteService;
use Appwrite\Enums\IndexType;

return new class extends Migration
{
    public function up(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $collectionId = 'medical_tests';

        $appwrite->ensureCollection($databaseId, $collectionId, 'Medical Tests');
        
        // Essential test attributes only
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'name', 255, true);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'category', 100, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'type', 100, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'cost', 20, false); // decimal as string
        $appwrite->ensureIntegerAttribute($databaseId, $collectionId, 'turnaround_minutes', false);
        $appwrite->ensureIntegerAttribute($databaseId, $collectionId, 'is_active', false, 0, 1, 1);

        // Indexes
        $appwrite->ensureIndex($databaseId, $collectionId, 'medical_tests_name_unique', IndexType::UNIQUE(), ['name']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'medical_tests_category_index', IndexType::KEY(), ['category']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'medical_tests_type_index', IndexType::KEY(), ['type']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'medical_tests_is_active_index', IndexType::KEY(), ['is_active']);
    }

    public function down(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $appwrite->deleteCollection($databaseId, 'medical_tests');
    }
};