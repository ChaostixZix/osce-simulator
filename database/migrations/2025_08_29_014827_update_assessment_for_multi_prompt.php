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
        // First, update the clinical areas in the existing model
        // We'll need to update this in code since the CLINICAL_AREAS is a const
        
        // Add a new table for storing detailed aspect results
        Schema::create('ai_assessment_aspect_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_assessment_area_result_id')->constrained()->onDelete('cascade');
            $table->string('aspect'); // e.g., 'systematic_approach', 'thoroughness', 'technique'
            $table->integer('score')->default(0);
            $table->integer('max_score')->default(0);
            $table->string('performance_level')->nullable(); // 'acceptable', 'good', 'excellent'
            $table->text('feedback')->nullable();
            $table->text('citations')->nullable(); // JSON array of evidence citations
            $table->timestamps();
            
            $table->index(['ai_assessment_area_result_id', 'aspect']);
        });
        
        // Add new columns to ai_assessment_area_results for detailed breakdown
        Schema::table('ai_assessment_area_results', function (Blueprint $table) {
            $table->json('aspect_breakdown')->nullable(); // Store detailed aspect scores
            $table->string('overall_performance_level')->nullable(); // 'acceptable', 'good'
            $table->text('detailed_feedback')->nullable(); // More detailed feedback
            $table->integer('acceptable_threshold')->nullable(); // e.g., 60% of max_score
            $table->integer('good_threshold')->nullable(); // e.g., 80% of max_score
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_assessment_aspect_results');
        
        Schema::table('ai_assessment_area_results', function (Blueprint $table) {
            $table->dropColumn([
                'aspect_breakdown',
                'overall_performance_level',
                'detailed_feedback',
                'acceptable_threshold',
                'good_threshold'
            ]);
        });
    }
};