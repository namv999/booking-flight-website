<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FlightScheduleGenerator
{
    // [sân bay A, sân bay B, giá Economy cơ sở, phút bay, hãng luân phiên, giờ đi A->B, giờ đi B->A]
    // Giá là VND một chiều đã gồm thuế phí; giờ là giờ địa phương sân bay đi.
    private const ROUTES = [
        ['SGN', 'HAN', 2000000, 125, ['VN', 'VJ', 'QH', 'BL'], ['05:40', '10:10', '15:30', '21:45'], ['06:30', '11:20', '16:40', '22:30']],
        ['SGN', 'DAD', 1500000, 80, ['VN', 'VJ'], ['07:15', '12:40', '18:40'], ['09:30', '14:40', '20:50']],
        ['HAN', 'DAD', 1400000, 85, ['VU', 'VJ'], ['08:20', '19:10'], ['10:30', '21:40']],
        ['SGN', 'PQC', 1400000, 60, ['VN', 'QH'], ['11:00'], ['13:10']],
        ['SGN', 'CXR', 1300000, 65, ['VN', 'QH'], ['08:50'], ['10:55']],
        ['SGN', 'BKK', 2800000, 105, ['TG', 'VJ'], ['07:30', '22:15'], ['10:20', '00:40']],
        ['HAN', 'BKK', 3200000, 130, ['AK', 'VJ'], ['13:20'], ['16:10']],
        ['SGN', 'SIN', 2900000, 130, ['SQ', 'TR'], ['06:55', '19:35'], ['09:30', '23:10']],
        ['SGN', 'ICN', 6000000, 335, ['KE'], ['23:30'], ['08:15']],
        ['HAN', 'ICN', 6200000, 290, ['VN'], ['00:20'], ['14:40']],
        ['SGN', 'NRT', 7500000, 365, ['JL'], ['23:55'], ['11:00']],
        ['HAN', 'NRT', 7800000, 330, ['NH'], ['01:10'], ['17:25']],
        ['SGN', 'HKG', 3800000, 190, ['CX'], ['14:05'], ['18:30']],
        ['SGN', 'KUL', 2600000, 120, ['MH', 'AK'], ['12:15'], ['15:00']],
        ['SGN', 'TPE', 4500000, 220, ['CI'], ['02:20'], ['09:50']],
        ['SGN', 'DXB', 11000000, 440, ['EK'], ['01:35'], ['03:10']],
        ['SGN', 'DOH', 12000000, 410, ['QR'], ['00:05'], ['02:45']],
        ['SGN', 'SYD', 11000000, 480, ['QF'], ['21:20'], ['12:40']],
        ['SGN', 'CDG', 16000000, 770, ['AF'], ['23:10'], ['13:05']],
        ['SGN', 'IST', 14000000, 650, ['TK'], ['22:50'], ['14:30']],
    ];

    // Hãng giá rẻ thấp hơn; hãng không liệt kê dùng hệ số 1.10.
    private const CARRIER_FACTOR = [
        'VJ' => 0.80, 'BL' => 0.85, 'AK' => 0.75, 'TR' => 0.80, 'VU' => 0.90, 'QH' => 1.00,
        'VN' => 1.15, 'EK' => 1.20, 'QR' => 1.20, 'SQ' => 1.20, 'QF' => 1.20,
    ];

    private array $airports = [];

    private array $fares = [];

    private array $fleet = [];

    private array $seatCache = [];

    private array $cursor = [];

    private array $existing = [];

    private array $buffer = [];

    private array $stats = [];

    public function generate(Carbon $from, Carbon $to, float $occupancy = 0.35): array
    {
        $this->airports = DB::table('airports')->whereNull('deleted_at')->get()->keyBy('iata_code')->all();
        $this->fares = DB::table('fare_classes')->whereNull('deleted_at')->pluck('id', 'name')->all();
        $this->buffer = [];
        $this->stats = ['flights' => 0, 'international' => 0, 'skipped' => 0, 'seats' => 0, 'missing' => []];

        $this->loadFleet();
        $this->loadExisting($from, $to);

        $today = now('Asia/Ho_Chi_Minh')->startOfDay();

        for ($day = $from->copy(), $d = 0; $day->lte($to); $day->addDay(), $d++) {
            $current = $day->copy();

            DB::transaction(function () use ($current, $d, $today, $occupancy) {
                foreach (self::ROUTES as $route) {
                    $this->generateRoute($route, $current, $d, $today, $occupancy);
                }
                $this->flush();
            });
        }

        $this->stats['missing'] = array_keys($this->stats['missing']);

        return $this->stats;
    }

    private function generateRoute(array $route, Carbon $day, int $dayIndex, Carbon $today, float $occupancy): void
    {
        [$codeA, $codeB, $base, $minutes, $carriers, $timesAB, $timesBA] = $route;
        $a = $this->airports[$codeA] ?? null;
        $b = $this->airports[$codeB] ?? null;

        if (! $a || ! $b) {
            $this->stats['missing'][$codeA.'-'.$codeB] = true;

            return;
        }

        foreach ([[$a, $b, $timesAB], [$b, $a, $timesBA]] as [$from, $to, $times]) {
            foreach ($times as $i => $time) {
                $carrier = $carriers[($i + $dayIndex) % count($carriers)];
                $this->createFlight($from, $to, $time, $day, $minutes, $base, $carrier, $today, $occupancy);
            }
        }
    }

    private function createFlight(object $from, object $to, string $time, Carbon $day, int $minutes, int $base, string $carrier, Carbon $today, float $occupancy): void
    {
        $localDep = Carbon::parse($day->toDateString().' '.$time, $from->timezone);
        $depStr = $localDep->copy()->setTimezone('UTC')->format('Y-m-d H:i:s');
        $arrStr = $localDep->copy()->setTimezone('UTC')->addMinutes($minutes)->format('Y-m-d H:i:s');
        $key = "{$from->id}|{$to->id}|{$depStr}";

        if (isset($this->existing[$key])) {
            $this->stats['skipped']++;

            return;
        }

        $aircraftId = $this->pickAircraft($carrier, $minutes >= 280);

        if (! $aircraftId) {
            $this->stats['missing'][$carrier] = true;

            return;
        }

        // Seed cố định theo chuyến để giá, trạng thái và độ lấp đầy lặp lại được.
        mt_srand(crc32($from->iata_code.$to->iata_code.$depStr));
        $jitter = mt_rand(0, 10000) / 10000;
        $roll = mt_rand(0, 10000) / 10000;
        $status = $roll < 0.01 ? 'cancelled' : ($roll < 0.04 ? 'delayed' : 'scheduled');
        $flightOccupancy = min(0.95, $occupancy * (0.4 + 1.2 * mt_rand(0, 10000) / 10000));
        $international = $from->country !== $to->country;
        $eco = $this->economyPrice($base, $carrier, $localDep, $today, $jitter);
        $prices = [
            'Economy' => $eco,
            'Business' => $this->round10k($eco * ($international ? 3.6 : 2.4)),
            'First' => $this->round10k($eco * ($international ? 6.0 : 4.5)),
        ];

        $now = now();
        $flightId = DB::table('flights')->insertGetId([
            'aircraft_id' => $aircraftId,
            'departure_airport_id' => $from->id,
            'arrival_airport_id' => $to->id,
            'departure_time' => $depStr,
            'arrival_time' => $arrStr,
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($this->seatsOf($aircraftId) as $seat) {
            $name = match ($seat->seat_class) {
                'first' => 'First',
                'business' => 'Business',
                default => 'Economy',
            };

            if (! isset($this->fares[$name])) {
                continue;
            }

            $this->buffer[] = [
                'flight_id' => $flightId,
                'seat_id' => $seat->id,
                'fare_class_id' => $this->fares[$name],
                'price' => $prices[$name],
                'status' => (mt_rand(0, 10000) / 10000) < $flightOccupancy ? 'booked' : 'available',
                'held_by' => null,
                'held_until' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($this->buffer) >= 1000) {
                $this->flush();
            }
        }

        $this->existing[$key] = true;
        $this->stats['flights']++;
        $this->stats['international'] += $international ? 1 : 0;
    }

    private function economyPrice(int $base, string $carrier, Carbon $localDep, Carbon $today, float $jitter): int
    {
        $price = $base * (self::CARRIER_FACTOR[$carrier] ?? 1.10);
        $dow = $localDep->dayOfWeek;
        $price *= in_array($dow, [0, 5], true) ? 1.15 : (in_array($dow, [2, 3], true) ? 0.90 : 1.0);
        $hour = $localDep->hour;
        $price *= (($hour >= 6 && $hour < 9) || ($hour >= 17 && $hour < 20)) ? 1.10 : (($hour >= 22 || $hour < 5) ? 0.85 : 1.0);
        $lead = $today->diffInDays($localDep->copy()->startOfDay(), false);
        $price *= $lead <= 3 ? 1.25 : ($lead <= 7 ? 1.15 : ($lead <= 14 ? 1.05 : ($lead <= 30 ? 1.0 : 0.92)));
        $price *= 1 + ($jitter - 0.5) * 0.16;

        return $this->round10k($price);
    }

    private function round10k(float $value): int
    {
        return (int) (round($value / 10000) * 10000);
    }

    private function pickAircraft(string $carrier, bool $wide): ?int
    {
        $pool = $this->fleet[$carrier][$wide ? 'wide' : 'narrow'] ?? [];

        if (! $pool) {
            $pool = array_merge($this->fleet[$carrier]['wide'] ?? [], $this->fleet[$carrier]['narrow'] ?? []);
        }

        if (! $pool) {
            return null;
        }

        $key = $carrier.($wide ? 'W' : 'N');
        $this->cursor[$key] = ($this->cursor[$key] ?? -1) + 1;

        return $pool[$this->cursor[$key] % count($pool)];
    }

    private function loadFleet(): void
    {
        $this->fleet = [];
        $rows = DB::table('aircrafts')
            ->join('airlines', 'airlines.id', '=', 'aircrafts.airline_id')
            ->whereNull('aircrafts.deleted_at')
            ->whereNull('airlines.deleted_at')
            ->orderBy('aircrafts.id')
            ->get(['aircrafts.id', 'airlines.code', 'aircrafts.total_seats']);

        foreach ($rows as $row) {
            $this->fleet[$row->code][$row->total_seats >= 290 ? 'wide' : 'narrow'][] = $row->id;
        }
    }

    private function seatsOf(int $aircraftId)
    {
        return $this->seatCache[$aircraftId] ??= DB::table('seats')
            ->where('aircraft_id', $aircraftId)
            ->get(['id', 'seat_class']);
    }

    private function loadExisting(Carbon $from, Carbon $to): void
    {
        $this->existing = [];
        $rows = DB::table('flights')
            ->whereBetween('departure_time', [
                $from->copy()->subDay()->format('Y-m-d H:i:s'),
                $to->copy()->addDays(2)->format('Y-m-d H:i:s'),
            ])
            ->get(['departure_airport_id', 'arrival_airport_id', 'departure_time']);

        foreach ($rows as $row) {
            $this->existing["{$row->departure_airport_id}|{$row->arrival_airport_id}|{$row->departure_time}"] = true;
        }
    }

    private function flush(): void
    {
        if ($this->buffer) {
            DB::table('flight_seats')->insert($this->buffer);
            $this->stats['seats'] += count($this->buffer);
            $this->buffer = [];
        }
    }
}
