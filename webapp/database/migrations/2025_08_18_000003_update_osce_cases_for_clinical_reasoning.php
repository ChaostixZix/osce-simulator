<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('osce_cases', function (Blueprint $table) {
            if (Schema::hasColumn('osce_cases', 'available_labs')) {
                $table->dropColumn([
                    'available_labs',
                    'available_procedures',
                    'available_examinations',
                    'lab_results_templates',
                    'procedure_results_templates',
                    'physical_exam_findings',
                ]);
            }

            $table->json('highly_appropriate_tests')->nullable();
            $table->json('appropriate_tests')->nullable();
            $table->json('acceptable_tests')->nullable();
            $table->json('inappropriate_tests')->nullable();
            $table->json('contraindicated_tests')->nullable();
            $table->json('required_tests')->nullable();

            $table->string('clinical_setting')->default('emergency');
            $table->integer('urgency_level')->default(3);
            $table->json('setting_limitations')->nullable();
            $table->decimal('case_budget', 10, 2)->nullable();

            $table->json('test_results_templates')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('osce_cases', function (Blueprint $table) {
            $table->dropColumn([
                'highly_appropriate_tests',
                'appropriate_tests',
                'acceptable_tests',
                'inappropriate_tests',
                'contraindicated_tests',
                'required_tests',
                'clinical_setting',
                'urgency_level',
                'setting_limitations',
                'case_budget',
                'test_results_templates',
            ]);

            // Do not re-create the old columns in down() to avoid data inconsistency
        });
    }
};


