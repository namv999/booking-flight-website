<?php

namespace App\Http\Controllers;

use App\Services\PassportService;
use App\Services\PassportUpdateService;

class BookingHistoryController extends Controller
{
    public function index()
    {
        $bookings = auth()->user()->bookings()
            ->with([
                'bookingFlights.flight.departureAirport' => fn ($q) => $q->withTrashed(),
                'bookingFlights.flight.arrivalAirport' => fn ($q) => $q->withTrashed(),
            ])
            ->latest('created_at')
            ->paginate(10);

        return view('booking-history.index', compact('bookings'));
    }

    public function show($id)
    {
        $booking = auth()->user()->bookings()
            ->with([
                'passengers.passportUpdateRequests' => fn ($q) => $q->latest('id'),
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

        // Cập nhật hộ chiếu chỉ áp dụng cho đơn quốc tế đã thanh toán; $passportUpdateError null = được phép gửi
        $showPassportUpdate = $booking->status === 'paid' && app(PassportService::class)->isInternationalBooking($booking);
        $passportUpdateError = $showPassportUpdate ? app(PassportUpdateService::class)->bookingError($booking) : null;

        return view('booking-history.show', compact('booking', 'showPassportUpdate', 'passportUpdateError'));
    }
}
