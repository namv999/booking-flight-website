<?php

namespace Database\Factories;

use App\Models\FareClass;
use App\Models\Flight;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FlightSeatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'flight_id' => Flight::factory(),
            'seat_id' => Seat::factory(),
            'fare_class_id' => FareClass::factory(),
            'price' => 1500000,
            'status' => 'available',
            'held_by' => null,
            'held_until' => null,
        ];
    }

    // Ghế đang bị người khác giữ hợp lệ - dùng để test KHÔNG được cướp ghế
    public function heldValid(): static
    {
        return $this->state(fn(array $attrs) => [
            'status' => 'held',
            'held_by' => User::factory(),
            'held_until' => now()->addMinutes(10),
        ]);
    }

    // Ghế held nhưng đã hết hạn - dùng để test reclaim trong BookingController::store()
    public function heldExpired(): static
    {
        return $this->state(fn(array $attrs) => [
            'status' => 'held',
            'held_by' => User::factory(),
            'held_until' => now()->subMinutes(5),
        ]);
    }

    public function booked(): static
    {
        return $this->state(fn(array $attrs) => [
            'status' => 'booked',
            'held_by' => null,
            'held_until' => null,
        ]);
    }
}
