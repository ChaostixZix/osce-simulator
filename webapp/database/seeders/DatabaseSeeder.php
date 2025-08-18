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
			]
		);

		// Seed initial app data
		$this->call([
			ForumSeeder::class,
			MedicalTestSeeder::class,
			OsceCaseSeeder::class,
		]);
	}
}
