<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure table exists; choose a safe anchor column for ordering
        if (! Schema::hasTable('osce_sessions')) {
            return;
        }

        $anchor = Schema::hasColumn('osce_sessions', 'time_extended') ? 'time_extended' : 'max_score';

        Schema::table('osce_sessions', function (Blueprint $table) use ($anchor) {
            // Add columns only if they do not already exist to make the migration idempotent
            if (! Schema::hasColumn('osce_sessions', 'paused_at')) {
                $table->timestamp('paused_at')->nullable()->after($anchor);
            }
            if (! Schema::hasColumn('osce_sessions', 'resumed_at')) {
                $table->timestamp('resumed_at')->nullable()->after('paused_at');
            }
            if (! Schema::hasColumn('osce_sessions', 'total_paused_seconds')) {
                $table->integer('total_paused_seconds')->default(0)->after('resumed_at');
            }
            if (! Schema::hasColumn('osce_sessions', 'current_remaining_seconds')) {
                $table->integer('current_remaining_seconds')->nullable()->after('total_paused_seconds');
            }
        });

        // Use raw statements for index creation to make it idempotent, especially for SQLite.
        // CREATE INDEX IF NOT EXISTS is supported in SQLite >= 3.3.0
        if (DB::connection()->getDriverName() == 'sqlite') {
            DB::statement('CREATE INDEX IF NOT EXISTS osce_sessions_paused_at_index ON osce_sessions (paused_at)');
            DB::statement('CREATE INDEX IF NOT EXISTS osce_sessions_status_paused_at_index ON osce_sessions (status, paused_at)');
        } else {
            // For other databases, we can use a different approach
            Schema::table('osce_sessions', function (Blueprint $table) {
                $table->index('paused_at');
                $table->index(['status', 'paused_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('osce_sessions')) {
            return;
        }

        // Drop composite/single indexes first if the columns are present
        if (Schema::hasColumn('osce_sessions', 'paused_at')) {
            Schema::table('osce_sessions', function (Blueprint $table) {
                // Use column-array form so Laravel builds index names correctly
                $table->dropIndex(['status', 'paused_at']);
                $table->dropIndex(['paused_at']);
            });
        }

        // Now drop the added columns that exist
        $columnsToDrop = [];
        foreach ([
            'paused_at',
            'resumed_at',
            'total_paused_seconds',
            'current_remaining_seconds',
        ] as $col) {
            if (Schema::hasColumn('osce_sessions', $col)) {
                $columnsToDrop[] = $col;
            }
        }

        if (! empty($columnsToDrop)) {
            Schema::table('osce_sessions', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
