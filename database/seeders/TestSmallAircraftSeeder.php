<?php

// TEST-ONLY SEEDER — không dùng trong DatabaseSeeder.php chính
// Chạy tay: php artisan db:seed --class=TestSmallAircraftSeeder
// Mục đích: tạo chuyến bay ít ghế để test race-condition/seat-hold

namespace Database\Seeders;

use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Seat;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TestSmallAircraftSeeder extends Seeder
{
    public function run(): void
    {
        // Máy bay nhỏ, ít ghế - phục vụ test C4 (hết ghế) và C10 (rollback)
        $airline = Airline::first();
        $fareClass = FareClass::first();
        $departureAirport = Airport::where('iata_code', 'SGN')->firstOrFail();
        $arrivalAirport = Airport::where('iata_code', 'HAN')->firstOrFail();

        $aircraft = Aircraft::create([
            'airline_id' => $airline->id,
            'model' => 'ATR 72 (Test)',
            'registration_number' => 'VN-TEST' . now()->timestamp,
            'total_seats' => 6,
        ]);

        $seats = collect();
        foreach (range(1, 6) as $i) {
            $seats->push(Seat::create([
                'aircraft_id' => $aircraft->id,
                'seat_number' => "1{$i}A",
                'seat_class' => 'economy',
                'position' => match ($i % 3) {
                    1 => 'window',
                    2 => 'middle',
                    0 => 'aisle',
                },
            ]));
        }

        // 3 chuyến trong ngày 30/9 - giờ khác nhau để test song song nhiều luồng
        $slots = [
            ['08:00:00', '10:15:00'],
            ['13:00:00', '15:15:00'],
            ['19:00:00', '21:15:00'],
        ];

        foreach ($slots as [$dep, $arr]) {
            $flight = Flight::create([
                'aircraft_id' => $aircraft->id,
                'departure_airport_id' => $departureAirport->id,
                'arrival_airport_id' => $arrivalAirport->id,
                'departure_time' => Carbon::parse("2026-09-30 {$dep}"),
                'arrival_time' => Carbon::parse("2026-09-30 {$arr}"),
                'status' => 'scheduled',
            ]);

            foreach ($seats as $seat) {
                FlightSeat::create([
                    'flight_id' => $flight->id,
                    'seat_id' => $seat->id,
                    'fare_class_id' => $fareClass->id,
                    'price' => $fareClass->base_price,
                    'status' => 'available',
                ]);
            }

            $this->command->info("Tạo chuyến {$flight->id}: SGN->HAN {$dep} 30/09/2026, 6 ghế.");
        }
    }
}