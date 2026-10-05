<?php

namespace App\Services;

use App\Models\Airport;
use App\Models\Flight;
use App\Models\FlightSeat;
use Carbon\Carbon;

class FlightSearchService
{
    // Tìm chuyến cho MỘT chặng. Ngày local theo timezone sân bay đi của chính chặng đó
    public function searchLeg(Airport $departureAirport, Airport $arrivalAirport, string $date, int $fareClassId)
    {
        $startUtc = Carbon::parse($date, $departureAirport->timezone)->startOfDay()->setTimezone('UTC');
        $endUtc = Carbon::parse($date, $departureAirport->timezone)->endOfDay()->setTimezone('UTC');

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

        return Flight::query()
            ->joinSub($seatStats, 'seat_stats', function ($join) {
                $join->on('seat_stats.flight_id', '=', 'flights.id');
            })
            ->where('flights.departure_airport_id', $departureAirport->id)
            ->where('flights.arrival_airport_id', $arrivalAirport->id)
            ->whereBetween('flights.departure_time', [$startUtc, $endUtc])
            ->whereIn('flights.status', ['scheduled', 'delayed'])
            ->with(['aircraft.airline', 'departureAirport', 'arrivalAirport'])
            ->select('flights.*', 'seat_stats.min_price', 'seat_stats.available_seats')
            ->orderBy('seat_stats.min_price', 'asc')
            ->paginate(10)
            ->withQueryString();
    }
}
