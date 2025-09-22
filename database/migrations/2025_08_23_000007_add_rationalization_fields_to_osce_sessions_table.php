<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('osce_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('osce_sessions', 'rationalization_completed_at')) {
                $table->timestamp('rationalization_completed_at')->nullable()->after('completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('osce_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('osce_sessions', 'rationalization_completed_at')) {
                $table->dropColumn('rationalization_completed_at');
            }
        });
    }
};

