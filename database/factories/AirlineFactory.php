<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AirlineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Airlines',
            'code' => strtoupper(fake()->unique()->bothify('??')), // 2 ký tự kiểu VN, VJ, QH
            'logo_url' => null,
            'country' => 'Vietnam',
        ];
    }
}
