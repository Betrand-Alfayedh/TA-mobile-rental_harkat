<?php

namespace Database\Seeders;

use App\Models\Supir;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupirSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil user supir dari tabel users
        $supirUser = User::where('role', 3)->get();

        foreach ($supirUser as $user) {
            Supir::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'status' => 1, // default aktif
                ]
            );
        }
    }
}
