<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osce_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_id')->unique(); // e.g., stemi-001
            $table->string('title');
            $table->text('description');
            $table->string('category'); // e.g., cardiology, emergency
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced']);
            $table->integer('expected_duration')->default(1200); // in seconds
            $table->json('patient_data'); // JSON case data
            $table->json('checklist'); // Performance checklist
            $table->json('scoring_weights'); // Scoring configuration
            $table->json('metadata')->nullable(); // Additional case info
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'difficulty']);
            $table->index(['is_active', 'case_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osce_cases');
    }
};