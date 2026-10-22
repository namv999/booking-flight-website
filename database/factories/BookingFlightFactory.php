<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Flight;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFlightFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'flight_id' => Flight::factory(),
        ];
    }
}
