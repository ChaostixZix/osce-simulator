<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\SoapComment;
use App\Models\SoapNote;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class PatientSoapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('id_ID');
        $user = User::first();

        if (! $user) {
            $user = User::factory()->create();
        }

        for ($i = 0; $i < 100; $i++) {
            $patient = Patient::create([
                'name' => $faker->name,
                'bangsal' => $faker->randomElement(['Mawar', 'Melati', 'Anggrek', 'Dahlia']),
                'nomor_kamar' => $faker->numberBetween(101, 120),
                'status' => $faker->randomElement(['active', 'discharged']),
            ]);

            $soapNote = SoapNote::create([
                'patient_id' => $patient->id,
                'author_id' => $user->id,
                'subjective' => $faker->paragraph,
                'objective' => $faker->paragraph,
                'assessment' => $faker->paragraph,
                'plan' => $faker->paragraph,
                'state' => 'finalized',
                'finalized_at' => now(),
            ]);

            for ($j = 0; $j < $faker->numberBetween(1, 5); $j++) {
                SoapComment::create([
                    'soap_note_id' => $soapNote->id,
                    'author_id' => $user->id,
                    'body' => $faker->sentence,
                ]);
            }
        }
    }
}
