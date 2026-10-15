<?php

namespace Database\Seeders;

use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Flight;
use Illuminate\Database\Seeder;

class FlightSeeder extends Seeder
{
    public function run(): void
    {
        // [máy bay, đi, đến, giờ đi UTC, giờ đến UTC, trạng thái (tuỳ chọn)]
        // Dữ liệu mẫu: lịch của từng máy bay không được kiểm tra tính khả thi vật lý
        $templates = [
            // nội địa
            ['VN-A32101', 'SGN', 'HAN', '00:00', '02:10'],
            ['VN-B78701', 'HAN', 'SGN', '07:00', '09:15'],
            ['VJ-A32001', 'SGN', 'DAD', '02:00', '03:20'],
            ['VJ-A32101', 'DAD', 'SGN', '09:00', '10:20'],
            ['VN-A32101', 'SGN', 'PQC', '03:30', '04:30'],
            ['QH-A32101', 'DAD', 'HAN', '01:00', '02:25'],
            ['VJ-A32001', 'HAN', 'DAD', '07:30', '08:55', 'cancelled'],
            ['QH-A32101', 'SGN', 'CXR', '04:00', '05:05', 'delayed'],
            // quốc tế
            ['VJ-A32101', 'SGN', 'BKK', '01:30', '03:20'],
            ['VJ-A32101', 'BKK', 'SGN', '05:00', '06:50'],
            ['VN-B78701', 'SGN', 'NRT', '00:30', '06:10'],
            ['VN-B78701', 'NRT', 'SGN', '08:00', '13:40'],
            ['VJ-A32001', 'HAN', 'BKK', '04:00', '05:55'],
            ['VJ-A32001', 'BKK', 'HAN', '08:00', '09:55'],
        ];

        $aircrafts = Aircraft::pluck('id', 'registration_number');
        $airports = Airport::pluck('id', 'iata_code');

        // Ngày tương đối: luôn là tương lai dù seed vào hôm nào (D+2 đến D+9)
        $firstDay = 2;

        for ($d = $firstDay; $d <= 9; $d++) {
            $date = now('UTC')->startOfDay()->addDays($d);

            foreach ($templates as $tpl) {
                [$reg, $from, $to, $dep, $arr] = $tpl;
                // chuyến hủy/hoãn chỉ có ở ngày đầu để các ngày sau còn chuyến để đặt
                $status = $d === $firstDay ? ($tpl[5] ?? 'scheduled') : 'scheduled';

                Flight::updateOrCreate(
                    [
                        'aircraft_id' => $aircrafts[$reg],
                        'departure_time' => $date->copy()->setTimeFromTimeString($dep)->toDateTimeString(),
                    ],
                    [
                        'departure_airport_id' => $airports[$from],
                        'arrival_airport_id' => $airports[$to],
                        'arrival_time' => $date->copy()->setTimeFromTimeString($arr)->toDateTimeString(),
                        'status' => $status,
                    ]
                );
            }
        }
    }
}
