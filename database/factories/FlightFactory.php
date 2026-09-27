<?php

namespace Database\Factories;

use App\Models\Aircraft;
use App\Models\Airport;
use Illuminate\Database\Eloquent\Factories\Factory;

class FlightFactory extends Factory
{
    public function definition(): array
    {
        $departure = now()->addDays(fake()->numberBetween(1, 30))->setTime(fake()->numberBetween(5, 20), 0);

        return [
            'aircraft_id' => Aircraft::factory(),
            'departure_airport_id' => Airport::factory(),
            'arrival_airport_id' => Airport::factory(), // factory riêng -> luôn khác departure
            'departure_time' => $departure,
            'arrival_time' => $departure->copy()->addHours(2),
            'status' => 'scheduled',
        ];
    }
}
