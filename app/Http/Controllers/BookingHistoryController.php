<?php

namespace App\Http\Controllers;

use App\Services\PassportUpdateService;
use Barryvdh\DomPDF\Facade\Pdf;

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

        // Cập nhật hồ sơ/hộ chiếu áp dụng cho đơn đã thanh toán (cả nội địa lẫn quốc tế); $passportUpdateError null = được phép gửi
        $showPassportUpdate = $booking->status === 'paid';
        $passportUpdateError = $showPassportUpdate ? app(PassportUpdateService::class)->bookingError($booking) : null;

        return view('booking-history.show', compact('booking', 'showPassportUpdate', 'passportUpdateError'));
    }

    public function pdf(int $id)
    {
        $booking = auth()->user()->bookings()
            ->where('status', 'paid')
            ->with([
                'bookingFlights.flight.aircraft' => fn ($query) => $query->withTrashed(),
                'bookingFlights.flight.aircraft.airline' => fn ($query) => $query->withTrashed(),
                'bookingFlights.flight.departureAirport' => fn ($query) => $query->withTrashed(),
                'bookingFlights.flight.arrivalAirport' => fn ($query) => $query->withTrashed(),
                'bookingFlights.tickets.passenger',
                'bookingFlights.tickets.flightSeat.seat',
            ])
            ->findOrFail($id);

        return Pdf::loadView('booking-history.ticket-pdf', compact('booking'))
            ->download('ve-booking-'.$booking->id.'.pdf');
    }
}
