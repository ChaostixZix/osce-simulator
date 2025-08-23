<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('osce_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('osce_session_id')->constrained('osce_sessions')->onDelete('cascade');
            $table->enum('sender_type', ['user', 'ai_patient', 'system']);
            $table->text('message');
            $table->json('metadata')->nullable(); // For storing additional context like patient symptoms, vital signs, etc.
            $table->timestamp('sent_at');
            $table->timestamps();

            // Indexes for better performance
            $table->index(['osce_session_id', 'sent_at']);
            $table->index('sender_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('osce_chat_messages');
    }
};
