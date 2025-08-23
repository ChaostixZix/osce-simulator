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
        Schema::create('mcq_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mcq_test_id')->constrained('mcq_tests')->cascadeOnDelete();
            $table->text('question');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['mcq_test_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mcq_questions');
    }
};
