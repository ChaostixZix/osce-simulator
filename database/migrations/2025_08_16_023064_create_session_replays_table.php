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
        Schema::create('session_replays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('osce_session_id')->constrained()->onDelete('cascade');
            $table->json('replay_data');
            $table->string('generation_version', 20)->default('1.0');
            $table->text('user_feedback')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();

            $table->unique('osce_session_id');
            $table->index(['osce_session_id', 'created_at']);
            $table->index('viewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_replays');
    }
};