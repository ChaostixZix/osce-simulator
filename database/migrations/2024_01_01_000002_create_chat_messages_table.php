<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('content');
            $table->boolean('is_summarized')->default(false);
            $table->text('summary')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->json('metadata')->nullable(); // for additional data like API model used
            $table->timestamps();

            $table->foreign('session_id')->references('session_id')->on('sessions')->onDelete('cascade');
            $table->index(['session_id', 'created_at']);
            $table->index(['session_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};