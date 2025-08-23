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
        Schema::create('rationalization_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_rationalization_id')->constrained('osce_session_rationalizations')->cascadeOnDelete();
            
            // Evaluation metadata
            $table->enum('evaluation_type', ['anamnesis', 'investigations', 'diagnosis', 'plan']);
            $table->text('section_name'); // Human readable section name
            
            // Section scores and feedback
            $table->integer('section_score')->default(0); // Overall section score
            $table->json('strengths')->nullable(); // Max 3 bullet points
            $table->json('gaps')->nullable(); // Max 3 bullet points  
            $table->json('top_fixes')->nullable(); // Max 3 ordered items
            
            // Gemini grounding metadata
            $table->json('grounding_metadata')->nullable(); // Raw grounding data from Gemini
            $table->json('search_queries')->nullable(); // Queries used for grounding
            $table->text('model_used')->nullable(); // e.g., "gemini-2.5-flash"
            
            // Processing metadata
            $table->timestamp('evaluation_started_at')->nullable();
            $table->timestamp('evaluation_completed_at')->nullable();
            $table->boolean('has_citations')->default(false);
            $table->integer('citation_count')->default(0);
            
            $table->timestamps();
            
            $table->index(['session_rationalization_id', 'evaluation_type']);
            $table->index(['evaluation_type', 'section_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rationalization_evaluations');
    }
};
