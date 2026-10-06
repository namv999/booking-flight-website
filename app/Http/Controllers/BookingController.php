<?php

namespace App\Http\Controllers;

use App\Http\Requests\PassengerBookingRequest;
use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Passenger;
use App\Models\Ticket;
use App\Services\JourneyValidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function create(JourneyValidationService $journey)
    {
        $pending = session('pending_selection');

        if (! $pending) {
            return redirect()->route('home')
                ->with('error', 'Vui lòng chọn chuyến bay trước khi nhập thông tin hành khách.');
        }

        // Hành trình chưa chọn đủ chặng thì quay lại wizard
        $next = $journey->firstUnchosenLeg($pending);
        if ($next !== null) {
            return redirect($journey->legUrl($pending, $next));
        }

        $flightIds = collect($pending['legs'])->pluck('flight_id');

        // Thứ tự chặng suy ra từ departure_time (đã chốt, không có cột leg_order)
        $flights = Flight::with(['departureAirport', 'arrivalAirport', 'aircraft.airline'])
            ->whereIn('id', $flightIds)
            ->orderBy('departure_time')
            ->get();

        if ($flights->count() !== $flightIds->count()) {
            return redirect()->route('home')
                ->with('error', 'Vui lòng chọn chuyến bay trước khi nhập thông tin hành khách.');
        }

        return view('booking.passengers', [
            'flight' => $flights->first(), // view hiện tại chỉ hiển thị 1 chuyến; giai đoạn B đổi sang $flights
            'flights' => $flights,
            'pending' => $pending,
        ]);
    }

    public function store(PassengerBookingRequest $request)
    {
        $pending = session('pending_selection');

        if (! $pending) {
            return redirect()->route('home')
                ->with('error', 'Vui lòng chọn chuyến bay trước khi nhập thông tin hành khách.');
        }

        $data = $request->validated();

        if (
            count($data['adults']) !== (int) $pending['adults']
            || count($data['children'] ?? []) !== (int) $pending['children']
            || count($data['infants'] ?? []) !== (int) $pending['infants']
        ) {
            return back()->withInput()
                ->with('error', 'Số lượng hành khách không khớp với lựa chọn ban đầu. Vui lòng thử lại.');
        }

        $flightIds = collect($pending['legs'])->pluck('flight_id');

        // Mỗi chặng phải là một chuyến khác nhau (uq_bookingflights và giữ ghế theo flight_id)
        if ($flightIds->unique()->count() !== $flightIds->count()) {
            return redirect()->route('home')
                ->with('error', 'Hành trình không hợp lệ, vui lòng chọn lại chuyến bay.');
        }

        $seatsNeeded = (int) $pending['adults'] + (int) $pending['children'];

        try {
            $booking = DB::transaction(function () use ($pending, $data, $flightIds, $seatsNeeded) {
                // 1. FINAL check + ATOMIC hold cho MỌI chặng. Khóa theo flight_id tăng dần
                // để hai booking nhiều chặng không khóa chéo nhau (deadlock)
                $seatsByFlight = [];

                foreach ($flightIds->sort()->values() as $flightId) {
                    $candidates = FlightSeat::where('flight_id', $flightId)
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

                    // Thiếu ghế ở BẤT KỲ chặng nào -> rollback cả booking
                    if ($candidates->count() < $seatsNeeded) {
                        throw new \RuntimeException('NOT_ENOUGH_SEATS');
                    }

                    $seatsByFlight[$flightId] = $candidates->sortBy('id')->values();
                }

                $seatIds = collect($seatsByFlight)->flatMap(fn ($seats) => $seats->pluck('id'));

                // Nullify ticket cũ của ghế vừa reclaim (tránh đụng uq_tickets_flightseat)
                Ticket::whereIn('flight_seat_id', $seatIds)->update(['flight_seat_id' => null]);

                FlightSeat::whereIn('id', $seatIds)->update([
                    'status' => 'held',
                    'held_by' => auth()->id(),
                    'held_until' => now()->addMinutes(config('booking.seat_hold_minutes')),
                ]);

                // 2. Booking + hành khách (tạo MỘT lần, dùng chung mọi chặng)
                $booking = Booking::create([
                    'user_id' => auth()->id(),
                    'status' => 'pending',
                    'total_amount' => 0,
                ]);

                $adults = [];
                foreach (array_values($data['adults']) as $row) {
                    $adults[] = $this->createPassenger($booking, $row, 'adult');
                }

                $children = [];
                foreach (array_values($data['children'] ?? []) as $row) {
                    $children[] = $this->createPassenger($booking, $row, 'child');
                }

                $infants = [];
                foreach (array_values($data['infants'] ?? []) as $row) {
                    $infants[] = [
                        'passenger' => $this->createPassenger($booking, $row, 'infant'),
                        'companion' => $adults[(int) $row['companion_adult_index']],
                    ];
                }

                // 3. Mỗi chặng: 1 booking_flight + 1 ticket cho từng hành khách
                $totalAmount = 0;

                foreach ($seatsByFlight as $flightId => $seats) {
                    $bookingFlight = BookingFlight::create([
                        'booking_id' => $booking->id,
                        'flight_id' => $flightId,
                    ]);

                    $seatIndex = 0;
                    $adultFarePrices = []; // giá GHẾ GỐC của người lớn trên CHẶNG NÀY

                    foreach ($adults as $adult) {
                        $seat = $seats[$seatIndex++];
                        $ticket = $this->createTicket($bookingFlight, $adult, $seat->id, $seat->price);
                        $adultFarePrices[$adult->id] = $seat->price;
                        $totalAmount += $ticket->price;
                    }

                    foreach ($children as $child) {
                        $seat = $seats[$seatIndex++];
                        $ticket = $this->createTicket($bookingFlight, $child, $seat->id, $seat->price);
                        $totalAmount += $ticket->price;
                    }

                    foreach ($infants as $infant) {
                        // 10% giá ghế gốc của người lớn đi kèm, cùng chặng
                        $infantPrice = round($adultFarePrices[$infant['companion']->id] * 0.10, 2);
                        $ticket = $this->createTicket(
                            $bookingFlight,
                            $infant['passenger'],
                            null,
                            $infantPrice,
                            $infant['companion']->id
                        );
                        $totalAmount += $ticket->price;
                    }
                }

                $booking->update(['total_amount' => $totalAmount]);

                return $booking;
            }, 3); // thử lại tối đa 3 lần nếu MariaDB hủy transaction vì deadlock
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

    private function createPassenger(Booking $booking, array $row, string $type): Passenger
    {
        return Passenger::create([
            'booking_id' => $booking->id,
            'full_name' => $row['full_name'],
            'passenger_type' => $type,
            'document_number' => $row['document_number'] ?? null,
            'date_of_birth' => $row['date_of_birth'] ?? null,
        ]);
    }

    private function createTicket(BookingFlight $bookingFlight, Passenger $passenger, ?int $seatId, $price, ?int $companionId = null): Ticket
    {
        return Ticket::create([
            'booking_flight_id' => $bookingFlight->id,
            'passenger_id' => $passenger->id,
            'flight_seat_id' => $seatId,
            'companion_adult_passenger_id' => $companionId,
            'price' => $price,
            'ticket_code' => strtoupper(Str::random(8)),
        ]);
    }
}
