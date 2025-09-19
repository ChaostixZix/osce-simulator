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
        Schema::create('patient_visualizations', function (Blueprint $table) {
            $table->id();
            $table->text('original_prompt');
            $table->text('enhanced_prompt');
            $table->string('prompt_hash', 32)->index();
            $table->string('image_path');
            $table->string('image_url')->nullable();
            $table->string('mime_type', 50);
            $table->json('generation_options')->nullable();
            $table->timestamp('generated_at');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('osce_case_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index(['prompt_hash', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['osce_case_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_visualizations');
    }
};