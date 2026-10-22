<?php

namespace Database\Seeders;

use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use Illuminate\Database\Seeder;

class FlightSeatSeeder extends Seeder
{
    public function run(): void
    {
        $fareClasses = FareClass::all()->keyBy('name');
        $now = now();

        Flight::with(['aircraft.seats', 'departureAirport', 'arrivalAirport'])
            ->each(function (Flight $flight) use ($fareClasses, $now) {
                // Chuyến quốc tế giá gấp 3 lần nội địa
                $multiplier = $flight->departureAirport?->country !== $flight->arrivalAirport?->country ? 3 : 1;

                $rows = [];

                foreach ($flight->aircraft->seats as $seat) {
                    $fareClassName = match ($seat->seat_class) {
                        'business' => 'Business',
                        'first' => 'First',
                        default => 'Economy',
                    };

                    $fareClass = $fareClasses->get($fareClassName);

                    if (! $fareClass) {
                        continue;
                    }

                    $price = match ($fareClassName) {
                        'First' => 5000000,
                        'Business' => 2500000,
                        default => 1000000,
                    };

                    $rows[] = [
                        'flight_id' => $flight->id,
                        'seat_id' => $seat->id,
                        'fare_class_id' => $fareClass->id,
                        'price' => $price * $multiplier,
                        'status' => 'available',
                        'held_by' => null,
                        'held_until' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Khóa duy nhất (flight_id, seat_id) có sẵn nên upsert chạy đúng
                foreach (array_chunk($rows, 500) as $chunk) {
                    FlightSeat::upsert(
                        $chunk,
                        ['flight_id', 'seat_id'],
                        ['fare_class_id', 'price', 'status', 'held_by', 'held_until', 'updated_at']
                    );
                }
            });
    }
}
