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
        Schema::table('osce_cases', function (Blueprint $table) {
            $table->text('ai_patient_profile')->nullable(); // Detailed patient profile for AI simulation
            $table->json('ai_patient_vitals')->nullable(); // Patient vital signs and baseline data
            $table->json('ai_patient_symptoms')->nullable(); // Patient symptoms and presentation
            $table->text('ai_patient_instructions')->nullable(); // Instructions for AI patient behavior
            $table->json('ai_patient_responses')->nullable(); // Predefined responses for common scenarios
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('osce_cases', function (Blueprint $table) {
            $table->dropColumn([
                'ai_patient_profile',
                'ai_patient_vitals',
                'ai_patient_symptoms',
                'ai_patient_instructions',
                'ai_patient_responses',
            ]);
        });
    }
};
