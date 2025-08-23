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
        Schema::create('anamnesis_rationalization_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_rationalization_id')->constrained('osce_session_rationalizations')->cascadeOnDelete();

            // Card type: 'asked_question', 'negative_anamnesis', 'investigation'
            $table->enum('card_type', ['asked_question', 'negative_anamnesis', 'investigation']);

            // The question or investigation being rationalized
            $table->text('question_text'); // Original question asked or expected question not asked
            $table->text('prompt_text'); // Display prompt like "Why did you ask: '...'?"

            // User response
            $table->text('user_rationale')->nullable();
            $table->boolean('marked_as_forgot')->default(false); // For negative anamnesis
            $table->boolean('is_answered')->default(false);

            // Evaluation results
            $table->text('evaluation_summary')->nullable(); // One sentence summary of rationale
            $table->enum('verdict', ['correct', 'partially_correct', 'incorrect'])->nullable();
            $table->text('feedback_why')->nullable(); // 1-2 sentences tied to evidence
            $table->integer('score')->default(0); // 0-10 score
            $table->json('citations')->nullable(); // Array of citation objects

            // Scoring breakdown
            $table->integer('relevance_score')->default(0); // 0-2
            $table->integer('evidence_accuracy_score')->default(0); // 0-3
            $table->integer('completeness_score')->default(0); // 0-2
            $table->integer('safety_score')->default(0); // 0-2
            $table->integer('prioritization_score')->default(0); // 0-1

            // Metadata
            $table->integer('order_index')->default(0); // Display order
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('evaluated_at')->nullable();

            $table->timestamps();

            $table->index(['session_rationalization_id', 'card_type']);
            $table->index(['card_type', 'is_answered']);
            $table->index(['session_rationalization_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anamnesis_rationalization_cards');
    }
};
