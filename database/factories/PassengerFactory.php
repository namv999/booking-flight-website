<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

class PassengerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'full_name' => fake()->name(),
            'passenger_type' => 'adult',
            'document_type' => null,
            'document_number' => null,
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'nationality' => null,
            'document_issued_country' => null,
            'document_expiry_date' => null,
        ];
    }

    public function child(): static
    {
        return $this->state(fn (array $attrs) => [
            'passenger_type' => 'child',
            'date_of_birth' => fake()->dateTimeBetween('-11 years', '-2 years'),
        ]);
    }

    public function infant(): static
    {
        return $this->state(fn (array $attrs) => [
            'passenger_type' => 'infant',
            'date_of_birth' => fake()->dateTimeBetween('-23 months', 'now'),
        ]);
    }
}
