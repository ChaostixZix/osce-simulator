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
        // Ensure table exists; choose a safe anchor column for ordering
        if (!Schema::hasTable('osce_sessions')) {
            return;
        }

        $anchor = Schema::hasColumn('osce_sessions', 'time_extended') ? 'time_extended' : 'max_score';

        Schema::table('osce_sessions', function (Blueprint $table) use ($anchor) {
            // Timer persistence fields (placed after anchor when supported)
            $table->timestamp('paused_at')->nullable()->after($anchor);
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
        if (!Schema::hasTable('osce_sessions')) {
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

        if (!empty($columnsToDrop)) {
            Schema::table('osce_sessions', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};

