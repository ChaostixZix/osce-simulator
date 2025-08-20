<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable();
            $table->enum('type', ['error', 'health_check', 'performance', 'api_call', 'system_status']);
            $table->enum('level', ['debug', 'info', 'warning', 'error', 'critical'])->default('info');
            $table->string('context'); // e.g., 'Chat Mode', 'OSCE Mode', 'API Call'
            $table->text('message');
            $table->json('data')->nullable(); // additional structured data
            $table->string('user_agent')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->foreign('session_id')->references('session_id')->on('sessions')->onDelete('set null');
            $table->index(['type', 'level', 'created_at']);
            $table->index(['session_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};