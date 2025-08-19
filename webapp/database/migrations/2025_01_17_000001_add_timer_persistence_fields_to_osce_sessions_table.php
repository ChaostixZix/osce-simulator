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
        Schema::table('osce_sessions', function (Blueprint $table) {
            // Timer persistence fields
            $table->timestamp('paused_at')->nullable()->after('time_extended');
            $table->timestamp('resumed_at')->nullable()->after('paused_at');
            $table->integer('total_paused_seconds')->default(0)->after('resumed_at');
            $table->integer('current_remaining_seconds')->nullable()->after('total_paused_seconds');
            
            // Add indexes for performance
            $table->index('paused_at');
            $table->index(['status', 'paused_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('osce_sessions', function (Blueprint $table) {
            $table->dropIndex(['paused_at']);
            $table->dropIndex(['status', 'paused_at']);
            
            $table->dropColumn([
                'paused_at',
                'resumed_at',
                'total_paused_seconds',
                'current_remaining_seconds'
            ]);
        });
    }
};