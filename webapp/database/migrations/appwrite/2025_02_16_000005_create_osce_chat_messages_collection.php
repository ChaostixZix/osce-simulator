<?php

use App\Appwrite\Migrations\Migration;
use App\Services\AppwriteService;
use Appwrite\Enums\IndexType;

return new class extends Migration
{
    public function up(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $collectionId = 'osce_chat_messages';

        $appwrite->ensureCollection($databaseId, $collectionId, 'OSCE Chat Messages');
        
        // References
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'osce_session_id', 50, true);
        
        // Message data
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'sender_type', 50, true);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'message', 10000, true);
        $appwrite->ensureStringAttribute($databaseId, $collectionId, 'metadata', 5000, false);
        $appwrite->ensureDatetimeAttribute($databaseId, $collectionId, 'sent_at', true);

        // Indexes
        $appwrite->ensureIndex($databaseId, $collectionId, 'chat_session_sent_index', IndexType::KEY(), ['osce_session_id', 'sent_at']);
        $appwrite->ensureIndex($databaseId, $collectionId, 'chat_sender_type_index', IndexType::KEY(), ['sender_type']);
    }

    public function down(AppwriteService $appwrite): void
    {
        $databaseId = config('appwrite.database_id');
        $appwrite->deleteCollection($databaseId, 'osce_chat_messages');
    }
};