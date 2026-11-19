<?php

namespace App\Services;

use App\Models\Airport;
use App\Models\Flight;
use App\Models\FlightSeat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FlightSearchService
{
    // Khung giờ theo giờ địa phương; to = 24 nghĩa là hết ngày.
    public const TIME_SLOTS = [
        'early_morning' => ['label' => 'Sáng sớm', 'from' => 0, 'to' => 6],
        'morning' => ['label' => 'Buổi sáng', 'from' => 6, 'to' => 12],
        'afternoon' => ['label' => 'Buổi chiều', 'from' => 12, 'to' => 18],
        'evening' => ['label' => 'Buổi tối', 'from' => 18, 'to' => 24],
    ];

    // Key là giá trị query string, value là nhãn hiển thị.
    public const SORTS = [
        'price_asc' => 'Giá thấp nhất',
        'price_desc' => 'Giá cao nhất',
        'departure_asc' => 'Cất cánh sớm nhất',
        'departure_desc' => 'Cất cánh muộn nhất',
        'arrival_asc' => 'Hạ cánh sớm nhất',
        'duration_asc' => 'Bay nhanh nhất',
    ];

    public function searchLeg(Airport $departureAirport, Airport $arrivalAirport, string $date, int $fareClassId, ?string $afterUtc = null, array $filters = [])
    {
        $filters = $this->normalizeFilters($filters);

        $query = $this->baseQuery($departureAirport, $arrivalAirport, $date, $fareClassId, $afterUtc)
            ->with(['aircraft.airline', 'departureAirport', 'arrivalAirport']);

        $this->applyFilters($query, $filters, $departureAirport, $arrivalAirport, $date);
        $this->applySort($query, $filters['sort']);

        return $query->paginate(10)->withQueryString();
    }

    // Facet values come from the unfiltered set so available airlines do not disappear.
    public function legFacets(Airport $departureAirport, Airport $arrivalAirport, string $date, int $fareClassId, ?string $afterUtc = null): array
    {
        $flights = $this->baseQuery($departureAirport, $arrivalAirport, $date, $fareClassId, $afterUtc)
            ->with('aircraft.airline')
            ->get();

        $airlines = $flights->map(fn ($flight) => $flight->aircraft?->airline)
            ->filter()
            ->groupBy('code')
            ->map(fn ($group) => [
                'code' => $group->first()->code,
                'name' => $group->first()->name,
                'count' => $group->count(),
            ])
            ->sortBy('name')
            ->values();

        return [
            'total' => $flights->count(),
            'airlines' => $airlines,
            'price_min' => $flights->isEmpty() ? null : (int) floor((float) $flights->min('min_price')),
            'price_max' => $flights->isEmpty() ? null : (int) ceil((float) $flights->max('min_price')),
        ];
    }

    public function normalizeFilters(array $raw): array
    {
        return [
            'airlines' => array_values(array_unique($raw['airlines'] ?? [])),
            'price_min' => isset($raw['price_min']) ? (int) $raw['price_min'] : null,
            'price_max' => isset($raw['price_max']) ? (int) $raw['price_max'] : null,
            'dep_time' => array_values(array_unique($raw['dep_time'] ?? [])),
            'arr_time' => array_values(array_unique($raw['arr_time'] ?? [])),
            'sort' => $raw['sort'] ?? 'price_asc',
        ];
    }

    public function hasActiveFilters(array $filters): bool
    {
        return ! empty($filters['airlines'])
            || $filters['price_min'] !== null
            || $filters['price_max'] !== null
            || ! empty($filters['dep_time'])
            || ! empty($filters['arr_time']);
    }

    private function baseQuery(Airport $departureAirport, Airport $arrivalAirport, string $date, int $fareClassId, ?string $afterUtc): Builder
    {
        $startUtc = Carbon::parse($date, $departureAirport->timezone)->startOfDay()->setTimezone('UTC');
        $endUtc = Carbon::parse($date, $departureAirport->timezone)->endOfDay()->setTimezone('UTC');

        $seatStats = FlightSeat::query()
            ->select('flight_id')
            ->selectRaw('MIN(price) as min_price')
            ->selectRaw('COUNT(id) as available_seats')
            ->where('fare_class_id', $fareClassId)
            ->where(function ($query) {
                $query->where('status', 'available')
                    ->orWhere(function ($heldQuery) {
                        $heldQuery->where('status', 'held')
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
            ->when($afterUtc, fn ($query) => $query->where('flights.departure_time', '>', $afterUtc))
            ->whereIn('flights.status', ['scheduled', 'delayed'])
            ->select('flights.*', 'seat_stats.min_price', 'seat_stats.available_seats');
    }

    private function applyFilters(Builder $query, array $filters, Airport $departureAirport, Airport $arrivalAirport, string $date): void
    {
        if ($filters['airlines']) {
            $query->whereHas('aircraft.airline', fn ($airlineQuery) => $airlineQuery->whereIn('airlines.code', $filters['airlines']));
        }

        // seat_stats.min_price is a derived-table column, so filter it in the outer WHERE.
        if ($filters['price_min'] !== null) {
            $query->where('seat_stats.min_price', '>=', $filters['price_min']);
        }
        if ($filters['price_max'] !== null) {
            $query->where('seat_stats.min_price', '<=', $filters['price_max']);
        }

        // Departure uses the searched day. Arrival may fall on an adjacent local day.
        if ($filters['dep_time']) {
            $this->whereTimeSlots($query, 'flights.departure_time', $filters['dep_time'], $date, $departureAirport->timezone, [0]);
        }
        if ($filters['arr_time']) {
            $this->whereTimeSlots($query, 'flights.arrival_time', $filters['arr_time'], $date, $arrivalAirport->timezone, [-1, 0, 1]);
        }
    }

    // Convert local time slots to UTC [from, to) intervals; selected slots are ORed.
    private function whereTimeSlots(Builder $query, string $column, array $slots, string $date, string $timezone, array $dayOffsets): void
    {
        $query->where(function ($outer) use ($column, $slots, $date, $timezone, $dayOffsets) {
            foreach ($slots as $slot) {
                $definition = self::TIME_SLOTS[$slot] ?? null;
                if (! $definition) {
                    continue;
                }

                foreach ($dayOffsets as $offset) {
                    $day = Carbon::parse($date, $timezone)->startOfDay()->addDays($offset);
                    $from = $day->copy()->setTime($definition['from'], 0)->setTimezone('UTC')->format('Y-m-d H:i:s');
                    $to = ($definition['to'] === 24
                        ? $day->copy()->addDay()->startOfDay()
                        : $day->copy()->setTime($definition['to'], 0)
                    )->setTimezone('UTC')->format('Y-m-d H:i:s');

                    $outer->orWhere(fn ($range) => $range->where($column, '>=', $from)->where($column, '<', $to));
                }
            }
        });
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_desc' => $query->orderByDesc('seat_stats.min_price')->orderBy('flights.departure_time'),
            'departure_asc' => $query->orderBy('flights.departure_time'),
            'departure_desc' => $query->orderByDesc('flights.departure_time'),
            'arrival_asc' => $query->orderBy('flights.arrival_time'),
            'duration_asc' => $query->orderByRaw($this->durationSql().' asc')->orderBy('flights.departure_time'),
            default => $query->orderBy('seat_stats.min_price')->orderBy('flights.departure_time'),
        };

        // Stable tie-breaker for pagination.
        $query->orderBy('flights.id');
    }

    // Fixed SQL only; no user input is included in the expression.
    private function durationSql(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? '(julianday(flights.arrival_time) - julianday(flights.departure_time))'
            : 'TIMESTAMPDIFF(MINUTE, flights.departure_time, flights.arrival_time)';
    }
}
