<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\FlightSeat;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    // const PAYMENT_EXPIRE_MINUTES = 15;
    public function __construct(private \App\Services\BookingExpiryService $expiryService) {}

    public function show(Booking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        $this->expiryService->cancelIfExpired($booking);
        $booking->refresh();

        if ($booking->status === 'cancelled') {
            return redirect()->route('home')
                ->with('error', 'Booking đã hết hạn thanh toán và đã bị hủy.');
        }

        // TODO: đổi sang route('booking.history') khi B làm xong
        if ($booking->status === 'paid') {
            return redirect()->route('flights.search.form')
                ->with('status', 'Booking này đã thanh toán rồi.');
        }

        $expiresAt = $booking->created_at->addMinutes(config('booking.seat_hold_minutes'));

        return view('payment.show', [
            'booking' => $booking->load('bookingFlights.flight', 'bookingFlights.tickets'),
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
            return redirect()->route('payment.show', $booking)
                ->with('error', 'Booking không còn ở trạng thái chờ thanh toán.');
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
            }
        });

        if ($validated['simulate_result'] === 'success') {
            // TODO: đổi sang route('booking.history') khi B làm xong
            return redirect()->route('flights.search.form')
                ->with('status', 'Thanh toán thành công! Mã booking #' . $booking->id);
        }

        return redirect()->route('payment.show', $booking)
            ->with('error', 'Thanh toán thất bại, vui lòng thử lại.');
    }

    // private function cancelExpiredBooking(Booking $booking): void
    // {
    //     if ($booking->status !== 'pending') {
    //         return;
    //     }

    //     $expiresAt = Carbon::parse($booking->created_at)->addMinutes(self::PAYMENT_EXPIRE_MINUTES);
    //     if (! now()->greaterThan($expiresAt)) {
    //         return;
    //     }

    //     DB::transaction(function () use ($booking) {
    //         $flightSeatIds = $booking->bookingFlights()
    //             ->with('tickets')
    //             ->get()
    //             ->pluck('tickets')
    //             ->flatten()
    //             ->pluck('flight_seat_id')
    //             ->filter()
    //             ->values();

    //         if ($flightSeatIds->isNotEmpty()) {
    //             FlightSeat::whereIn('id', $flightSeatIds)
    //                 ->update([
    //                     'status' => 'available',
    //                     'held_by' => null,
    //                     'held_until' => null,
    //                 ]);
    //         }

    //         $booking->update(['status' => 'cancelled']);
    //     });
    // }
}