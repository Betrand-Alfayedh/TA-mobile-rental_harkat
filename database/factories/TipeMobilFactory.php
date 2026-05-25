<?php

namespace Database\Factories;

use App\Models\TipeMobil;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TipeMobil>
 */
class TipeMobilFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = TipeMobil::class;

    public function definition(): array
    {
        return [
            'nama_tipe' => $this->faker->randomElement([
                'City Car',
                'MPV',
                'SUV',
                'Mini Bus',
            ]),
        ];
    }
}
