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
            ['iata_code' => 'SIN', 'name' => 'Changi Airport', 'city' => 'Singapore', 'country' => 'Singapore', 'timezone' => 'Asia/Singapore'],
            ['iata_code' => 'ICN', 'name' => 'Incheon International Airport', 'city' => 'Seoul', 'country' => 'South Korea', 'timezone' => 'Asia/Seoul'],
            ['iata_code' => 'HKG', 'name' => 'Hong Kong International Airport', 'city' => 'Hong Kong', 'country' => 'Hong Kong', 'timezone' => 'Asia/Hong_Kong'],
            ['iata_code' => 'KUL', 'name' => 'Kuala Lumpur International Airport', 'city' => 'Kuala Lumpur', 'country' => 'Malaysia', 'timezone' => 'Asia/Kuala_Lumpur'],
            ['iata_code' => 'TPE', 'name' => 'Taiwan Taoyuan International Airport', 'city' => 'Taipei', 'country' => 'Taiwan', 'timezone' => 'Asia/Taipei'],
            ['iata_code' => 'DXB', 'name' => 'Dubai International Airport', 'city' => 'Dubai', 'country' => 'United Arab Emirates', 'timezone' => 'Asia/Dubai'],
            ['iata_code' => 'DOH', 'name' => 'Hamad International Airport', 'city' => 'Doha', 'country' => 'Qatar', 'timezone' => 'Asia/Qatar'],
            ['iata_code' => 'SYD', 'name' => 'Sydney Kingsford Smith Airport', 'city' => 'Sydney', 'country' => 'Australia', 'timezone' => 'Australia/Sydney'],
            ['iata_code' => 'CDG', 'name' => 'Charles de Gaulle Airport', 'city' => 'Paris', 'country' => 'France', 'timezone' => 'Europe/Paris'],
            ['iata_code' => 'IST', 'name' => 'Istanbul Airport', 'city' => 'Istanbul', 'country' => 'Turkey', 'timezone' => 'Europe/Istanbul'],
        ];

        foreach ($airports as $airport) {
            Airport::updateOrCreate(
                ['iata_code' => $airport['iata_code']],
                $airport
            );
        }
    }
}
