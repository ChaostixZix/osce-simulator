<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default user if not exists
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'workos_id' => 'seed-workos-id-0001',
                'avatar' => 'https://ui-avatars.com/api/?name=Test+User&background=0d1117&color=fff',
            ]
        );

        // Seed requested user email
        User::firstOrCreate(
            ['email' => 'bintangputra5556@gmail.com'],
            [
                'name' => 'Bintang Putra',
                'workos_id' => 'seed-workos-id-0002',
                'avatar' => 'https://ui-avatars.com/api/?name=Bintang+Putra&background=0d1117&color=fff',
            ]
        );

        // Seed initial app data
        $this->call([
            MedicalTestSeeder::class,
            OsceCaseSeeder::class,
            ComprehensiveMedicalTestSeeder::class,
        ]);
    }
}
