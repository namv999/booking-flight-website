<?php

namespace Database\Seeders;

use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * TEST-ONLY SEEDER — không chạy trong DatabaseSeeder.php chính.
 * Chạy tay: php artisan db:seed --class=RoundTripTestDataSeeder
 */
class RoundTripTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $aircraft = Aircraft::where('registration_number', 'VN-A32101')->firstOrFail();
        $economy = FareClass::where('name', 'Economy')->firstOrFail();
        $seats = $aircraft->seats()->get();

        // Đặt ngày đi và ngày về cách nhau để test hai lượt tìm kiếm rõ ràng.
        $departureDate = now('Asia/Ho_Chi_Minh')->addDays(7)->toDateString();
        $returnDate = now('Asia/Ho_Chi_Minh')->addDays(10)->toDateString();

        $flights = [
            ['SGN', 'HAN', $departureDate, '06:30', '08:40'],
            ['SGN', 'HAN', $departureDate, '13:00', '15:10'],
            ['HAN', 'SGN', $returnDate, '09:00', '11:10'],
            ['HAN', 'SGN', $returnDate, '18:00', '20:10'],
        ];

        foreach ($flights as [$departureCode, $arrivalCode, $date, $departureTime, $arrivalTime]) {
            $flight = Flight::updateOrCreate(
                [
                    'aircraft_id' => $aircraft->id,
                    'departure_time' => $this->toUtc($date, $departureTime),
                ],
                [
                    'departure_airport_id' => Airport::where('iata_code', $departureCode)->firstOrFail()->id,
                    'arrival_airport_id' => Airport::where('iata_code', $arrivalCode)->firstOrFail()->id,
                    'arrival_time' => $this->toUtc($date, $arrivalTime),
                    'status' => 'scheduled',
                ]
            );

            foreach ($seats as $seat) {
                // Chỉ economy seats được bán ở Economy; máy bay này có ghế Business ở hàng đầu.
                if ($seat->seat_class !== 'economy') {
                    continue;
                }

                FlightSeat::updateOrCreate(
                    ['flight_id' => $flight->id, 'seat_id' => $seat->id],
                    [
                        'fare_class_id' => $economy->id,
                        'price' => $economy->base_price,
                        'status' => 'available',
                        'held_by' => null,
                        'held_until' => null,
                    ]
                );
            }

            $this->command?->info(sprintf(
                'Chuyến %d: %s → %s, %s %s (giờ Việt Nam), Economy còn ghế.',
                $flight->id,
                $departureCode,
                $arrivalCode,
                Carbon::parse($date)->format('d/m/Y'),
                $departureTime
            ));
        }

        $this->command?->info("Round trip SGN → HAN: {$departureDate}; HAN → SGN: {$returnDate}.");
    }

    private function toUtc(string $date, string $time): Carbon
    {
        return Carbon::parse("{$date} {$time}", 'Asia/Ho_Chi_Minh')->utc();
    }
}
