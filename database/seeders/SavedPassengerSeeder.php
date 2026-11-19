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
                'gender' => 'male',
                'nationality' => 'VNM',
                'document_type' => 'national_id',
                'document_number' => '079201000001',
                'cccd_number' => '079201000001',
                'cccd_issue_date' => '2021-06-15',
                'passport_number' => 'C1000001',
                'passport_issued_country' => 'VNM',
                'passport_issue_date' => '2022-01-10',
                'passport_expiry_date' => '2032-01-10',
                'date_of_birth' => '1998-05-12',
                'passenger_type_default' => 'adult',
                'relationship' => 'self',
            ],
            [
                'full_name' => 'Tran Thi Binh',
                'gender' => 'female',
                'nationality' => 'VNM',
                'document_type' => 'national_id',
                'document_number' => '079201000002',
                'cccd_number' => '079201000002',
                'cccd_issue_date' => '2020-08-20',
                'passport_number' => 'C1000002',
                'passport_issued_country' => 'VNM',
                'passport_issue_date' => '2021-05-15',
                'passport_expiry_date' => '2031-05-15',
                'date_of_birth' => '1995-09-20',
                'passenger_type_default' => 'adult',
                'relationship' => 'spouse',
            ],
            [
                'full_name' => 'Tran Minh Khang',
                'gender' => 'male',
                'nationality' => 'VNM',
                'document_type' => 'national_id',
                'document_number' => '079201000003',
                'cccd_number' => '079201000003',
                'cccd_issue_date' => '2024-07-05',
                'passport_number' => null,
                'passport_issued_country' => null,
                'passport_issue_date' => null,
                'passport_expiry_date' => null,
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
                $passenger
            );
        }
    }
}
