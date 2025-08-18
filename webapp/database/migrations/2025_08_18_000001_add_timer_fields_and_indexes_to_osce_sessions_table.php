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
            if (!Schema::hasColumn('osce_sessions', 'time_extended')) {
                $table->integer('time_extended')->nullable()->after('max_score');
            }
            $table->index('started_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('osce_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('osce_sessions', 'time_extended')) {
                $table->dropColumn('time_extended');
            }
            $table->dropIndex(['started_at']);
            $table->dropIndex(['status']);
        });
    }
};

