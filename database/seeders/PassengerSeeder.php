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

        if ($bookings->count() < 4) {
            return;
        }

        $passengers = [
            // Booking 1: Adult
            [
                'booking_id' => $bookings[0]->id,
                'full_name' => 'Nguyen Van An',
                'passenger_type' => 'adult',
                'document_type' => 'national_id',
                'document_number' => '079201000001',
                'date_of_birth' => '1998-05-12',
            ],

            // Booking 2: Adult
            [
                'booking_id' => $bookings[1]->id,
                'full_name' => 'Tran Thi Binh',
                'passenger_type' => 'adult',
                'document_type' => 'national_id',
                'document_number' => '079201000002',
                'date_of_birth' => '1995-09-20',
            ],

            // Booking 2: Child
            [
                'booking_id' => $bookings[1]->id,
                'full_name' => 'Tran Minh Khang',
                'passenger_type' => 'child',
                'document_type' => 'national_id',
                'document_number' => '079201000003',
                'date_of_birth' => '2018-03-15',
            ],

            // Booking 2: Infant
            [
                'booking_id' => $bookings[1]->id,
                'full_name' => 'Tran Bao Ngoc',
                'passenger_type' => 'infant',
                'document_type' => null, // infant thường chưa có giấy tờ riêng
                'document_number' => null,
                'date_of_birth' => '2025-11-10',
            ],

            // Booking 3: Adult
            [
                'booking_id' => $bookings[2]->id,
                'full_name' => 'Le Van Cuong',
                'passenger_type' => 'adult',
                'document_type' => 'national_id',
                'document_number' => '079201000004',
                'date_of_birth' => '1992-07-08',
            ],

            // Booking 4: Adult - Passport (chuyến quốc tế)
            [
                'booking_id' => $bookings[3]->id,
                'full_name' => 'Pham Thi Dao',
                'passenger_type' => 'adult',
                'document_type' => 'passport',
                'document_number' => 'P1234567',
                'date_of_birth' => '1990-02-14',
                'nationality' => 'VN',
                'document_issued_country' => 'VN',
                'document_expiry_date' => '2032-05-20',
            ],
        ];

        foreach ($passengers as $passenger) {
            Passenger::create($passenger);
        }
    }
}