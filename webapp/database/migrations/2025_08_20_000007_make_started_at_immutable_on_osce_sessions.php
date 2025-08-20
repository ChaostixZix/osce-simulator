<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Ensure started_at does not auto-update and is stored as an immutable datetime
        if ($driver === 'mysql') {
            // Convert to DATETIME to avoid ON UPDATE CURRENT_TIMESTAMP behavior
            DB::statement('ALTER TABLE osce_sessions MODIFY started_at DATETIME NULL');
        } elseif ($driver === 'pgsql') {
            // Ensure no default and correct type (no timezone dependency)
            DB::statement('ALTER TABLE osce_sessions ALTER COLUMN started_at TYPE timestamp without time zone');
            DB::statement('ALTER TABLE osce_sessions ALTER COLUMN started_at DROP DEFAULT');
        }

        // Backfill legacy rows: set started_at to created_at for active sessions missing it
        DB::table('osce_sessions')
            ->where('status', 'in_progress')
            ->whereNull('started_at')
            ->update(['started_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Revert to TIMESTAMP (note: this does not reintroduce ON UPDATE behavior explicitly)
            DB::statement('ALTER TABLE osce_sessions MODIFY started_at TIMESTAMP NULL');
        } elseif ($driver === 'pgsql') {
            // Keep as timestamp without time zone to avoid implicit behavior
            DB::statement('ALTER TABLE osce_sessions ALTER COLUMN started_at TYPE timestamp without time zone');
        }
    }
};

