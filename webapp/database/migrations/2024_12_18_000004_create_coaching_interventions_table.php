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
        Schema::create('coaching_interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('osce_session_id')->constrained()->onDelete('cascade');
            $table->string('intervention_type', 50); // decision_support, resource_management, etc.
            $table->string('trigger_reason', 100); // long_pause, excessive_testing, etc.
            $table->text('content');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->timestamp('displayed_at')->nullable();
            $table->text('user_response')->nullable();
            $table->integer('effectiveness_rating')->nullable(); // 1-5 rating
            $table->timestamps();

            $table->index(['osce_session_id', 'created_at']);
            $table->index(['intervention_type', 'created_at']);
            $table->index(['priority', 'displayed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coaching_interventions');
    }
};