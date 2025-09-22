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
        Schema::create('case_primers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('osce_case_id')->constrained()->onDelete('cascade');
            $table->json('primer_data');
            $table->string('user_level', 50)->default('intermediate');
            $table->json('focus_areas')->nullable();
            $table->string('options_hash', 32)->index();
            $table->timestamp('generated_at');
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->unique(['osce_case_id', 'options_hash']);
            $table->index(['osce_case_id', 'generated_at']);
            $table->index(['user_level', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_primers');
    }
};