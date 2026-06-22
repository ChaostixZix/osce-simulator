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
        Schema::create('spaced_repetition_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('osce_case_id')->nullable()->constrained()->onDelete('set null');
            $table->string('clinical_area');
            $table->json('card_content'); // question, answer, explanation, tags
            $table->integer('repetition_level')->default(0);
            $table->float('easiness_factor')->default(2.5);
            $table->dateTime('next_review_date');
            $table->dateTime('last_reviewed_at')->nullable();
            $table->integer('review_count')->default(0);
            $table->foreignId('created_from_session')->nullable()->constrained('osce_sessions')->onDelete('set null');
            $table->timestamps();

            $table->index(['user_id', 'next_review_date']);
            $table->index(['user_id', 'clinical_area']);
            $table->index('next_review_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spaced_repetition_cards');
    }
};