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
        Schema::create('osce_diagnosis_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_rationalization_id')->constrained('osce_session_rationalizations')->cascadeOnDelete();

            // Diagnosis information
            $table->text('diagnosis_name');
            $table->text('reasoning'); // User's reasoning for this diagnosis
            $table->enum('diagnosis_type', ['primary', 'differential']);
            $table->integer('order_index')->default(0); // For differential diagnosis ordering

            // Evaluation results
            $table->text('evaluation_summary')->nullable(); // One sentence summary
            $table->enum('verdict', ['correct', 'partially_correct', 'incorrect'])->nullable();
            $table->text('feedback_why')->nullable(); // Evidence-based feedback
            $table->integer('score')->default(0); // 0-10 score
            $table->json('citations')->nullable(); // Citation objects from Gemini

            // Scoring breakdown (same rubric as cards)
            $table->integer('relevance_score')->default(0);
            $table->integer('evidence_accuracy_score')->default(0);
            $table->integer('completeness_score')->default(0);
            $table->integer('safety_score')->default(0);
            $table->integer('prioritization_score')->default(0);

            // Metadata
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('evaluated_at')->nullable();

            $table->timestamps();

            $table->index(['session_rationalization_id', 'diagnosis_type']);
            $table->index(['diagnosis_type', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('osce_diagnosis_entries');
    }
};
