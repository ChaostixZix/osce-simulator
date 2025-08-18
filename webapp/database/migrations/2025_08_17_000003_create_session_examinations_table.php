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
        Schema::create('session_examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('osce_session_id')->constrained('osce_sessions')->onDelete('cascade');
            $table->string('examination_category'); // e.g., 'cardiovascular', 'respiratory'
            $table->string('examination_type'); // e.g., 'auscultation', 'palpation'
            $table->json('findings'); // The examination findings from the template
            $table->timestamp('performed_at');
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['osce_session_id', 'performed_at']);
            $table->index(['examination_category', 'examination_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_examinations');
    }
};