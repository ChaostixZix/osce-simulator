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
        Schema::table('osce_sessions', function (Blueprint $table) {
            $table->json('assessor_payload')->nullable();
            $table->json('assessor_output')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->string('assessor_model')->nullable();
            $table->string('rubric_version')->nullable();
            
            // Ensure score and max_score exist (add if missing)
            if (!Schema::hasColumn('osce_sessions', 'score')) {
                $table->integer('score')->nullable();
            }
            if (!Schema::hasColumn('osce_sessions', 'max_score')) {
                $table->integer('max_score')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('osce_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'assessor_payload',
                'assessor_output',
                'assessed_at',
                'assessor_model',
                'rubric_version'
            ]);
        });
    }
};
