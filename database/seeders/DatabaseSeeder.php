<?php

namespace Database\Seeders;

use App\Models\MasterMobil;
use App\Models\Mobil;
use App\Models\TipeMobil;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password'),
                'role' => 1,
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 2,
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                $u
            );
        }
        TipeMobil::factory()->count(4)->create();

        // Generate master mobil
        MasterMobil::factory(7)->create();

        // Generate unit mobil
        Mobil::factory(10)->create();

        // Seeder lainnya
        $this->call([
            CitiesSeeder::class,
        ]);
    }
}
