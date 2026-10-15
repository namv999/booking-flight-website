<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Seeder;

class AirportSeeder extends Seeder
{
    public function run(): void
    {
        $airports = [
            [
                'iata_code' => 'SGN',
                'name' => 'Tan Son Nhat International Airport',
                'city' => 'Ho Chi Minh City',
                'country' => 'Vietnam',
                'timezone' => 'Asia/Ho_Chi_Minh',
            ],
            [
                'iata_code' => 'HAN',
                'name' => 'Noi Bai International Airport',
                'city' => 'Hanoi',
                'country' => 'Vietnam',
                'timezone' => 'Asia/Ho_Chi_Minh',
            ],
            [
                'iata_code' => 'DAD',
                'name' => 'Da Nang International Airport',
                'city' => 'Da Nang',
                'country' => 'Vietnam',
                'timezone' => 'Asia/Ho_Chi_Minh',
            ],
            [
                'iata_code' => 'CXR',
                'name' => 'Cam Ranh International Airport',
                'city' => 'Nha Trang',
                'country' => 'Vietnam',
                'timezone' => 'Asia/Ho_Chi_Minh',
            ],
            [
                'iata_code' => 'PQC',
                'name' => 'Phu Quoc International Airport',
                'city' => 'Phu Quoc',
                'country' => 'Vietnam',
                'timezone' => 'Asia/Ho_Chi_Minh',
            ],
            [
                'iata_code' => 'BKK',
                'name' => 'Suvarnabhumi Airport',
                'city' => 'Bangkok',
                'country' => 'Thailand',
                'timezone' => 'Asia/Bangkok',
            ],
            [
                'iata_code' => 'NRT',
                'name' => 'Narita International Airport',
                'city' => 'Tokyo',
                'country' => 'Japan',
                'timezone' => 'Asia/Tokyo',
            ],
        ];

        foreach ($airports as $airport) {
            Airport::updateOrCreate(
                ['iata_code' => $airport['iata_code']],
                $airport
            );
        }
    }
}
