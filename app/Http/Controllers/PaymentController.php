<?php

namespace App\Http\Controllers;

use App\Mail\BookingPaidMail;
use App\Models\Booking;
use App\Models\FlightSeat;
use App\Models\Payment;
use App\Models\Ticket;
use App\Services\BookingExpiryService;
use App\Services\PassportRiskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    // const PAYMENT_EXPIRE_MINUTES = 15;
    public function __construct(
        private BookingExpiryService $expiryService,
        private PassportRiskService $passportRisk,
    ) {}

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
            'passportRisk' => $this->passportRisk->assess($booking),
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

        $rules = [
            'method' => 'required|in:the_tin_dung,vi_dien_tu',
            'simulate_result' => 'required|in:success,failed',
        ];

        // Tính lại ở server mỗi lần thanh toán (kể cả retry sau khi thất bại), không tin client
        if ($this->passportRisk->assess($booking)['level'] !== null) {
            $rules['confirm_passport_risk'] = 'accepted';
        }

        $validated = $request->validate($rules, [
            'confirm_passport_risk.accepted' => 'Vui lòng xác nhận bạn đã đọc cảnh báo về hộ chiếu trước khi thanh toán.',
        ]);

        $isSuccess = $validated['simulate_result'] === 'success';

        $outcome = DB::transaction(function () use ($validated, $booking, $isSuccess) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== 'pending') {
                return 'not_pending';
            }

            $expiresAt = $locked->created_at->addMinutes(config('booking.seat_hold_minutes'));
            if (now()->greaterThanOrEqualTo($expiresAt)) {
                return 'expired';
            }

            $seatIds = collect();

            if ($isSuccess) {
                $bookingFlightIds = $locked->bookingFlights()->pluck('id');
                $tickets = Ticket::whereIn('booking_flight_id', $bookingFlightIds)->get();
                $seatIds = $tickets->pluck('flight_seat_id')->filter()->values();
                $seats = FlightSeat::whereIn('flight_seats.id', $seatIds)
                    ->join('flights', 'flights.id', '=', 'flight_seats.flight_id')
                    ->orderBy('flights.departure_time')
                    ->orderBy('flight_seats.flight_id')
                    ->orderBy('flight_seats.id')
                    ->lockForUpdate()
                    ->get('flight_seats.*');

                $needSeat = $tickets->whereNull('companion_adult_passenger_id')->count();
                $seatLost = $seats->count() !== $needSeat
                    || $seats->contains(fn ($seat) => $seat->status !== 'held'
                        || (int) $seat->held_by !== (int) $locked->user_id);

                if ($seatLost) {
                    return 'seat_lost';
                }
            }

            Payment::updateOrCreate(
                ['booking_id' => $locked->id],
                [
                    'amount' => $locked->total_amount,
                    'method' => $validated['method'],
                    'status' => $isSuccess ? 'success' : 'failed',
                    'transaction_code' => strtoupper(Str::random(12)),
                    'paid_at' => $isSuccess ? now() : null,
                ]
            );

            if ($isSuccess) {
                $locked->update(['status' => 'paid']);
                FlightSeat::whereIn('id', $seatIds)->update([
                    'status' => 'booked',
                    'held_by' => null,
                    'held_until' => null,
                ]);
            }

            return $isSuccess ? 'paid' : 'failed';
        });

        if ($outcome === 'expired') {
            $this->expiryService->cancelIfExpired($booking);

            return redirect()->route('booking-history.show', $booking)
                ->with('error', 'Phiên đặt vé của bạn đã hết hạn. Ghế đã được giải phóng, vui lòng đặt lại.');
        }

        if ($outcome === 'not_pending') {
            return redirect()->route('booking-history.show', $booking)
                ->with('error', 'Booking không còn ở trạng thái chờ thanh toán.');
        }

        if ($outcome === 'seat_lost') {
            return redirect()->route('home')
                ->with('error', 'Ghế của bạn không còn được giữ. Vui lòng đặt lại.');
        }

        if ($outcome === 'paid') {
            try {
                Mail::to(auth()->user())->send(new BookingPaidMail($booking->id));
            } catch (\Throwable $e) {
                report($e);
            }

            return redirect()->route('booking-history.show', $booking)
                ->with('status', 'Thanh toán thành công! Mã booking #'.$booking->id);
        }

        return redirect()->route('payment.show', $booking)
            ->with('error', 'Thanh toán thất bại, vui lòng thử lại.');
    }
}
