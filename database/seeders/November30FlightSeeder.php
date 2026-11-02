<?php

namespace Database\Seeders;

use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class November30FlightSeeder extends Seeder
{
    private const DATE = '2026-11-30';

    public function run(): void
    {
        $aircraft = Aircraft::where('registration_number', 'VN-A32101')->firstOrFail();
        $economy = FareClass::where('name', 'Economy')->firstOrFail();
        $seats = $aircraft->seats()->where('seat_class', 'economy')->get();

        $flights = [
            // Bốn lựa chọn cho chuyến đơn SGN → HAN.
            ['SGN', 'HAN', '06:00', '08:10'],
            ['SGN', 'HAN', '09:00', '11:10'],
            ['SGN', 'HAN', '12:00', '14:10'],
            ['SGN', 'HAN', '15:00', '17:10'],

            // Hai lựa chọn mỗi chiều cho chuyến khứ hồi cùng ngày.
            ['SGN', 'HAN', '06:30', '08:40'],
            ['SGN', 'HAN', '10:30', '12:40'],
            ['HAN', 'SGN', '14:00', '16:10'],
            ['HAN', 'SGN', '19:00', '21:10'],

            // Hai lựa chọn cho mỗi chặng của hành trình đa chặng SGN → HAN → DAD.
            ['SGN', 'HAN', '07:00', '09:10'],
            ['SGN', 'HAN', '10:00', '12:10'],
            ['HAN', 'DAD', '13:00', '14:25'],
            ['HAN', 'DAD', '16:00', '17:25'],
        ];

        foreach ($flights as [$departureCode, $arrivalCode, $departureTime, $arrivalTime]) {
            $departureAirport = Airport::where('iata_code', $departureCode)->firstOrFail();
            $arrivalAirport = Airport::where('iata_code', $arrivalCode)->firstOrFail();

            $flight = Flight::updateOrCreate(
                [
                    'aircraft_id' => $aircraft->id,
                    'departure_time' => Carbon::parse(self::DATE.' '.$departureTime, $departureAirport->timezone)->utc(),
                ],
                [
                    'departure_airport_id' => $departureAirport->id,
                    'arrival_airport_id' => $arrivalAirport->id,
                    'arrival_time' => Carbon::parse(self::DATE.' '.$arrivalTime, $arrivalAirport->timezone)->utc(),
                    'status' => 'scheduled',
                ]
            );

            foreach ($seats as $seat) {
                FlightSeat::firstOrCreate(
                    ['flight_id' => $flight->id, 'seat_id' => $seat->id],
                    [
                        'fare_class_id' => $economy->id,
                        'price' => $economy->base_price,
                        'status' => 'available',
                    ]
                );
            }
        }

        // Bốn chuyến quốc tế SGN → NRT, dùng máy bay Boeing 787.
        $internationalAircraft = Aircraft::where('registration_number', 'VN-B78701')->firstOrFail();
        $internationalSeats = $internationalAircraft->seats()->where('seat_class', 'economy')->get();

        foreach ([
            ['06:00', '14:00'],
            ['09:00', '17:00'],
            ['12:00', '20:00'],
            ['15:00', '23:00'],
        ] as [$departureTime, $arrivalTime]) {
            $departureAirport = Airport::where('iata_code', 'SGN')->firstOrFail();
            $arrivalAirport = Airport::where('iata_code', 'NRT')->firstOrFail();

            $flight = Flight::updateOrCreate(
                [
                    'aircraft_id' => $internationalAircraft->id,
                    'departure_time' => Carbon::parse(self::DATE.' '.$departureTime, $departureAirport->timezone)->utc(),
                ],
                [
                    'departure_airport_id' => $departureAirport->id,
                    'arrival_airport_id' => $arrivalAirport->id,
                    'arrival_time' => Carbon::parse(self::DATE.' '.$arrivalTime, $arrivalAirport->timezone)->utc(),
                    'status' => 'scheduled',
                ]
            );

            foreach ($internationalSeats as $seat) {
                FlightSeat::firstOrCreate(
                    ['flight_id' => $flight->id, 'seat_id' => $seat->id],
                    [
                        'fare_class_id' => $economy->id,
                        'price' => $economy->base_price * 3,
                        'status' => 'available',
                    ]
                );
            }
        }

        $this->command?->info('Đã thêm dữ liệu chuyến bay ngày 30/11/2026: 4 chuyến đơn, 4 chuyến khứ hồi, 4 chuyến đa chặng và 4 chuyến quốc tế SGN → NRT.');
    }
}
