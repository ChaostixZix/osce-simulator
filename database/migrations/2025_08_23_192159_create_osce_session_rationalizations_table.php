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
        Schema::create('osce_session_rationalizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('osce_session_id')->constrained('osce_sessions')->cascadeOnDelete();

            // Rationalization status tracking
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->boolean('results_unlocked')->default(false);

            // Post-session diagnosis and plan data
            $table->text('primary_diagnosis')->nullable();
            $table->text('primary_diagnosis_reasoning')->nullable();
            $table->json('differential_diagnoses')->nullable(); // Array of {diagnosis, reasoning}
            $table->longText('care_plan')->nullable(); // Rich text plan

            // Evaluation scores and feedback
            $table->integer('anamnesis_score')->default(0);
            $table->integer('investigations_score')->default(0);
            $table->integer('diagnosis_score')->default(0);
            $table->integer('plan_score')->default(0);
            $table->integer('total_score')->default(0);
            $table->enum('performance_band', ['needs_work', 'satisfactory', 'strong'])->nullable();

            // Overall feedback
            $table->json('strengths')->nullable();
            $table->json('gaps')->nullable();
            $table->json('top_fixes')->nullable();
            $table->text('overall_summary')->nullable();
            $table->json('suggested_study_topics')->nullable();

            // Completion tracking
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['osce_session_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('osce_session_rationalizations');
    }
};
