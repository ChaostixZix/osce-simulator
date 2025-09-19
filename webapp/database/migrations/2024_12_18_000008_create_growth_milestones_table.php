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
        Schema::create('growth_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('milestone_type'); // sessions_completed, learning_streak, study_time, assessment_score
            $table->string('milestone_title');
            $table->text('milestone_description');
            $table->integer('threshold_value');
            $table->integer('current_value');
            $table->dateTime('achieved_at')->nullable();
            $table->string('badge_icon')->nullable();
            $table->string('badge_color')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'milestone_type']);
            $table->index(['user_id', 'achieved_at']);
            $table->index('milestone_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('growth_milestones');
    }
};