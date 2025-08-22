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
            // Available options for ordering
            $table->json('available_labs')->nullable(); // Available lab tests that can be ordered
            $table->json('available_procedures')->nullable(); // Available procedures that can be ordered
            $table->json('available_examinations')->nullable(); // Available physical examinations
            
            // Pre-defined results templates
            $table->json('lab_results_templates')->nullable(); // Pre-defined lab results for each test
            $table->json('procedure_results_templates')->nullable(); // Pre-defined procedure results
            $table->json('physical_exam_findings')->nullable(); // Pre-defined physical examination findings
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('osce_cases', function (Blueprint $table) {
            $table->dropColumn([
                'available_labs',
                'available_procedures', 
                'available_examinations',
                'lab_results_templates',
                'procedure_results_templates',
                'physical_exam_findings'
            ]);
        });
    }
};