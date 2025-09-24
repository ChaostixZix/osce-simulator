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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'supabase_id')) {
                $table->uuid('supabase_id')->nullable()->unique()->after('id');
            }

            if (!Schema::hasColumn('users', 'provider')) {
                $table->string('provider')->nullable()->after('email');
            }

            if (!Schema::hasColumn('users', 'provider_id')) {
                $table->string('provider_id')->nullable()->after('provider');
            }

            if (!Schema::hasColumn('users', 'is_migrated')) {
                $table->boolean('is_migrated')->default(false)->after('is_banned');
            }

            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = collect([
                'supabase_id',
                'provider',
                'provider_id',
                'is_migrated',
                'last_login_at',
            ])->filter(fn ($column) => Schema::hasColumn('users', $column))->all();

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
