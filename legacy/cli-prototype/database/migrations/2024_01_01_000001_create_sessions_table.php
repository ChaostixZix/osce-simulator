<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('chat_messages')->default(0);
            $table->integer('osce_sessions_completed')->default(0);
            $table->integer('total_osce_time')->default(0); // in seconds
            $table->integer('error_count')->default(0);
            $table->json('metadata')->nullable(); // additional session data
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['session_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};