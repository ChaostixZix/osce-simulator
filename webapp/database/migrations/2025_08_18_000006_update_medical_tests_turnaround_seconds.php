<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_tests', function (Blueprint $table) {
            $table->integer('turnaround_seconds')->default(0)->after('cost');
        });

        // Convert existing data: 60 minutes -> 60 seconds (OSCE accelerated scale)
        DB::table('medical_tests')->update([
            'turnaround_seconds' => DB::raw('turnaround_minutes')
        ]);

        Schema::table('medical_tests', function (Blueprint $table) {
            $table->dropColumn('turnaround_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('medical_tests', function (Blueprint $table) {
            $table->integer('turnaround_minutes')->default(0)->after('cost');
        });

        // Convert back: seconds -> minutes (1:1 per original acceleration scheme)
        DB::table('medical_tests')->update([
            'turnaround_minutes' => DB::raw('turnaround_seconds')
        ]);

        Schema::table('medical_tests', function (Blueprint $table) {
            $table->dropColumn('turnaround_seconds');
        });
    }
};

 
