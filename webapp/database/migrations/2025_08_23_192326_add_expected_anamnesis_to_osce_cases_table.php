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
            $table->json('expected_anamnesis_questions')->nullable(); // Array of expected questions
            $table->json('red_flags')->nullable(); // Array of red flag indicators  
            $table->json('common_differentials')->nullable(); // Array of common differential diagnoses
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('osce_cases', function (Blueprint $table) {
            $table->dropColumn(['expected_anamnesis_questions', 'red_flags', 'common_differentials']);
        });
    }
};
