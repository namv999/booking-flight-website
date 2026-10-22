<?php

namespace Database\Factories;

use App\Models\BookingFlight;
use App\Models\FlightSeat;
use App\Models\Passenger;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_flight_id' => BookingFlight::factory(),
            'passenger_id' => Passenger::factory(),
            'flight_seat_id' => FlightSeat::factory(),
            'companion_adult_passenger_id' => null,
            'is_self_selected' => false,
            'baggage_addon_id' => null,
            'price' => 1500000,
            'ticket_code' => strtoupper(Str::random(8)), // trùng pattern với Controller thật
        ];
    }

    // Vé infant: không ghế, giá = 10% giá vé cụ thể của adult đi kèm
    public function infantOf(Passenger $adult, float $adultTicketPrice): static
    {
        return $this->state(fn (array $attrs) => [
            'flight_seat_id' => null,
            'companion_adult_passenger_id' => $adult->id,
            'price' => round($adultTicketPrice * 0.10, 2),
        ]);
    }
}
