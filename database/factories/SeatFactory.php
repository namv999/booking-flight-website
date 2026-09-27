<?php

namespace Database\Factories;

use App\Models\Aircraft;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'aircraft_id' => Aircraft::factory(),
            'seat_number' => fake()->numberBetween(1, 30) . fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
            'seat_class' => 'economy',
            'position' => fake()->randomElement(['window', 'aisle', 'middle']),
        ];
    }
}
