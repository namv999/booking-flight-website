<?php

namespace Database\Seeders;

use App\Models\Aircraft;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use Illuminate\Database\Seeder;

class FlightSeatSeeder extends Seeder
{
    public function run(): void
    {
        $fareClasses = FareClass::all()->keyBy('name');

        Flight::with('aircraft.seats')->each(function (Flight $flight) use ($fareClasses) {
            foreach ($flight->aircraft->seats as $seat) {
                $fareClassName = match ($seat->seat_class) {
                    'business' => 'Business',
                    'first' => 'First',
                    default => 'Economy',
                };

                $fareClass = $fareClasses->get($fareClassName);

                if (!$fareClass) {
                    continue;
                }

                $price = match ($fareClassName) {
                    'First' => 5000000,
                    'Business' => 2500000,
                    default => 1000000,
                };

                FlightSeat::updateOrCreate(
                    [
                        'flight_id' => $flight->id,
                        'seat_id' => $seat->id,
                    ],
                    [
                        'fare_class_id' => $fareClass->id,
                        'price' => $price,
                        'status' => 'available',
                        'held_by' => null,
                        'held_until' => null,
                    ]
                );
            }
        });
    }
}