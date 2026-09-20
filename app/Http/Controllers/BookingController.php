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
        $pending = session('pending_selection');

        if (!$pending) {
            return redirect()->route('home')
                ->with('error', 'Vui lòng chọn chuyến bay trước khi nhập thông tin hành khách.');
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
        $pending = session('pending_selection');

        if (!$pending) {
            return redirect()->route('home')
                ->with('error', 'Vui lòng chọn chuyến bay trước khi nhập thông tin hành khách.');
        }

        $data = $request->validated();
        $seatsNeeded = $pending['adults'] + $pending['children'];

        try {
            $booking = DB::transaction(function () use ($pending, $data, $seatsNeeded) {
                // FINAL check + ATOMIC hold — gộp từ SeatSelectionController::hold() cũ
                $candidates = FlightSeat::where('flight_id', $pending['flight_id'])
                    ->where('fare_class_id', $pending['fare_class_id'])
                    ->where(function ($q) {
                        $q->where('status', 'available')
                        ->orWhere(function ($q2) {
                            $q2->where('status', 'held')
                                ->where('held_until', '<', now());
                        });
                    })
                    ->lockForUpdate()
                    ->inRandomOrder()
                    ->limit($seatsNeeded)
                    ->get();

                if ($candidates->count() < $seatsNeeded) {
                    throw new \RuntimeException('NOT_ENOUGH_SEATS');
                }

                $heldSeats = $candidates->sortBy('id')->values();

                FlightSeat::whereIn('id', $heldSeats->pluck('id'))->update([
                    'status'     => 'held',
                    'held_by'    => auth()->id(),
                    'held_until' => now()->addMinutes(config('booking.seat_hold_minutes')),
                ]);

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

                $booking->update(['total_amount' => $totalAmount]);

                return $booking;
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'NOT_ENOUGH_SEATS') {
                return back()->withInput()
                    ->with('error', 'Rất tiếc, ghế vừa hết trong lúc bạn điền thông tin. Vui lòng thử lại.');
            }
            throw $e;
        }

        session()->forget('pending_selection');

        return redirect()->route('payment.show', $booking)
            ->with('status', 'Đặt vé thành công, tiến hành thanh toán.');
    }
}