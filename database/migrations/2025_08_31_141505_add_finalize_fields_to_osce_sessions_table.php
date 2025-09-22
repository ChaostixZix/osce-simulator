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
            $table->text('diagnosis')->nullable();
            $table->text('differential_diagnosis')->nullable();
            $table->text('plan')->nullable();
            $table->timestamp('finalized_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('osce_sessions', function (Blueprint $table) {
            $table->dropColumn(['diagnosis', 'differential_diagnosis', 'plan', 'finalized_at']);
        });
    }
};
