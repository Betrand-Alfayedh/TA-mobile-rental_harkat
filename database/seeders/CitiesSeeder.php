<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buka file CSV
        $csvPath = database_path('seeders/world_cities.csv');
        $csv = File::get($csvPath);

        $lines = explode("\n", $csv);

        // Ambil header
        $header = array_shift($lines);

        foreach ($lines as $line) {
            // skip baris kosong
            if (trim($line) === '') continue;

            $data = str_getcsv($line);

            // Ambil kolom city_ascii & country
            $name = $data[1];    // city_ascii
            $country = $data[4]; // country

            // Insert ke tabel cities
            DB::table('cities')->insert([
                'name' => $name,
                'country' => $country,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Cities successfully seeded!');
    }
}
