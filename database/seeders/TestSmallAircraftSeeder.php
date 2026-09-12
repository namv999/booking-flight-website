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

class TestSmallAircraftSeeder extends Seeder
{
    public function run(): void
    {
        $airline = Airline::first();
        $fareClass = FareClass::orderBy('base_price')->first();
        $departureAirport = Airport::first();
        $arrivalAirport = Airport::where('id', '!=', $departureAirport?->id)->first();

        if (!$airline || !$fareClass || !$departureAirport || !$arrivalAirport) {
            $this->command->error('Thiếu dữ liệu gốc (airline/fare_class/airport) - chạy DatabaseSeeder chính trước.');
            return;
        }

        $aircraft = Aircraft::create([
            'airline_id' => $airline->id,
            'model' => 'TEST-9SEAT',
            'registration_number' => 'TEST-' . now()->timestamp,
            'total_seats' => 9,
        ]);

        $seatIds = [];
        foreach (range(1, 9) as $i) {
            $seat = Seat::create([
                'aircraft_id' => $aircraft->id,
                'seat_number' => '1' . chr(64 + $i), // 1A..1I
                'seat_class' => 'economy',
                'position' => null,
            ]);
            $seatIds[] = $seat->id;
        }

        $flight = Flight::create([
            'aircraft_id' => $aircraft->id,
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'departure_time' => now()->addDays(3)->setTime(9, 0),
            'arrival_time' => now()->addDays(3)->setTime(11, 0),
            'status' => 'scheduled',
        ]);

        foreach ($seatIds as $seatId) {
            FlightSeat::create([
                'flight_id' => $flight->id,
                'seat_id' => $seatId,
                'fare_class_id' => $fareClass->id,
                'price' => $fareClass->base_price,
                'status' => 'available',
            ]);
        }

        $this->command->info("Flight test ID: {$flight->id} | {$departureAirport->iata_code} -> {$arrivalAirport->iata_code} | 9 ghế hạng {$fareClass->name}");
    }
}