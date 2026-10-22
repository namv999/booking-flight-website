<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'amount' => 1500000,
            'method' => 'the_tin_dung',
            'status' => 'success',
            'transaction_code' => strtoupper(Str::random(12)),
            'paid_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'failed',
            'paid_at' => null,
        ]);
    }
}
