<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AirportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'iata_code' => strtoupper(fake()->unique()->bothify('???')),
            'name' => fake()->city().' International Airport',
            'city' => fake()->city(),
            'country' => 'Vietnam',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ];
    }

    // Dùng khi cần test chuyến bay quốc tế (departure.country != arrival.country)
    public function foreign(): static
    {
        return $this->state(fn (array $attrs) => [
            'country' => 'Thailand',
            'timezone' => 'Asia/Bangkok',
        ]);
    }
}
