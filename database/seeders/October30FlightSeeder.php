<?php

namespace Database\Seeders;

use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Seat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class October30FlightSeeder extends Seeder
{
    public function run(): void
    {
        $airline = Airline::where('code', 'VN')->firstOrFail();
        $economy = FareClass::where('name', 'Economy')->firstOrFail();
        $aircraft = Aircraft::updateOrCreate(
            ['registration_number' => 'VN-10SEAT26'],
            [
                'airline_id' => $airline->id,
                'model' => 'Airbus A321 (10-seat sample)',
                'total_seats' => 10,
            ]
        );

        $seats = collect(range(1, 10))->map(fn (int $number) => Seat::updateOrCreate(
            ['aircraft_id' => $aircraft->id, 'seat_number' => sprintf('2%02d', $number)],
            [
                'seat_class' => 'economy',
                'position' => match ($number % 3) {
                    1 => 'window',
                    2 => 'middle',
                    0 => 'aisle',
                },
            ]
        ));

        $flights = [
            ['SGN', 'HAN', '06:00', '08:10'],
            ['HAN', 'DAD', '10:00', '11:25'],
            ['DAD', 'CXR', '13:00', '14:00'],
            ['CXR', 'PQC', '16:00', '17:20'],
            ['PQC', 'SGN', '20:00', '21:30'],
        ];

        foreach ($flights as [$departureCode, $arrivalCode, $departureTime, $arrivalTime]) {
            $flight = Flight::updateOrCreate(
                [
                    'aircraft_id' => $aircraft->id,
                    'departure_time' => Carbon::parse("2026-10-30 {$departureTime}", 'Asia/Ho_Chi_Minh')->utc(),
                ],
                [
                    'departure_airport_id' => Airport::where('iata_code', $departureCode)->firstOrFail()->id,
                    'arrival_airport_id' => Airport::where('iata_code', $arrivalCode)->firstOrFail()->id,
                    'arrival_time' => Carbon::parse("2026-10-30 {$arrivalTime}", 'Asia/Ho_Chi_Minh')->utc(),
                    'status' => 'scheduled',
                ]
            );

            foreach ($seats as $seat) {
                FlightSeat::updateOrCreate(
                    ['flight_id' => $flight->id, 'seat_id' => $seat->id],
                    [
                        'fare_class_id' => $economy->id,
                        'price' => $economy->base_price,
                        'status' => 'available',
                        'held_by' => null,
                        'held_until' => null,
                    ]
                );
            }

            $this->command?->info("Chuyến {$flight->id}: {$departureCode} → {$arrivalCode}, 30/10/2026, 10 ghế.");
        }
    }
}
