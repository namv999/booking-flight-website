<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Passenger;
use Illuminate\Database\Seeder;

class PassengerSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::orderBy('id')->get();

        if ($bookings->count() < 3) {
            return;
        }

        $passengers = [
            // Booking 1: Adult
            [
                'booking_id' => $bookings[0]->id,
                'full_name' => 'Nguyen Van An',
                'passenger_type' => 'adult',
                'document_number' => '079201000001',
                'date_of_birth' => '1998-05-12',
            ],

            // Booking 2: Adult
            [
                'booking_id' => $bookings[1]->id,
                'full_name' => 'Tran Thi Binh',
                'passenger_type' => 'adult',
                'document_number' => '079201000002',
                'date_of_birth' => '1995-09-20',
            ],

            // Booking 2: Child
            [
                'booking_id' => $bookings[1]->id,
                'full_name' => 'Tran Minh Khang',
                'passenger_type' => 'child',
                'document_number' => '079201000003',
                'date_of_birth' => '2018-03-15',
            ],

            // Booking 2: Infant
            [
                'booking_id' => $bookings[1]->id,
                'full_name' => 'Tran Bao Ngoc',
                'passenger_type' => 'infant',
                'document_number' => null,
                'date_of_birth' => '2025-11-10',
            ],

            // Booking 3: Adult
            [
                'booking_id' => $bookings[2]->id,
                'full_name' => 'Le Van Cuong',
                'passenger_type' => 'adult',
                'document_number' => '079201000004',
                'date_of_birth' => '1992-07-08',
            ],
        ];

        foreach ($passengers as $passenger) {
            Passenger::create($passenger);
        }
    }
}