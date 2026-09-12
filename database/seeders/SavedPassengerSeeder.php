<?php

namespace Database\Seeders;

use App\Models\SavedPassenger;
use App\Models\User;
use Illuminate\Database\Seeder;

class SavedPassengerSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'user@bookingflight.test')
            ->firstOrFail();

        $savedPassengers = [
            [
                'full_name' => 'Nguyen Van An',
                'document_type' => 'national_id',
                'document_number' => '079201000001',
                'date_of_birth' => '1998-05-12',
                'passenger_type_default' => 'adult',
                'relationship' => 'self',
            ],
            [
                'full_name' => 'Tran Thi Binh',
                'document_type' => 'national_id',
                'document_number' => '079201000002',
                'date_of_birth' => '1995-09-20',
                'passenger_type_default' => 'adult',
                'relationship' => 'spouse',
            ],
            [
                'full_name' => 'Tran Minh Khang',
                'document_type' => 'national_id',
                'document_number' => '079201000003',
                'date_of_birth' => '2018-03-15',
                'passenger_type_default' => 'child',
                'relationship' => 'child',
            ],
        ];

        foreach ($savedPassengers as $passenger) {
            SavedPassenger::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'document_number' => $passenger['document_number'],
                ],
                [
                    'full_name' => $passenger['full_name'],
                    'date_of_birth' => $passenger['date_of_birth'],
                    'passenger_type_default' => $passenger['passenger_type_default'],
                    'relationship' => $passenger['relationship'],
                ]
            );
        }
    }
}