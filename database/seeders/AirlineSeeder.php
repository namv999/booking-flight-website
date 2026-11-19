<?php

namespace Database\Seeders;

use App\Models\Airline;
use Illuminate\Database\Seeder;

class AirlineSeeder extends Seeder
{
    public function run(): void
    {
        // [tên, mã IATA, quốc gia]
        $airlines = [
            ['Vietnam Airlines', 'VN', 'Vietnam'],
            ['Vietjet Air', 'VJ', 'Vietnam'],
            ['Bamboo Airways', 'QH', 'Vietnam'],
            ['Pacific Airlines', 'BL', 'Vietnam'],
            ['Vietravel Airlines', 'VU', 'Vietnam'],
            ['Singapore Airlines', 'SQ', 'Singapore'],
            ['Thai Airways', 'TG', 'Thailand'],
            ['AirAsia', 'AK', 'Malaysia'],
            ['Korean Air', 'KE', 'South Korea'],
            ['Japan Airlines', 'JL', 'Japan'],
            ['All Nippon Airways', 'NH', 'Japan'],
            ['Cathay Pacific', 'CX', 'Hong Kong'],
            ['Emirates', 'EK', 'United Arab Emirates'],
            ['Qatar Airways', 'QR', 'Qatar'],
            ['Malaysia Airlines', 'MH', 'Malaysia'],
            ['China Airlines', 'CI', 'Taiwan'],
            ['Scoot', 'TR', 'Singapore'],
            ['Qantas', 'QF', 'Australia'],
            ['Air France', 'AF', 'France'],
            ['Turkish Airlines', 'TK', 'Turkey'],
        ];

        foreach ($airlines as [$name, $code, $country]) {
            Airline::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'logo_url' => null, 'country' => $country]
            );
        }
    }
}
