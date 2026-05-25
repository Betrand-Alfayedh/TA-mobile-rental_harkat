<?php

namespace Database\Factories;

use App\Models\MasterMobil;
use App\Models\TipeMobil;
use Illuminate\Database\Eloquent\Factories\Factory;

class MasterMobilFactory extends Factory
{
    protected $model = MasterMobil::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->unique()->randomElement([
                'New Avanza',
                'New Brio',
                'New Fortuner GR',
                'New Innova Reborn',
                'Raize',
                'New Agya',
                'New BR-V',
            ]),
            // random tipe mobil yang udah ada
            'tipe_id' => TipeMobil::inRandomOrder()->first()->id ?? TipeMobil::factory(),
        ];
    }
}
