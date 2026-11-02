<?php

namespace App\Http\Controllers;

use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Services\BookingCreationService;
use App\Services\JourneyValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeatSelectionController extends Controller
{
    public function __construct(private JourneyValidationService $journey) {}

    // Client chỉ gửi flight_id + leg; hạng vé và số người lấy từ session
    public function precheck(Request $request)
    {
        $data = $request->validate([
            'flight_id' => ['required', 'integer', 'exists:flights,id'],
            'leg' => ['required', 'integer', 'min:0'],
        ]);

        $pending = session('pending_selection');
        $legIndex = (int) $data['leg'];

        if (! $pending || ! isset($pending['legs'][$legIndex])) {
            return redirect()->route('home')
                ->with('error', 'Phiên tìm kiếm đã hết hạn, vui lòng tìm chuyến bay lại.');
        }

        // Không được nhảy cóc: các chặng trước phải chọn xong
        $firstUnchosen = $this->journey->firstUnchosenLeg($pending);
        if ($firstUnchosen !== null && $legIndex > $firstUnchosen) {
            return redirect($this->journey->legUrl($pending, $firstUnchosen));
        }

        $previous = $legIndex > 0 ? Flight::find($pending['legs'][$legIndex - 1]['flight_id']) : null;

        $error = $this->journey->validateLeg($pending, $legIndex, (int) $data['flight_id'], $previous);

        if ($error) {
            return back()->with('error', $error);
        }

        // Lưu chuyến cho chặng này, xóa lựa chọn của các chặng sau (chọn lại chặng trước thì chặng sau phải chọn lại)
        $pending['legs'][$legIndex]['flight_id'] = (int) $data['flight_id'];
        for ($i = $legIndex + 1; $i < count($pending['legs']); $i++) {
            $pending['legs'][$i]['flight_id'] = null;
        }
        session(['pending_selection' => $pending]);

        $next = $this->journey->firstUnchosenLeg($pending);

        return $next === null
            ? redirect()->route('booking.passengers.form')
            : redirect()->route('booking.leg', $next);
    }

    /**
     * Hiển thị trang chọn ghế (Bước 4)
     */
    public function show(): View|RedirectResponse
    {
        $pending = session('pending_selection');
        $passengerData = session('pending_passengers');

        if (! $pending) {
            return redirect()->route('home')
                ->with('error', 'Vui lòng chọn chuyến bay trước khi chọn ghế.');
        }

        if (! $passengerData) {
            return redirect()->route('booking.passengers.form')
                ->with('error', 'Vui lòng hoàn thành thông tin hành khách trước khi chọn ghế.');
        }

        $flightIds = collect($pending['legs'])->pluck('flight_id');
        $flights = Flight::with(['departureAirport', 'arrivalAirport', 'aircraft.airline'])
            ->whereIn('id', $flightIds)
            ->orderBy('departure_time')
            ->get();

        if ($flights->count() !== $flightIds->count()) {
            return redirect()->route('home')
                ->with('error', 'Chuyến bay đã chọn không còn khả dụng.');
        }

        $fareClass = FareClass::findOrFail($pending['fare_class_id']);

        // Xây dựng danh sách hành khách theo hợp đồng của seat-map.js
        $passengers = [];
        foreach ($passengerData['adults'] as $i => $adult) {
            $passengers[] = [
                'group' => 'adults',
                'index' => $i,
                'name' => $adult['full_name'],
                'type_label' => 'Người lớn',
            ];
        }

        foreach ($passengerData['children'] ?? [] as $i => $child) {
            $passengers[] = [
                'group' => 'children',
                'index' => $i,
                'name' => $child['full_name'],
                'type_label' => 'Trẻ em',
            ];
        }

        $infants = [];
        foreach ($passengerData['infants'] ?? [] as $infant) {
            $infants[] = $infant['full_name'];
        }

        $mapUrls = [];
        foreach ($flights->values() as $i => $flight) {
            $mapUrls[$i] = route('booking.seats.map', ['leg' => $i]);
        }

        $oldSeats = old('seats', session('pending_seats', []));

        return view('booking.seats', [
            'flights' => $flights,
            'passengers' => $passengers,
            'infants' => $infants,
            'fareClass' => $fareClass,
            'mapUrls' => $mapUrls,
            'oldSeats' => $oldSeats,
        ]);
    }

    /**
     * Cung cấp JSON sơ đồ ghế của chặng theo chỉ số leg
     */
    public function map(int $leg): JsonResponse
    {
        $pending = session('pending_selection');

        if (! $pending || ! isset($pending['legs'][$leg])) {
            return response()->json(['error' => 'Phiên làm việc không hợp lệ hoặc đã hết hạn.'], 404);
        }

        $flightIds = collect($pending['legs'])->pluck('flight_id');
        $flights = Flight::with(['aircraft.seats'])
            ->whereIn('id', $flightIds)
            ->orderBy('departure_time')
            ->get();

        $flight = $flights->values()->get($leg);

        if (! $flight) {
            return response()->json(['error' => 'Không tìm thấy chuyến bay cho chặng này.'], 404);
        }

        $fareClass = FareClass::find($pending['fare_class_id']);
        $fee = (float) ($fareClass?->seat_selection_fee ?? 0);

        $flightSeats = FlightSeat::with('seat')
            ->where('flight_id', $flight->id)
            ->get();

        $now = now();
        $groupedRows = [];

        foreach ($flightSeats as $fs) {
            $seat = $fs->seat;
            if (! $seat) {
                continue;
            }

            preg_match('/^(\d+)([A-Za-z]+)$/', $seat->seat_number, $matches);
            $rowNum = (int) ($matches[1] ?? 1);
            $colLetter = strtoupper($matches[2] ?? 'A');

            // Lazy expiry: nếu held đã hết hạn thì xem như available
            $isHeldExpired = ($fs->status === 'held' && $fs->held_until && $fs->held_until < $now);
            $effectiveStatus = $isHeldExpired ? 'available' : $fs->status;

            // Kiểm tra khả năng chọn ghế
            $isMatchingFareClass = ((int) $fs->fare_class_id === (int) $pending['fare_class_id']);
            $isAvailable = ($effectiveStatus === 'available');
            $selectable = ($isMatchingFareClass && $isAvailable);

            // Aisle sau cột C (sơ đồ tiêu chuẩn 6 ghế: ABC DEF)
            $aisleAfter = ($colLetter === 'C');

            $seatData = [
                'flight_seat_id' => $fs->id,
                'seat_number' => $seat->seat_number,
                'seat_class' => $seat->seat_class,
                'position' => $seat->position,
                'col' => $colLetter,
                'aisle_after' => $aisleAfter,
                'status' => $effectiveStatus,
                'selectable' => $selectable,
                'fee' => $fee,
            ];

            $groupedRows[$rowNum][] = $seatData;
        }

        ksort($groupedRows);

        $rows = [];
        foreach ($groupedRows as $rowNum => $seats) {
            usort($seats, fn ($a, $b) => strcmp($a['col'], $b['col']));
            $rows[] = [
                'row' => $rowNum,
                'seats' => $seats,
            ];
        }

        return response()->json(['rows' => $rows]);
    }

    /**
     * Nhận lựa chọn ghế và hoàn tất đặt vé
     */
    public function store(Request $request, BookingCreationService $bookingService): RedirectResponse
    {
        $pending = session('pending_selection');
        $passengerData = session('pending_passengers');

        if (! $pending) {
            return redirect()->route('home')
                ->with('error', 'Phiên đặt vé đã hết hạn. Vui lòng tìm chuyến bay lại.');
        }

        if (! $passengerData) {
            return redirect()->route('booking.passengers.form')
                ->with('error', 'Vui lòng hoàn thành thông tin hành khách trước khi chọn ghế.');
        }

        $selectedSeats = $request->input('seats', []);

        try {
            $booking = $bookingService->create($pending, $passengerData, $selectedSeats, auth()->user());
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'CHOSEN_SEAT_UNAVAILABLE' || $e->getMessage() === 'NOT_ENOUGH_SEATS') {
                return back()->withInput()->with('error', 'Một số ghế bạn chọn không còn khả dụng hoặc vừa được đặt. Vui lòng chọn lại.');
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
