<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\Flight;
use Illuminate\Database\Seeder;

class BookingFlightSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::orderBy('id')->get();
        $flights = Flight::orderBy('id')->get();

        if ($bookings->count() < 3 || $flights->count() < 4) {
            return;
        }

        $bookingFlights = [
            [
                'booking_id' => $bookings[0]->id,
                'flight_id' => $flights[0]->id,
            ],
            [
                'booking_id' => $bookings[1]->id,
                'flight_id' => $flights[1]->id,
            ],
            [
                'booking_id' => $bookings[2]->id,
                'flight_id' => $flights[2]->id,
            ],
        ];

        foreach ($bookingFlights as $data) {
            BookingFlight::updateOrCreate(
                [
                    'booking_id' => $data['booking_id'],
                    'flight_id' => $data['flight_id'],
                ]
            );
        }
    }
}