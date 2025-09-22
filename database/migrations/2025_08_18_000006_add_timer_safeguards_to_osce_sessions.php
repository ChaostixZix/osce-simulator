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
        // Add database constraints to prevent timing bugs
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER TABLE ... ADD CONSTRAINT for CHECKs; use triggers.
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS osce_sessions_started_at_check_insert
                BEFORE INSERT ON osce_sessions
                FOR EACH ROW
                WHEN NEW.status = 'in_progress' AND NEW.started_at IS NULL
                BEGIN
                    SELECT RAISE(ABORT, 'started_at required when in_progress');
                END;
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS osce_sessions_started_at_check_update
                BEFORE UPDATE ON osce_sessions
                FOR EACH ROW
                WHEN NEW.status = 'in_progress' AND NEW.started_at IS NULL
                BEGIN
                    SELECT RAISE(ABORT, 'started_at required when in_progress');
                END;
            SQL);
        } else {
            DB::statement("\n                ALTER TABLE osce_sessions\n                ADD CONSTRAINT check_started_at_not_null\n                CHECK (status != 'in_progress' OR started_at IS NOT NULL)\n            ");
        }

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
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS osce_sessions_started_at_check_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS osce_sessions_started_at_check_update');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE osce_sessions DROP CONSTRAINT IF EXISTS check_started_at_not_null');
        } elseif ($driver === 'mysql') {
            try {
                DB::statement('ALTER TABLE osce_sessions DROP CHECK check_started_at_not_null');
            } catch (\Throwable $e) {
                // Ignore if CHECK did not exist (older MySQL/MariaDB)
            }
        }

        Schema::table('osce_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_user_status_timer');
        });
    }
};
