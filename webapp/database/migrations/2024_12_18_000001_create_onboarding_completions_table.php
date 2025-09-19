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
        Schema::create('onboarding_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('osce_case_id')->constrained()->onDelete('cascade');
            $table->timestamp('completed_at');
            $table->integer('steps_completed')->default(4);
            $table->integer('time_spent_seconds')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'osce_case_id']);
            $table->index(['user_id', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_completions');
    }
};