<?php

namespace App\Services;

use App\Models\Airport;
use App\Models\Flight;
use App\Models\FlightSeat;
use Carbon\Carbon;

class FlightSearchService
{
    // Khung giờ cất cánh theo GIỜ ĐỊA PHƯƠNG của sân bay đi: [từ, đến). 'đến' = null nghĩa là hết ngày
    private const TIME_SLOTS = [
        'early' => ['00:00:00', '06:00:00'],
        'morning' => ['06:00:00', '12:00:00'],
        'afternoon' => ['12:00:00', '18:00:00'],
        'evening' => ['18:00:00', null],
    ];

    // $filters: airlines (mảng mã hãng), time_slots (mảng khóa khung giờ), sort ('price' | 'time')
    // Tìm chuyến cho MỘT chặng. Ngày local theo timezone sân bay đi của chính chặng đó
    public function searchLeg(Airport $departureAirport, Airport $arrivalAirport, string $date, int $fareClassId, ?string $afterUtc = null, array $filters = [])
    {
        $startUtc = Carbon::parse($date, $departureAirport->timezone)->startOfDay()->setTimezone('UTC');
        $endUtc = Carbon::parse($date, $departureAirport->timezone)->endOfDay()->setTimezone('UTC');

        // Độ lệch (giây) giữa giờ địa phương sân bay đi và UTC tại ngày đang tìm
        $offsetSeconds = Carbon::parse($date, $departureAirport->timezone)->getOffset();

        $airlines = array_filter((array) ($filters['airlines'] ?? []));
        $slots = array_values(array_intersect((array) ($filters['time_slots'] ?? []), array_keys(self::TIME_SLOTS)));
        $sort = $filters['sort'] ?? 'price';

        $seatStats = FlightSeat::query()
            ->select('flight_id')
            ->selectRaw('MIN(price) as min_price')
            ->selectRaw('COUNT(id) as available_seats')
            ->where('fare_class_id', $fareClassId)
            ->where(function ($q) {
                $q->where('status', 'available')
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'held')
                            ->where('held_until', '<', now());
                    });
            })
            ->groupBy('flight_id')
            ->having('available_seats', '>', 0);

        $query = Flight::query()
            ->joinSub($seatStats, 'seat_stats', function ($join) {
                $join->on('seat_stats.flight_id', '=', 'flights.id');
            })
            ->where('flights.departure_airport_id', $departureAirport->id)
            ->where('flights.arrival_airport_id', $arrivalAirport->id)
            ->whereBetween('flights.departure_time', [$startUtc, $endUtc])
            ->when($afterUtc, fn ($q) => $q->where('flights.departure_time', '>', $afterUtc)) // chuỗi UTC thô, so trực tiếp trong SQL
            ->whereIn('flights.status', ['scheduled', 'delayed'])
            // Lọc hãng hàng không theo mã (VN, VJ, QH...)
            ->when($airlines, function ($q) use ($airlines) {
                $q->whereHas('aircraft.airline', fn ($a) => $a->whereIn('code', $airlines));
            })
            // Lọc khung giờ cất cánh: cộng offset vào giờ UTC để ra giờ địa phương rồi so sánh phần TIME
            ->when($slots, function ($q) use ($slots, $offsetSeconds) {
                $q->where(function ($w) use ($slots, $offsetSeconds) {
                    foreach ($slots as $slot) {
                        [$from, $to] = self::TIME_SLOTS[$slot];

                        if ($to === null) {
                            $w->orWhereRaw(
                                'TIME(DATE_ADD(flights.departure_time, INTERVAL ? SECOND)) >= ?',
                                [$offsetSeconds, $from]
                            );
                        } else {
                            $w->orWhereRaw(
                                'TIME(DATE_ADD(flights.departure_time, INTERVAL ? SECOND)) >= ? AND TIME(DATE_ADD(flights.departure_time, INTERVAL ? SECOND)) < ?',
                                [$offsetSeconds, $from, $offsetSeconds, $to]
                            );
                        }
                    }
                });
            })
            ->with(['aircraft.airline', 'departureAirport', 'arrivalAirport'])
            ->select('flights.*', 'seat_stats.min_price', 'seat_stats.available_seats');

        if ($sort === 'time') {
            $query->orderBy('flights.departure_time', 'asc');
        } else {
            $query->orderBy('seat_stats.min_price', 'asc')->orderBy('flights.departure_time', 'asc');
        }

        return $query->paginate(10)->withQueryString();
    }
}