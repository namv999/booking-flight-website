<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserDashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        $bookings = $user->bookings()
            ->with([
                'bookingFlights.flight.departureAirport',
                'bookingFlights.flight.arrivalAirport',
                'bookingFlights.flight.aircraft.airline',
                'bookingFlights.tickets.flightSeat.seat',
                'bookingFlights.tickets.passenger',
                'passengers',
            ])
            ->latest()
            ->get();

        $now = Carbon::now('UTC');

        $bookings->transform(function ($booking) {
            $flights = $booking->bookingFlights->map->flight->filter()->sortBy('departure_time')->values();
            $firstFlight = $flights->first();
            $lastFlight = $flights->last();

            $departureTime = $firstFlight?->departure_time ? Carbon::parse($firstFlight->departure_time) : null;
            $arrivalTime = $lastFlight?->arrival_time ? Carbon::parse($lastFlight->arrival_time) : null;

            if ($booking->status === 'cancelled') {
                $tripStatus = 'completed';
                $statusLabel = 'Đã hủy';
                $statusClass = 'bf-status--cancelled';
            } elseif ($departureTime && $departureTime->isPast()) {
                $tripStatus = 'completed';
                $statusLabel = 'Đã hoàn thành';
                $statusClass = 'bf-status--completed';
            } else {
                $tripStatus = 'upcoming';
                $statusLabel = $booking->status === 'paid' ? 'Đã xác nhận' : 'Chờ thanh toán';
                $statusClass = $booking->status === 'paid' ? 'bf-status--confirmed' : 'bf-status--pending';
            }

            $booking->trip_status = $tripStatus;
            $booking->status_label = $statusLabel;
            $booking->status_class = $statusClass;
            $booking->first_flight = $firstFlight;
            $booking->last_flight = $lastFlight;
            $booking->departure_time_parsed = $departureTime;
            $booking->arrival_time_parsed = $arrivalTime;

            return $booking;
        });

        $upcomingCount = $bookings->where('trip_status', 'upcoming')->count();
        $completedCount = $bookings->where('trip_status', 'completed')->count();
        $totalBookings = $bookings->count();
        $savedPassengersCount = $user->savedPassengers()->count();

        return view('dashboard.index', compact(
            'bookings',
            'upcomingCount',
            'completedCount',
            'totalBookings',
            'savedPassengersCount'
        ));
    }
}
