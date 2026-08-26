<?php

namespace Database\Seeders;

use App\Models\BaggageAddon;
use Illuminate\Database\Seeder;

class BaggageAddonSeeder extends Seeder
{
    public function run(): void
    {
        $addons = [
            [
                'name' => 'Gói 10kg',
                'weight_kg' => 10,
                'price' => 180000,
            ],
            [
                'name' => 'Gói 15kg',
                'weight_kg' => 15,
                'price' => 250000,
            ],
            [
                'name' => 'Gói 20kg',
                'weight_kg' => 20,
                'price' => 350000,
            ],
            [
                'name' => 'Gói 25kg',
                'weight_kg' => 25,
                'price' => 450000,
            ],
            [
                'name' => 'Gói 30kg',
                'weight_kg' => 30,
                'price' => 550000,
            ],
        ];

        foreach ($addons as $addon) {
            BaggageAddon::updateOrCreate(
                [
                    'name' => $addon['name'],
                    'weight_kg' => $addon['weight_kg'],
                ],
                $addon
            );
        }
    }
}