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
        Schema::create('refresher_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('osce_case_id')->nullable()->constrained()->onDelete('set null');
            $table->string('content_type'); // quick_quiz, case_review, skill_drill
            $table->json('content'); // structured content based on type
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->dateTime('generated_at');
            $table->dateTime('completed_at')->nullable();
            $table->float('performance_score')->nullable();
            $table->dateTime('next_reminder_date');
            $table->timestamps();

            $table->index(['user_id', 'next_reminder_date']);
            $table->index(['user_id', 'content_type']);
            $table->index('next_reminder_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresher_cases');
    }
};