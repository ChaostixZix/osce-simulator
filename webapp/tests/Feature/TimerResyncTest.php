<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\OsceCase;
use App\Models\OsceSession;
use Carbon\Carbon;

class TimerResyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!file_exists(database_path('database.sqlite'))) {
            touch(database_path('database.sqlite'));
        }
        Artisan::call('migrate', ['--path' => database_path('migrations/0001_01_01_000000_create_users_table.php'), '--realpath' => true]);
        Artisan::call('migrate', ['--path' => database_path('migrations/2025_08_16_023044_create_osce_cases_table.php'), '--realpath' => true]);
        Artisan::call('migrate', ['--path' => database_path('migrations/2025_08_16_131415_add_ai_patient_data_to_osce_cases_table.php'), '--realpath' => true]);
        Artisan::call('migrate', ['--path' => database_path('migrations/2025_08_17_000001_add_examination_fields_to_osce_cases_table.php'), '--realpath' => true]);
        Artisan::call('migrate', ['--path' => database_path('migrations/2025_08_16_023052_create_osce_sessions_table.php'), '--realpath' => true]);
        Artisan::call('migrate', ['--path' => database_path('migrations/2025_08_18_000001_add_timer_fields_and_indexes_to_osce_sessions_table.php'), '--realpath' => true]);
        Artisan::call('migrate', ['--path' => database_path('migrations/2025_08_19_000007_make_started_at_immutable.php'), '--realpath' => true]);
    }

    /** @test */
    public function timer_endpoint_decreases_over_time()
    {
        Carbon::setTestNow(now());
        $user = User::factory()->create();
        $case = OsceCase::create([
            'title' => 'Test Case',
            'description' => 'Desc',
            'difficulty' => 'medium',
            'duration_minutes' => 15,
            'scenario' => 'Scenario',
            'objectives' => 'Objectives',
            'stations' => [],
            'checklist' => [],
            'is_active' => true,
        ]);
        $session = OsceSession::create([
            'user_id' => $user->id,
            'osce_case_id' => $case->id,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(2),
        ]);

        $this->actingAs($user);
        $first = $this->getJson("/api/osce/sessions/{$session->id}/timer")->json();
        $this->assertEquals(780, $first['remaining_seconds']);

        Carbon::setTestNow(now()->addSeconds(3));
        $second = $this->getJson("/api/osce/sessions/{$session->id}/timer")->json();

        $this->assertEquals($first['remaining_seconds'] - 3, $second['remaining_seconds']);
        $this->assertEquals($first['elapsed_seconds'] + 3, $second['elapsed_seconds']);
        Carbon::setTestNow();
    }
}
