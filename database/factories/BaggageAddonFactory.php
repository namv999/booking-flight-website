<?php

namespace Database\Factories;

use App\Models\BaggageAddon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BaggageAddon>
 */
class BaggageAddonFactory extends Factory
{
    public function definition(): array
    {
        $weight = fake()->randomElement([10, 15, 20, 25, 30]);

        return [
            'name' => 'Gói '.$weight.'kg',
            'weight_kg' => $weight,
            'price' => $weight * 10000,
        ];
    }
}