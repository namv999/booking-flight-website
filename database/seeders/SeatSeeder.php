<?php

namespace Database\Seeders;

use App\Models\Aircraft;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $letters = ['A', 'B', 'C', 'D', 'E', 'F'];

        Aircraft::query()->each(function (Aircraft $aircraft) use ($now, $letters) {
            $wide = $aircraft->total_seats >= 290;
            $rows = intdiv($aircraft->total_seats, 6);
            $batch = [];

            for ($row = 1; $row <= $rows; $row++) {
                $class = $wide
                    ? ($row <= 2 ? 'first' : ($row <= 7 ? 'business' : 'economy'))
                    : ($row <= 3 ? 'business' : 'economy');

                foreach ($letters as $letter) {
                    $batch[] = [
                        'aircraft_id' => $aircraft->id,
                        'seat_number' => $row.$letter,
                        'seat_class' => $class,
                        'position' => match ($letter) {
                            'A', 'F' => 'window',
                            'B', 'E' => 'middle',
                            default => 'aisle',
                        },
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            Seat::upsert($batch, ['aircraft_id', 'seat_number'], ['seat_class', 'position', 'updated_at']);
        });
    }
}
