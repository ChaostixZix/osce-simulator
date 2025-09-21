<?php

use App\Appwrite\Migrations\Migration;
use App\Services\AppwriteService;
use Appwrite\Enums\IndexType;

return new class extends Migration
{
    public function up(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $collectionId = 'session_ordered_tests';

        $appwrite->ensureCollection($databaseId, $collectionId, 'Session Ordered Tests');
        
        // References
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'osce_session_id', 50, true);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'medical_test_id', 50, false);
        
        // Test identification
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'test_type', 100, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'test_name', 255, true);
        
        // Test execution
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'results', 10000, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'clinical_reasoning', 5000, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'priority', 50, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'cost', 20, false); // decimal as string
        
        // Timing
        $appwrite->ensureDatetimeAttribute($databaseId, $collectionId, 'ordered_at', true);
        $appwrite->ensureDatetimeAttribute($databaseId, $collectionId, 'results_available_at', false);
        $appwrite->ensureDatetimeAttribute($databaseId, $collectionId, 'completed_at', false);

        // Indexes
        $appwrite->ensureIndex($databaseId, $collectionId, 'tests_session_ordered_index', IndexType::KEY(), ['osce_session_id', 'ordered_at']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'tests_type_name_index', IndexType::KEY(), ['test_type', 'test_name']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'tests_priority_index', IndexType::KEY(), ['priority']);
    }

    public function down(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $appwrite->deleteCollection($databaseId, 'session_ordered_tests');
    }
};