<?php

use App\Appwrite\Migrations\Migration;
use App\Services\AppwriteService;
use Appwrite\Enums\IndexType;

return new class extends Migration
{
    public function up(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $collectionId = 'case_test_categories';

        $appwrite->ensureCollection($databaseId, $collectionId, 'Case Test Categories');
        
        // Reference to case
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'osce_case_id', 50, true);
        
        // Test categorization
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'highly_appropriate_tests', 10000, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'appropriate_tests', 10000, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'acceptable_tests', 10000, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'inappropriate_tests', 10000, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'contraindicated_tests', 10000, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'required_tests', 10000, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'test_results_templates', 10000, false);

        // Indexes
        $appwrite->ensureIndex($databaseId, $collectionId, 'test_categories_case_index', IndexType::UNIQUE(), ['osce_case_id']);
    }

    public function down(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $appwrite->deleteCollection($databaseId, 'case_test_categories');
    }
};