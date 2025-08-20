<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE osce_sessions MODIFY started_at DATETIME NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE osce_sessions ALTER COLUMN started_at TYPE timestamp without time zone");
            DB::statement("ALTER TABLE osce_sessions ALTER COLUMN started_at DROP DEFAULT");
        }

        DB::table('osce_sessions')
            ->where('status', 'in_progress')
            ->whereNull('started_at')
            ->update(['started_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE osce_sessions MODIFY started_at TIMESTAMP NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE osce_sessions ALTER COLUMN started_at TYPE timestamp without time zone");
        }
    }
};
