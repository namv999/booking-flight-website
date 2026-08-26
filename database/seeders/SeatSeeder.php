<?php

namespace Database\Seeders;

use App\Models\Aircraft;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        Aircraft::query()->each(function (Aircraft $aircraft) {
            $seatCount = $aircraft->total_seats;

            for ($number = 1; $number <= $seatCount; $number++) {
                $row = intdiv($number - 1, 6) + 1;
                $positionInRow = ($number - 1) % 6;

                $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
                $letter = $letters[$positionInRow];

                if ($row <= 3) {
                    $seatClass = 'business';
                } else {
                    $seatClass = 'economy';
                }

                $position = match ($letter) {
                    'A', 'F' => 'window',
                    'B', 'E' => 'middle',
                    'C', 'D' => 'aisle',
                };

                Seat::updateOrCreate(
                    [
                        'aircraft_id' => $aircraft->id,
                        'seat_number' => "{$row}{$letter}",
                    ],
                    [
                        'seat_class' => $seatClass,
                        'position' => $position,
                    ]
                );
            }
        });
    }
}