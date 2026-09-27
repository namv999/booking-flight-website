<?php

namespace Database\Factories;

use App\Models\Airline;
use Illuminate\Database\Eloquent\Factories\Factory;

class AircraftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'airline_id' => Airline::factory(),
            'model' => 'Airbus A321',
            'registration_number' => strtoupper(fake()->bothify('VN-???')),
            'total_seats' => 180,
        ];
    }
}
