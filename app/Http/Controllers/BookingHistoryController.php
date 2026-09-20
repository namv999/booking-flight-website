<?php

namespace App\Http\Controllers;

class BookingHistoryController extends Controller
{
    public function index()
    {
        $bookings = auth()->user()->bookings()
            ->with(['bookingFlights.flight.departureAirport', 'bookingFlights.flight.arrivalAirport'])
            ->latest('created_at')
            ->paginate(10);

        return view('booking-history.index', compact('bookings'));
    }

    public function show($id)
    {
        $booking = auth()->user()->bookings()
            ->with([
                'passengers',
                'payment',
                'bookingFlights.flight.aircraft' => fn ($q) => $q->withTrashed(),
                'bookingFlights.flight.aircraft.airline' => fn ($q) => $q->withTrashed(),
                'bookingFlights.flight.departureAirport' => fn ($q) => $q->withTrashed(),
                'bookingFlights.flight.arrivalAirport' => fn ($q) => $q->withTrashed(),
                'bookingFlights.tickets.passenger',
                'bookingFlights.tickets.flightSeat.seat',
                'bookingFlights.tickets.baggageAddon',
            ])
            ->findOrFail($id);

        return view('booking-history.show', compact('booking'));
    }
}