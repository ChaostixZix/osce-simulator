<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('osce_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('guest_token_hash', 64)->nullable()->index();
            $table->json('guest_actions')->nullable();
            $table->json('guest_review')->nullable();
        });
        Schema::table('osce_chat_messages', function (Blueprint $table) {
            $table->string('source_topic')->nullable();
            $table->uuid('client_message_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('osce_chat_messages', function (Blueprint $table) {
            $table->dropColumn(['source_topic', 'client_message_id']);
        });
        Schema::table('osce_sessions', function (Blueprint $table) {
            $table->dropColumn(['guest_token_hash', 'guest_actions', 'guest_review']);
        });
        // Keep user_id nullable: rolling back must not delete guest encounters.
    }
};
