<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'user@bookingflight.test')->firstOrFail();

        $bookings = [
            [
                'status' => 'pending',
                'total_amount' => 0,
            ],
            [
                'status' => 'paid',
                'total_amount' => 0,
            ],
            [
                'status' => 'cancelled',
                'total_amount' => 0,
            ],
        ];

        foreach ($bookings as $booking) {
            Booking::create([
                'user_id' => $user->id,
                'status' => $booking['status'],
                'total_amount' => $booking['total_amount'],
            ]);
        }
    }
}