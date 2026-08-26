<?php

namespace Database\Seeders;

use App\Models\FareClass;
use Illuminate\Database\Seeder;

class FareClassSeeder extends Seeder
{
    public function run(): void
    {
        $fareClasses = [
            [
                'name' => 'Economy',
                'base_price' => 1000000,
                'seat_selection_fee' => 40000,
                'checked_baggage_kg' => 10,
                'carry_on_baggage_kg' => 7,
                'description' => 'Hạng phổ thông',
            ],
            [
                'name' => 'Business',
                'base_price' => 2500000,
                'seat_selection_fee' => 80000,
                'checked_baggage_kg' => 18,
                'carry_on_baggage_kg' => 7,
                'description' => 'Hạng thương gia',
            ],
            [
                'name' => 'First',
                'base_price' => 5000000,
                'seat_selection_fee' => 160000,
                'checked_baggage_kg' => 18,
                'carry_on_baggage_kg' => 7,
                'description' => 'Hạng nhất',
            ],
        ];

        foreach ($fareClasses as $fareClass) {
            FareClass::updateOrCreate(
                ['name' => $fareClass['name']],
                $fareClass
            );
        }
    }
}