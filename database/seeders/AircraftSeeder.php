<?php

namespace Database\Seeders;

use App\Models\Aircraft;
use App\Models\Airline;
use Illuminate\Database\Seeder;

class AircraftSeeder extends Seeder
{
    // khóa => [tên model, số ghế]
    private const MODELS = [
        'A320' => ['Airbus A320', 180],
        'A321' => ['Airbus A321', 198],
        'B787' => ['Boeing 787-9', 294],
        'A350' => ['Airbus A350-900', 306],
        'B777' => ['Boeing 777-300ER', 342],
    ];

    // hãng => [loại => số chiếc]. Tổng = 100
    private const FLEET = [
        'VN' => ['A321' => 6, 'A350' => 4, 'B787' => 4],
        'VJ' => ['A320' => 6, 'A321' => 8],
        'QH' => ['A321' => 5],
        'BL' => ['A320' => 3],
        'VU' => ['A321' => 3],
        'SQ' => ['A350' => 3, 'B787' => 2],
        'TG' => ['B787' => 3, 'A320' => 2],
        'AK' => ['A320' => 4],
        'KE' => ['B787' => 3, 'A321' => 2],
        'JL' => ['B787' => 4],
        'NH' => ['B787' => 4],
        'CX' => ['A350' => 4],
        'EK' => ['B777' => 5],
        'QR' => ['B787' => 2, 'B777' => 3],
        'MH' => ['A350' => 2, 'A320' => 2],
        'CI' => ['A321' => 1, 'A350' => 2],
        'TR' => ['A320' => 3],
        'QF' => ['B787' => 4],
        'AF' => ['B777' => 3],
        'TK' => ['A321' => 1, 'B777' => 2],
    ];

    public function run(): void
    {
        foreach (self::FLEET as $code => $types) {
            $airline = Airline::where('code', $code)->firstOrFail();

            foreach ($types as $type => $count) {
                [$model, $seats] = self::MODELS[$type];

                for ($n = 1; $n <= $count; $n++) {
                    Aircraft::updateOrCreate(
                        ['registration_number' => $code.'-'.$type.str_pad($n, 2, '0', STR_PAD_LEFT)],
                        ['airline_id' => $airline->id, 'model' => $model, 'total_seats' => $seats]
                    );
                }
            }
        }
    }
}
