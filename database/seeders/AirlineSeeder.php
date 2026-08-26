<?php

namespace Database\Seeders;

use App\Models\Airline;
use Illuminate\Database\Seeder;

class AirlineSeeder extends Seeder
{
    public function run(): void
    {
        $airlines = [
            [
                'name' => 'Vietnam Airlines',
                'code' => 'VN',
                'logo_url' => null,
                'country' => 'Vietnam',
            ],
            [
                'name' => 'Vietjet Air',
                'code' => 'VJ',
                'logo_url' => null,
                'country' => 'Vietnam',
            ],
            [
                'name' => 'Bamboo Airways',
                'code' => 'QH',
                'logo_url' => null,
                'country' => 'Vietnam',
            ],
        ];

        foreach ($airlines as $airline) {
            Airline::updateOrCreate(
                ['code' => $airline['code']],
                $airline
            );
        }
    }
}