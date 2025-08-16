<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osce_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->unsignedBigInteger('case_id');
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->decimal('score', 5, 2)->nullable(); // percentage score
            $table->json('checklist_progress'); // current checklist state
            $table->json('conversation_log'); // patient simulation conversation
            $table->json('performance_data')->nullable(); // detailed performance metrics
            $table->text('feedback')->nullable(); // AI-generated feedback
            $table->timestamps();

            $table->foreign('session_id')->references('session_id')->on('sessions')->onDelete('cascade');
            $table->foreign('case_id')->references('id')->on('osce_cases')->onDelete('cascade');
            $table->index(['session_id', 'status']);
            $table->index(['case_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osce_sessions');
    }
};