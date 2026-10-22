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
 * Chạy tay: php artisan db:seed --class=MultiCityTestDataSeeder
 */
class MultiCityTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $aircraft = Aircraft::where('registration_number', 'VN-A32101')->firstOrFail();
        $economy = FareClass::where('name', 'Economy')->firstOrFail();
        $seats = $aircraft->seats()->where('seat_class', 'economy')->get();

        if ($seats->isEmpty()) {
            $this->command?->error('Không tìm thấy ghế Economy cho máy bay VN-A32101.');

            return;
        }

        // Ba chặng mẫu có ngày khác nhau; chặng 3 là open-jaw từ PQC.
        $firstDate = now('Asia/Ho_Chi_Minh')->addDays(7)->toDateString();
        $legs = [
            ['SGN', 'HAN', $firstDate, '06:30', '08:40'],
            ['HAN', 'DAD', Carbon::parse($firstDate)->addDay()->toDateString(), '10:00', '11:25'],
            ['PQC', 'SGN', Carbon::parse($firstDate)->addDays(2)->toDateString(), '13:00', '14:30'],
        ];

        foreach ($legs as [$departureCode, $arrivalCode, $date, $departureTime, $arrivalTime]) {
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
                // Không ghi đè trạng thái ghế đã được giữ hoặc bán khi seeder chạy lại.
                FlightSeat::firstOrCreate(
                    ['flight_id' => $flight->id, 'seat_id' => $seat->id],
                    [
                        'fare_class_id' => $economy->id,
                        'price' => $economy->base_price,
                        'status' => 'available',
                    ]
                );
            }

            $this->command?->info(sprintf(
                'Chuyến %d: %s → %s, %s %s (giờ Việt Nam), Economy.',
                $flight->id,
                $departureCode,
                $arrivalCode,
                Carbon::parse($date)->format('d/m/Y'),
                $departureTime
            ));
        }

        $this->command?->info(sprintf(
            'Tìm kiếm mẫu: SGN → HAN ngày %s, sau đó HAN → DAD ngày kế tiếp và PQC → SGN ngày thứ ba.',
            Carbon::parse($firstDate)->format('d/m/Y')
        ));
    }

    private function toUtc(string $date, string $time): Carbon
    {
        return Carbon::parse("{$date} {$time}", 'Asia/Ho_Chi_Minh')->utc();
    }
}
