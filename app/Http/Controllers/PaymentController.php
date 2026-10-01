<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\FlightSeat;
use App\Models\Payment;
use App\Services\BookingExpiryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    // const PAYMENT_EXPIRE_MINUTES = 15;
    public function __construct(private BookingExpiryService $expiryService) {}

    public function show(Booking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        $this->expiryService->cancelIfExpired($booking);
        $booking->refresh();

        if ($booking->status === 'cancelled') {
            return redirect()->route('booking-history.show', $booking)
                ->with('error', 'Phiên đặt vé của bạn đã hết hạn. Ghế đã được giải phóng, vui lòng đặt lại.');
        }

        if ($booking->status === 'paid') {
            return redirect()->route('booking-history.show', $booking)
                ->with('status', 'Booking này đã thanh toán rồi.');
        }

        $expiresAt = $booking->created_at->addMinutes(config('booking.seat_hold_minutes'));

        return view('payment.show', [
            'booking' => $booking->load([
                'bookingFlights.flight.departureAirport',
                'bookingFlights.flight.arrivalAirport',
                'bookingFlights.flight.aircraft.airline',
                'bookingFlights.tickets.passenger',
                'bookingFlights.tickets.flightSeat.seat',
            ]),
            'expiresAt' => $expiresAt,
        ]);
    }

    public function store(Request $request, Booking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        $this->expiryService->cancelIfExpired($booking);
        $booking->refresh();

        if ($booking->status !== 'pending') {
            return match ($booking->status) {
                'cancelled' => redirect()->route('booking-history.show', $booking)
                    ->with('error', 'Phiên đặt vé của bạn đã hết hạn. Ghế đã được giải phóng, vui lòng đặt lại.'),
                'paid' => redirect()->route('booking-history.show', $booking)
                    ->with('status', 'Booking này đã thanh toán rồi.'),
                default => redirect()->route('payment.show', $booking)
                    ->with('error', 'Booking không còn ở trạng thái chờ thanh toán.'),
            };
        }

        $validated = $request->validate([
            'method' => 'required|in:the_tin_dung,vi_dien_tu',
            'simulate_result' => 'required|in:success,failed',
        ]);

        DB::transaction(function () use ($validated, $booking) {
            Payment::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'amount' => $booking->total_amount,
                    'method' => $validated['method'],
                    'status' => $validated['simulate_result'] === 'success' ? 'success' : 'failed',
                    'transaction_code' => strtoupper(Str::random(12)),
                    'paid_at' => $validated['simulate_result'] === 'success' ? now() : null,
                ]
            );

            if ($validated['simulate_result'] === 'success') {
                $booking->update(['status' => 'paid']);

                // Ghế đã thanh toán -> chuyển hẳn sang booked, không còn "held" nữa
                // (held_by/held_until không còn ý nghĩa với ghế đã bán, xóa theo)
                $flightSeatIds = $booking->bookingFlights()
                    ->with('tickets')
                    ->get()
                    ->pluck('tickets')
                    ->flatten()
                    ->pluck('flight_seat_id')
                    ->filter()
                    ->values();

                if ($flightSeatIds->isNotEmpty()) {
                    FlightSeat::whereIn('id', $flightSeatIds)
                        ->update([
                            'status' => 'booked',
                            'held_by' => null,
                            'held_until' => null,
                        ]);
                }
            }
        });

        if ($validated['simulate_result'] === 'success') {
            return redirect()->route('booking-history.show', $booking)
                ->with('status', 'Thanh toán thành công! Mã booking #'.$booking->id);
        }

        return redirect()->route('payment.show', $booking)
            ->with('error', 'Thanh toán thất bại, vui lòng thử lại.');
    }
}
