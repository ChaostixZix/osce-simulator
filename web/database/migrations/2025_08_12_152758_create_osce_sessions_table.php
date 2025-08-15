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
        Schema::create('osce_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('case_id');
            $table->string('case_title');
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->json('performance_data')->nullable();
            $table->integer('total_messages')->default(0);
            $table->integer('duration_seconds')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();
            
            $table->index(['case_id', 'status']);
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('osce_sessions');
    }
};

