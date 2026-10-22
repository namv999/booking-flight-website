<?php

namespace Tests;

use App\Models\FareClass;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Dựng pending_selection theo shape legs[]; $flights là 1 Flight hoặc mảng Flight theo thứ tự chặng
    protected function pendingSelection($flights, FareClass $fareClass, int $adults = 1, int $children = 0, int $infants = 0, bool $chosen = true): array
    {
        $flights = is_array($flights) ? $flights : [$flights];

        return [
            'trip_type' => match (count($flights)) {
                1 => 'one-way',
                2 => 'round-trip',
                default => 'multi-city'
            },
            'fare_class_id' => $fareClass->id,
            'adults' => $adults,
            'children' => $children,
            'infants' => $infants,
            'legs' => array_map(fn ($f) => [
                'from' => $f->departure_airport_id,
                'to' => $f->arrival_airport_id,
                // ngày local của chuyến, khớp với kiểm tra trong JourneyValidationService
                'date' => Carbon::parse($f->fresh()->getRawOriginal('departure_time'), 'UTC')
                    ->setTimezone($f->departureAirport->timezone)->toDateString(),
                'flight_id' => $chosen ? $f->id : null,
            ], $flights),
        ];
    }
}
