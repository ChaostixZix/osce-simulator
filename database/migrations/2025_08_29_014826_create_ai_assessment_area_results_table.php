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
        Schema::create('ai_assessment_area_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_assessment_run_id')->constrained()->onDelete('cascade');
            $table->string('clinical_area'); // 'history', 'exam', 'investigations', 'differential_diagnosis', 'management'
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'fallback'])->default('pending');
            $table->integer('score')->nullable();
            $table->integer('max_score');
            $table->text('justification')->nullable();
            $table->json('raw_response')->nullable(); // Store the raw AI response
            $table->integer('response_length')->nullable();
            $table->integer('attempts')->default(0);
            $table->boolean('was_repaired')->default(false);
            $table->text('error_message')->nullable();
            $table->json('telemetry')->nullable(); // Logs: attempts, repair info, etc.
            $table->timestamps();

            $table->index(['ai_assessment_run_id', 'clinical_area']);
            $table->index(['ai_assessment_run_id', 'status']);
            $table->unique(['ai_assessment_run_id', 'clinical_area']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_assessment_area_results');
    }
};
