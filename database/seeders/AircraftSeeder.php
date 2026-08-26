<?php

namespace Database\Seeders;

use App\Models\Aircraft;
use App\Models\Airline;
use Illuminate\Database\Seeder;

class AircraftSeeder extends Seeder
{
    public function run(): void
    {
        $aircrafts = [
            [
                'airline_code' => 'VN',
                'model' => 'Airbus A321',
                'registration_number' => 'VN-A32101',
                'total_seats' => 184,
            ],
            [
                'airline_code' => 'VN',
                'model' => 'Boeing 787-9',
                'registration_number' => 'VN-B78701',
                'total_seats' => 274,
            ],
            [
                'airline_code' => 'VJ',
                'model' => 'Airbus A320',
                'registration_number' => 'VJ-A32001',
                'total_seats' => 180,
            ],
            [
                'airline_code' => 'VJ',
                'model' => 'Airbus A321',
                'registration_number' => 'VJ-A32101',
                'total_seats' => 230,
            ],
            [
                'airline_code' => 'QH',
                'model' => 'Airbus A321',
                'registration_number' => 'QH-A32101',
                'total_seats' => 196,
            ],
        ];

        foreach ($aircrafts as $data) {
            $airline = Airline::where('code', $data['airline_code'])->firstOrFail();

            Aircraft::updateOrCreate(
                [
                    'registration_number' => $data['registration_number'],
                ],
                [
                    'airline_id' => $airline->id,
                    'model' => $data['model'],
                    'total_seats' => $data['total_seats'],
                ]
            );
        }
    }
}