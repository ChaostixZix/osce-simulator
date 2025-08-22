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
        Schema::create('session_ordered_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('osce_session_id')->constrained('osce_sessions')->onDelete('cascade');
            $table->enum('test_type', ['lab', 'procedure']); // Type of test ordered
            $table->string('test_name'); // Name of the test/procedure (e.g., "Complete Blood Count")
            $table->json('results'); // The actual results data from the template
            $table->timestamp('ordered_at');
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['osce_session_id', 'ordered_at']);
            $table->index(['test_type', 'test_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_ordered_tests');
    }
};