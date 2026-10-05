<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightSearchRequest;
use App\Models\Airport;
use App\Models\FareClass;
use App\Services\BookingExpiryService;
use App\Services\FlightSearchService;

class FlightSearchController extends Controller
{
    public function __construct(
        private BookingExpiryService $expiryService,
        private FlightSearchService $searchService,
    ) {}

    public function results(FlightSearchRequest $request)
    {
        $this->expiryService->cancelAllExpired();
        $data = $request->validated();
        $leg = $request->legs()[0];

        $departureAirport = Airport::findOrFail($leg['from']);
        $arrivalAirport = Airport::findOrFail($leg['to']);
        $fareClass = FareClass::findOrFail($data['fare_class_id']);

        $flights = $this->searchService->searchLeg(
            $departureAirport,
            $arrivalAirport,
            $leg['date'],
            (int) $data['fare_class_id']
        );

        // Các key truyền sang view giữ nguyên
        return view('flights.results', [
            'flights' => $flights,
            'departureAirport' => $departureAirport,
            'arrivalAirport' => $arrivalAirport,
            'fareClass' => $fareClass,
            'departureDate' => $data['departure_date'],
            'adults' => $data['adults'],
            'children' => $data['children'] ?? 0,
            'infants' => $data['infants'] ?? 0,
        ]);
    }
}
