<?php

namespace App\Services;

use App\Models\Flight;
use App\Models\FlightSeat;
use Carbon\Carbon;

class JourneyValidationService
{
    // Index chặng đầu tiên chưa chọn chuyến; null nếu đã chọn đủ
    public function firstUnchosenLeg(array $pending): ?int
    {
        foreach ($pending['legs'] as $i => $leg) {
            if (empty($leg['flight_id'])) {
                return $i;
            }
        }

        return null;
    }

    // Chặng 0 không có route riêng (kết quả đến từ tham số tìm kiếm), nên dựng lại URL tìm kiếm từ session
    public function legUrl(array $pending, int $legIndex): string
    {
        if ($legIndex > 0) {
            return route('booking.leg', $legIndex);
        }

        $first = $pending['legs'][0];

        return route('flights.search.results', array_filter([
            'trip_type' => $pending['trip_type'],
            'departure_airport_id' => $first['from'],
            'arrival_airport_id' => $first['to'],
            'departure_date' => $first['date'],
            'return_date' => $pending['trip_type'] === 'round-trip' ? $pending['legs'][1]['date'] : null,
            'fare_class_id' => $pending['fare_class_id'],
            'adults' => $pending['adults'],
            'children' => $pending['children'],
            'infants' => $pending['infants'],
        ]));
    }

    // Trả về thông báo lỗi hoặc null nếu chuyến hợp lệ cho chặng $legIndex
    public function validateLeg(array $pending, int $legIndex, int $flightId, ?Flight $previous): ?string
    {
        $leg = $pending['legs'][$legIndex];

        // Còn hiệu lực + chưa cất cánh (so UTC trong SQL)
        $flight = Flight::with('departureAirport')
            ->whereKey($flightId)
            ->whereIn('status', ['scheduled', 'delayed'])
            ->where('departure_time', '>', now('UTC'))
            ->first();

        if (! $flight) {
            return 'Chuyến bay này không còn khả dụng, vui lòng chọn chuyến khác.';
        }

        // Ngày local theo timezone sân bay đi của chính chặng này
        $depUtc = Carbon::parse($flight->getRawOriginal('departure_time'), 'UTC');
        $localDate = $depUtc->copy()->setTimezone($flight->departureAirport->timezone)->toDateString();

        if (
            (int) $flight->departure_airport_id !== (int) $leg['from']
            || (int) $flight->arrival_airport_id !== (int) $leg['to']
            || $localDate !== $leg['date']
        ) {
            return 'Chuyến bay không khớp với chặng đã tìm kiếm, vui lòng tìm lại.';
        }

        if ($legIndex > 0) {
            if (! $previous) {
                return 'Vui lòng chọn chuyến bay của chặng trước.';
            }

            $prevArrival = Carbon::parse($previous->getRawOriginal('arrival_time'), 'UTC');

            if ($depUtc->lte($prevArrival)) {
                return 'Chuyến bay phải khởi hành sau khi chặng trước hạ cánh.';
            }
        }

        $seatsNeeded = (int) $pending['adults'] + (int) $pending['children'];

        $availableCount = FlightSeat::where('flight_id', $flight->id)
            ->where('fare_class_id', $pending['fare_class_id'])
            ->where(function ($q) {
                $q->where('status', 'available')
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'held')->where('held_until', '<', now());
                    });
            })
            ->count();

        if ($availableCount < $seatsNeeded) {
            return 'Không đủ ghế trống cho hạng vé này, vui lòng thử lại hoặc chọn chuyến khác.';
        }

        return null;
    }
}
