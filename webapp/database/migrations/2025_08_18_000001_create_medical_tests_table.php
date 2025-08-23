<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category');
            $table->enum('type', ['lab', 'imaging', 'procedure', 'physical_exam']);
            $table->text('description')->nullable();
            $table->json('indications')->nullable();
            $table->json('contraindications')->nullable();
            $table->decimal('cost', 8, 2)->default(0);
            $table->integer('turnaround_minutes')->default(0);
            $table->json('available_settings')->nullable();
            $table->boolean('requires_consent')->default(false);
            $table->integer('risk_level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_tests');
    }
};
