<?php

namespace App\Http\Controllers;

use App\Http\Requests\PassengerBookingRequest;
use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Passenger;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function create()
    {
        $pending = session('pending_hold');

        if (!$pending || now()->greaterThan(Carbon::parse($pending['expires_at']))) {
            return redirect()->route('flights.search.form')
                ->with('error', 'Phiên giữ ghế đã hết hạn, vui lòng tìm lại chuyến bay.');
        }

        $flight = Flight::with(['departureAirport', 'arrivalAirport', 'aircraft.airline'])
            ->findOrFail($pending['flight_id']);

        return view('booking.passengers', [
            'flight' => $flight,
            'pending' => $pending,
        ]);
    }

    public function store(PassengerBookingRequest $request)
    {
        $pending = session('pending_hold');

        if (!$pending || now()->greaterThan(Carbon::parse($pending['expires_at']))) {
            return redirect()->route('flights.search.form')
                ->with('error', 'Phiên giữ ghế đã hết hạn, vui lòng tìm lại chuyến bay.');
        }

        $data = $request->validated();

        try {
            $booking = DB::transaction(function () use ($pending, $data) {
                $seatIds = $pending['flight_seat_ids'];

                // re-check hold còn sống tại thời điểm submit
                $heldSeats = FlightSeat::whereIn('id', $seatIds)
                    ->where('status', 'held')
                    ->where('held_by', auth()->id())
                    ->lockForUpdate()
                    ->get()
                    ->sortBy('id')
                    ->values();

                if ($heldSeats->count() !== count($seatIds)) {
                    throw new \RuntimeException('HOLD_EXPIRED');
                }
                foreach ($heldSeats as $seat) {
                    if (!$seat->held_until || $seat->held_until->isPast()) {
                        throw new \RuntimeException('HOLD_EXPIRED');
                    }
                }

                $booking = Booking::create([
                    'user_id' => auth()->id(),
                    'status' => 'pending',
                    'total_amount' => 0,
                ]);

                $bookingFlight = BookingFlight::create([
                    'booking_id' => $booking->id,
                    'flight_id' => $pending['flight_id'],
                ]);

                $seatIndex = 0;
                $totalAmount = 0;
                $adultPassengerIds = [];
                $adultTicketPrices = [];

                // adult trước
                foreach ($data['adults'] as $adultData) {
                    $passenger = Passenger::create([
                        'booking_id' => $booking->id,
                        'full_name' => $adultData['full_name'],
                        'passenger_type' => 'adult',
                        'document_number' => $adultData['document_number'] ?? null,
                        'date_of_birth' => $adultData['date_of_birth'] ?? null,
                    ]);
                    $adultPassengerIds[] = $passenger->id;

                    $seat = $heldSeats[$seatIndex];
                    $ticket = Ticket::create([
                        'booking_flight_id' => $bookingFlight->id,
                        'passenger_id' => $passenger->id,
                        'flight_seat_id' => $seat->id,
                        'price' => $seat->price,
                        'ticket_code' => strtoupper(Str::random(8)),
                    ]);
                    $adultTicketPrices[$passenger->id] = $ticket->price;
                    $totalAmount += $ticket->price;
                    $seatIndex++;
                }

                // child sau
                foreach ($data['children'] ?? [] as $childData) {
                    $passenger = Passenger::create([
                        'booking_id' => $booking->id,
                        'full_name' => $childData['full_name'],
                        'passenger_type' => 'child',
                        'document_number' => $childData['document_number'] ?? null,
                        'date_of_birth' => $childData['date_of_birth'] ?? null,
                    ]);

                    $seat = $heldSeats[$seatIndex];
                    $ticket = Ticket::create([
                        'booking_flight_id' => $bookingFlight->id,
                        'passenger_id' => $passenger->id,
                        'flight_seat_id' => $seat->id,
                        'price' => $seat->price,
                        'ticket_code' => strtoupper(Str::random(8)),
                    ]);
                    $totalAmount += $ticket->price;
                    $seatIndex++;
                }

                // infant cuối cùng, cần adultPassengerIds đã có
                foreach ($data['infants'] ?? [] as $infantData) {
                    $companionAdultId = $adultPassengerIds[$infantData['companion_adult_index']];
                    $companionPrice = $adultTicketPrices[$companionAdultId];

                    $passenger = Passenger::create([
                        'booking_id' => $booking->id,
                        'full_name' => $infantData['full_name'],
                        'passenger_type' => 'infant',
                        'document_number' => $infantData['document_number'] ?? null,
                        'date_of_birth' => $infantData['date_of_birth'] ?? null,
                    ]);

                    $infantPrice = round($companionPrice * 0.10, 2);

                    $ticket = Ticket::create([
                        'booking_flight_id' => $bookingFlight->id,
                        'passenger_id' => $passenger->id,
                        'flight_seat_id' => null,
                        'companion_adult_passenger_id' => $companionAdultId,
                        'price' => $infantPrice,
                        'ticket_code' => strtoupper(Str::random(8)),
                    ]);
                    $totalAmount += $ticket->price;
                }

                FlightSeat::whereIn('id', $seatIds)->update([
                    'status' => 'booked',
                    'held_by' => null,
                    'held_until' => null,
                ]);

                $booking->update(['total_amount' => $totalAmount]);

                return $booking;
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'HOLD_EXPIRED') {
                session()->forget('pending_hold');
                return redirect()->route('flights.search.form')
                    ->with('error', 'Ghế giữ đã hết hạn, vui lòng đặt lại.');
            }
            throw $e;
        }

        session()->forget('pending_hold');

        return redirect()->route('flights.search.form')
            ->with('status', 'Đặt vé thành công! Mã booking #' . $booking->id);
    }
}