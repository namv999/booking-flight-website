<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::orderBy('id')->get();

        if ($bookings->count() < 3) {
            return;
        }

        // Booking #2: thanh toán thành công
        Payment::updateOrCreate(
            [
                'booking_id' => $bookings[1]->id,
            ],
            [
                'amount' => 5000000,
                'method' => 'bank_transfer',
                'status' => 'success',
                'transaction_code' => 'TXN-20260826-000001',
                'paid_at' => now(),
            ]
        );

        // Booking #1: thanh toán thất bại
        Payment::updateOrCreate(
            [
                'booking_id' => $bookings[0]->id,
            ],
            [
                'amount' => 2680000,
                'method' => 'credit_card',
                'status' => 'failed',
                'transaction_code' => 'TXN-20260826-000002',
                'paid_at' => null,
            ]
        );
    }
}