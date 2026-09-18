<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightSearchRequest;
use App\Models\Airport;
use App\Models\FareClass;
use App\Models\Flight;

class FlightSearchController extends Controller
{
    public function __construct(private \App\Services\BookingExpiryService $expiryService) {}
    // Ẩn đi vì form tìm kiếm chuyến bay đã được tích hợp vào trang chủ
    // public function form()
    // {
    //     $airports = Airport::orderBy('city')->get();
    //     $fareClasses = FareClass::orderBy('base_price')->get(); // rẻ nhất trước -> default option đầu = "Phổ thông"

    //     return view('flights.search', compact('airports', 'fareClasses'));
    // } 

    public function results(FlightSearchRequest $request)
    {
        $this->expiryService->cancelAllExpired();
        $data = $request->validated();

        $seatStats = \App\Models\FlightSeat::query()
            ->select('flight_id')
            ->selectRaw('MIN(price) as min_price')
            ->selectRaw('COUNT(id) as available_seats')
            ->where('fare_class_id', $data['fare_class_id'])
            ->where(function ($q) {
                $q->where('status', 'available')
                ->orWhere(function ($q2) {
                    $q2->where('status', 'held')
                        ->where('held_until', '<', now());
                });
            })
            ->groupBy('flight_id')
            ->having('available_seats', '>', 0);

        $flights = Flight::query()
            ->joinSub($seatStats, 'seat_stats', function ($join) {
                $join->on('seat_stats.flight_id', '=', 'flights.id');
            })
            ->where('flights.departure_airport_id', $data['departure_airport_id'])
            ->where('flights.arrival_airport_id', $data['arrival_airport_id'])
            ->whereDate('flights.departure_time', $data['departure_date'])
            ->whereIn('flights.status', ['scheduled', 'delayed'])
            ->with(['aircraft.airline', 'departureAirport', 'arrivalAirport'])
            ->select('flights.*', 'seat_stats.min_price', 'seat_stats.available_seats')
            ->orderBy('seat_stats.min_price', 'asc')
            ->paginate(10)
            ->withQueryString();

        $departureAirport = Airport::findOrFail($data['departure_airport_id']);
        $arrivalAirport   = Airport::findOrFail($data['arrival_airport_id']);
        $fareClass        = FareClass::findOrFail($data['fare_class_id']);

        return view('flights.results', [
            'flights'          => $flights,
            'departureAirport' => $departureAirport,
            'arrivalAirport'   => $arrivalAirport,
            'fareClass'        => $fareClass,
            'departureDate'    => $data['departure_date'],
            'adults'           => $data['adults'],
            'children'         => $data['children'] ?? 0,
            'infants'          => $data['infants'] ?? 0,
        ]);
    }
}