<?php

namespace Tests;

use App\Models\FareClass;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Dựng pending_selection theo shape legs[]; $flights là 1 Flight hoặc mảng Flight theo thứ tự chặng
    protected function pendingSelection($flights, FareClass $fareClass, int $adults = 1, int $children = 0, int $infants = 0): array
    {
        $flights = is_array($flights) ? $flights : [$flights];

        return [
            'trip_type' => count($flights) > 1 ? 'multi-city' : 'one-way',
            'fare_class_id' => $fareClass->id,
            'adults' => $adults,
            'children' => $children,
            'infants' => $infants,
            'legs' => array_map(fn ($f) => [
                'from' => $f->departure_airport_id,
                'to' => $f->arrival_airport_id,
                'date' => now()->addDays(2)->toDateString(),
                'flight_id' => $f->id,
            ], $flights),
        ];
    }
}
