<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add database constraints to prevent timing bugs
        DB::statement('
            ALTER TABLE osce_sessions 
            ADD CONSTRAINT check_started_at_not_null 
            CHECK (status != \'in_progress\' OR started_at IS NOT NULL)
        ');
        
        // Add index for better performance on timer queries
        Schema::table('osce_sessions', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_user_status_timer');
        });
        
        // Update any existing sessions that might have null started_at
        DB::table('osce_sessions')
            ->where('status', 'in_progress')
            ->whereNull('started_at')
            ->update(['started_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE osce_sessions DROP CONSTRAINT check_started_at_not_null');
        
        Schema::table('osce_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_user_status_timer');
        });
    }
};