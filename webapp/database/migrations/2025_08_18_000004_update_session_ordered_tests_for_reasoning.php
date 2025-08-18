<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_ordered_tests', function (Blueprint $table) {
            $table->unsignedBigInteger('medical_test_id')->after('osce_session_id')->nullable();
            $table->text('clinical_reasoning')->after('test_name')->nullable();
            $table->enum('priority', ['immediate', 'urgent', 'routine'])->after('clinical_reasoning')->nullable();
            $table->decimal('cost', 8, 2)->after('priority')->default(0);
            $table->timestamp('results_available_at')->nullable()->after('ordered_at');
            $table->timestamp('completed_at')->nullable()->after('results_available_at');
        });
    }

    public function down(): void
    {
        Schema::table('session_ordered_tests', function (Blueprint $table) {
            $table->dropColumn(['medical_test_id', 'clinical_reasoning', 'priority', 'cost', 'results_available_at', 'completed_at']);
        });
    }
};


