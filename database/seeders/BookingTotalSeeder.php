<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Seeder;

class BookingTotalSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::with([
            'bookingFlights.tickets.baggageAddon',
        ])->get();

        foreach ($bookings as $booking) {
            $total = 0;

            foreach ($booking->bookingFlights as $bookingFlight) {
                foreach ($bookingFlight->tickets as $ticket) {
                    $total += (float) $ticket->price;

                    if ($ticket->baggageAddon) {
                        $total += (float) $ticket->baggageAddon->price;
                    }
                }
            }

            $booking->update([
                'total_amount' => $total,
            ]);
        }
    }
}