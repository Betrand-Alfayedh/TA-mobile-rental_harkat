<?php

namespace Database\Factories;

use App\Models\MasterMobil;
use App\Models\Mobil;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mobil>
 */
class MobilFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Mobil::class;

    public function definition(): array
    {
        return [
            'master_mobil_id' => MasterMobil::query()->inRandomOrder()->value('id')
                ?? MasterMobil::factory()->create()->id,
            'plat_nomor' => function () {
                $kodePlat = $this->faker->randomElement(['AB', 'B', 'F']);
                $angka = $this->faker->numerify('####'); // 4 digit angka
                $huruf = strtoupper($this->faker->bothify('???')); // 2 huruf random
                return "{$kodePlat} {$angka} {$huruf}";
            },
            'merk' => $this->faker->randomElement(['Toyota', 'Honda', 'Daihatsu']),
            'tahun' => $this->faker->numberBetween(2022, now()->year),
            'harga_sewa' => $this->faker->numberBetween(300000, 1200000),
            'harga_all_in' => $this->faker->numberBetween(500000, 1500000),
            'status' => $this->faker->randomElement([0, 1, 2, 3, 4]), // 0 = tidak tersedia, 1 = tersedia
        ];
    }
}
