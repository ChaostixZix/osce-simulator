<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ai_assessment_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_assessment_runs', 'queue_position')) {
                $table->integer('queue_position')->nullable()->after('status');
            }
            if (!Schema::hasColumn('ai_assessment_runs', 'estimated_wait_time_minutes')) {
                $table->integer('estimated_wait_time_minutes')->nullable()->after('queue_position');
            }
            if (!Schema::hasColumn('ai_assessment_runs', 'queued_at')) {
                $table->timestamp('queued_at')->nullable()->after('estimated_wait_time_minutes');
            }
            if (!Schema::hasColumn('ai_assessment_runs', 'current_area')) {
                $table->string('current_area')->nullable()->after('status');
            }
            if (!Schema::hasColumn('ai_assessment_runs', 'status_message')) {
                $table->text('status_message')->nullable()->after('current_area');
            }
            
            // Skip index creation - already exists
        });
    }

    public function down()
    {
        Schema::table('ai_assessment_runs', function (Blueprint $table) {
            $table->dropColumn([
                'queue_position',
                'estimated_wait_time_minutes', 
                'queued_at',
                'current_area',
                'status_message'
            ]);
        });
    }
};