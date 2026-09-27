<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FareClassFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Economy',
            'base_price' => 1500000,
            'seat_selection_fee' => 80000,
            'checked_baggage_kg' => 10,
            'carry_on_baggage_kg' => 7,
            'description' => null,
        ];
    }
}
