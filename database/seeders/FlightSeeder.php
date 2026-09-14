<?php

namespace Database\Seeders;

use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Flight;
use Illuminate\Database\Seeder;

class FlightSeeder extends Seeder
{
    public function run(): void
    {
        $flights = [
            [
                'aircraft' => 'VN-A32101',
                'departure' => 'SGN',
                'arrival' => 'HAN',
                'departure_time' => '2026-09-10 06:30:00',
                'arrival_time' => '2026-09-10 08:40:00',
                'status' => 'scheduled',
            ],
            [
                'aircraft' => 'VJ-A32001',
                'departure' => 'SGN',
                'arrival' => 'DAD',
                'departure_time' => '2026-09-10 09:00:00',
                'arrival_time' => '2026-09-10 10:20:00',
                'status' => 'scheduled',
            ],
            [
                'aircraft' => 'VN-B78701',
                'departure' => 'HAN',
                'arrival' => 'SGN',
                'departure_time' => '2026-09-10 13:30:00',
                'arrival_time' => '2026-09-10 15:45:00',
                'status' => 'scheduled',
            ],
            [
                'aircraft' => 'VJ-A32101',
                'departure' => 'DAD',
                'arrival' => 'SGN',
                'departure_time' => '2026-09-10 16:00:00',
                'arrival_time' => '2026-09-10 17:20:00',
                'status' => 'scheduled',
            ],
            [
                'aircraft' => 'QH-A32101',
                'departure' => 'SGN',
                'arrival' => 'CXR',
                'departure_time' => '2026-09-11 07:15:00',
                'arrival_time' => '2026-09-11 08:20:00',
                'status' => 'delayed',
            ],
            [
                'aircraft' => 'VN-A32101',
                'departure' => 'SGN',
                'arrival' => 'PQC',
                'departure_time' => '2026-09-11 10:30:00',
                'arrival_time' => '2026-09-11 11:30:00',
                'status' => 'scheduled',
            ],
            [
                'aircraft' => 'VJ-A32001',
                'departure' => 'HAN',
                'arrival' => 'DAD',
                'departure_time' => '2026-09-11 14:00:00',
                'arrival_time' => '2026-09-11 15:25:00',
                'status' => 'cancelled',
            ],
            [
                'aircraft' => 'QH-A32101',
                'departure' => 'DAD',
                'arrival' => 'HAN',
                'departure_time' => '2026-09-12 08:00:00',
                'arrival_time' => '2026-09-12 09:25:00',
                'status' => 'scheduled',
            ],
        ];

        foreach ($flights as $data) {
            $aircraft = Aircraft::where(
                'registration_number',
                $data['aircraft']
            )->firstOrFail();

            $departureAirport = Airport::where(
                'iata_code',
                $data['departure']
            )->firstOrFail();

            $arrivalAirport = Airport::where(
                'iata_code',
                $data['arrival']
            )->firstOrFail();

            Flight::updateOrCreate(
                [
                    'aircraft_id' => $aircraft->id,
                    'departure_time' => $data['departure_time'],
                ],
                [
                    'departure_airport_id' => $departureAirport->id,
                    'arrival_airport_id' => $arrivalAirport->id,
                    'arrival_time' => $data['arrival_time'],
                    'status' => $data['status'],
                ]
            );
        }
    }
}