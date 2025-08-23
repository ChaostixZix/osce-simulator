<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('osce_sessions', function (Blueprint $table) {
            $table->integer('clinical_reasoning_score')->default(0)->after('max_score');
            $table->decimal('total_test_cost', 10, 2)->default(0)->after('clinical_reasoning_score');
            $table->json('evaluation_feedback')->nullable()->after('total_test_cost');
        });
    }

    public function down(): void
    {
        Schema::table('osce_sessions', function (Blueprint $table) {
            $columns = ['clinical_reasoning_score', 'total_test_cost', 'evaluation_feedback'];
            $toDrop = [];
            foreach ($columns as $col) {
                if (Schema::hasColumn('osce_sessions', $col)) {
                    $toDrop[] = $col;
                }
            }
            if (! empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });
    }
};
