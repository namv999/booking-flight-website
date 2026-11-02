<?php

namespace App\Http\Controllers;

use App\Http\Requests\PassengerBookingRequest;
use App\Models\BaggageAddon;
use App\Models\FareClass;
use App\Models\Flight;
use App\Services\BookingCreationService;
use App\Services\JourneyValidationService;
use App\Services\PassportService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function create(Request $request, JourneyValidationService $journey, PassportService $passport)
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

        // Thứ tự chặng suy ra từ departure_time
        $flights = Flight::with(['departureAirport', 'arrivalAirport', 'aircraft.airline'])
            ->whereIn('id', $flightIds)
            ->orderBy('departure_time')
            ->get();

        if ($flights->count() !== $flightIds->count()) {
            return redirect()->route('home')
                ->with('error', 'Vui lòng chọn chuyến bay trước khi nhập thông tin hành khách.');
        }

        // Nếu quay lại từ bước chọn ghế, khôi phục dữ liệu đã điền vào old()
        if (empty(old()) && session('pending_passengers')) {
            session()->flashInput(session('pending_passengers'));
        }

        return view('booking.passengers', [
            'flight' => $flights->first(),
            'flights' => $flights,
            'pending' => $pending,
            'isInternational' => $passport->isInternational($pending),
            'countries' => config('countries'),
            'baggageAddons' => BaggageAddon::orderBy('weight_kg')->get(),
            'freeBaggageKg' => FareClass::find($pending['fare_class_id'])?->checked_baggage_kg,
        ]);
    }

    public function next(PassengerBookingRequest $request, JourneyValidationService $journey, BookingCreationService $bookingService)
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

        // 1. Hành trình phải chọn đủ chuyến cho mọi chặng
        $unchosen = $journey->firstUnchosenLeg($pending);
        if ($unchosen !== null) {
            return redirect($journey->legUrl($pending, $unchosen))
                ->with('error', 'Vui lòng chọn đủ chuyến bay cho mọi chặng.');
        }

        // 2. Mỗi chặng phải là một chuyến khác nhau
        $flightIds = collect($pending['legs'])->pluck('flight_id');
        if ($flightIds->unique()->count() !== $flightIds->count()) {
            return redirect()->route('home')
                ->with('error', 'Hành trình không hợp lệ, vui lòng chọn lại chuyến bay.');
        }

        // 3. Kiểm tra tính hợp lệ của chuỗi chuyến bay
        $chosen = Flight::whereIn('id', $flightIds)->get()->keyBy('id');
        foreach ($pending['legs'] as $i => $leg) {
            $previous = $i > 0 ? $chosen->get($pending['legs'][$i - 1]['flight_id']) : null;
            $error = $journey->validateLeg($pending, $i, (int) $leg['flight_id'], $previous, false);

            if ($error) {
                for ($j = $i; $j < count($pending['legs']); $j++) {
                    $pending['legs'][$j]['flight_id'] = null;
                }
                session(['pending_selection' => $pending]);

                return redirect($journey->legUrl($pending, $i))->with('error', $error);
            }
        }

        // Nếu người dùng chọn bỏ qua bước chọn ghế
        if ($request->boolean('skip_seats')) {
            try {
                $booking = $bookingService->create($pending, $data, null, auth()->user());
                session()->forget(['pending_selection', 'pending_passengers', 'pending_seats']);

                return redirect()->route('payment.show', $booking)
                    ->with('status', 'Đặt vé thành công, tiến hành thanh toán.');
            } catch (\RuntimeException $e) {
                if ($e->getMessage() === 'NOT_ENOUGH_SEATS') {
                    return back()->withInput()
                        ->with('error', 'Rất tiếc, ghế vừa hết trong lúc bạn điền thông tin. Vui lòng thử lại.');
                }
                throw $e;
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        session(['pending_passengers' => $data]);

        return redirect()->route('booking.seats.show');
    }

    public function store(PassengerBookingRequest $request, JourneyValidationService $journey, BookingCreationService $bookingService)
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

        // 1. Hành trình phải chọn đủ chuyến cho mọi chặng
        $unchosen = $journey->firstUnchosenLeg($pending);
        if ($unchosen !== null) {
            return redirect($journey->legUrl($pending, $unchosen))
                ->with('error', 'Vui lòng chọn đủ chuyến bay cho mọi chặng.');
        }

        // 2. Mỗi chặng phải là một chuyến khác nhau
        $flightIds = collect($pending['legs'])->pluck('flight_id');
        if ($flightIds->unique()->count() !== $flightIds->count()) {
            return redirect()->route('home')
                ->with('error', 'Hành trình không hợp lệ, vui lòng chọn lại chuyến bay.');
        }

        // 3. Kiểm tra tính hợp lệ của chuỗi chuyến bay
        $chosen = Flight::whereIn('id', $flightIds)->get()->keyBy('id');
        foreach ($pending['legs'] as $i => $leg) {
            $previous = $i > 0 ? $chosen->get($pending['legs'][$i - 1]['flight_id']) : null;
            $error = $journey->validateLeg($pending, $i, (int) $leg['flight_id'], $previous, false);

            if ($error) {
                for ($j = $i; $j < count($pending['legs']); $j++) {
                    $pending['legs'][$j]['flight_id'] = null;
                }
                session(['pending_selection' => $pending]);

                return redirect($journey->legUrl($pending, $i))->with('error', $error);
            }
        }

        try {
            $booking = $bookingService->create($pending, $data, $request->input('seats'), auth()->user());
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'NOT_ENOUGH_SEATS' || $e->getMessage() === 'CHOSEN_SEAT_UNAVAILABLE') {
                return back()->withInput()
                    ->with('error', 'Rất tiếc, ghế vừa hết trong lúc bạn điền thông tin. Vui lòng thử lại.');
            }
            throw $e;
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        session()->forget(['pending_selection', 'pending_passengers', 'pending_seats']);

        return redirect()->route('payment.show', $booking)
            ->with('status', 'Đặt vé thành công, tiến hành thanh toán.');
    }
}
