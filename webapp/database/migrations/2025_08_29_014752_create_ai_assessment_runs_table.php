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
        Schema::create('ai_assessment_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('osce_session_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->json('final_result')->nullable(); // The aggregated assessment result
            $table->integer('total_score')->nullable();
            $table->integer('max_possible_score')->nullable();
            $table->text('error_message')->nullable();
            $table->json('telemetry')->nullable(); // Store telemetry data for debugging
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['osce_session_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_assessment_runs');
    }
};
