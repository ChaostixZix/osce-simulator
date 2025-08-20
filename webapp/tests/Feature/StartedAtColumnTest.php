<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class StartedAtColumnTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!file_exists(database_path('database.sqlite'))) {
            touch(database_path('database.sqlite'));
        }
        Artisan::call('migrate', ['--path' => database_path('migrations/2025_08_16_023052_create_osce_sessions_table.php'), '--realpath' => true, '--force' => true]);
        Artisan::call('migrate', ['--path' => database_path('migrations/2025_08_18_000001_add_timer_fields_and_indexes_to_osce_sessions_table.php'), '--realpath' => true, '--force' => true]);
        Artisan::call('migrate', ['--path' => database_path('migrations/2025_08_19_000007_make_started_at_immutable.php'), '--realpath' => true, '--force' => true]);
    }

    /** @test */
    public function started_at_column_has_no_on_update_behavior()
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $this->markTestSkipped('SQLite driver does not support ON UPDATE');
        }

        if ($driver === 'mysql') {
            $result = DB::select("SHOW CREATE TABLE osce_sessions");
            $create = $result[0]->{'Create Table'} ?? '';
            $this->assertStringNotContainsString('ON UPDATE', strtoupper($create));
        } elseif ($driver === 'pgsql') {
            $result = DB::select("SELECT pg_get_expr(d.adbin, d.adrelid) AS default_expr FROM pg_attrdef d JOIN pg_class t ON d.adrelid = t.oid JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = d.adnum WHERE t.relname = 'osce_sessions' AND a.attname = 'started_at'");
            $this->assertEmpty($result, 'started_at should not have a default');
        }
    }
}
