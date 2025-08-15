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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('osce_sessions')->onDelete('cascade');
            $table->enum('type', ['user', 'patient', 'system']);
            $table->text('content');
            $table->string('action_type')->default('conversation');
            $table->json('metadata')->nullable();
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamps();
            
            $table->index(['session_id', 'timestamp']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
