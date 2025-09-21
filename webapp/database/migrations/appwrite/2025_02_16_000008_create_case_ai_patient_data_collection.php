<?php

use App\Appwrite\Migrations\Migration;
use App\Services\AppwriteService;
use Appwrite\Enums\IndexType;

return new class extends Migration
{
    public function up(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $collectionId = 'case_ai_patient_data';

        $appwrite->ensureCollection($databaseId, $collectionId, 'Case AI Patient Data');
        
        // Essential AI patient data only
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'osce_case_id', 50, true);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'ai_patient_profile', 5000, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'ai_patient_vitals', 2000, false);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'ai_patient_symptoms', 2000, false);

        // Indexes
        $appwrite->ensureIndex($databaseId, $collectionId, 'ai_patient_case_index', IndexType::UNIQUE(), ['osce_case_id']);
    }

    public function down(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $appwrite->deleteCollection($databaseId, 'case_ai_patient_data');
    }
};