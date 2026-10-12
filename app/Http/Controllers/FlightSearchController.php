<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightSearchRequest;
use App\Models\Airport;
use App\Models\FareClass;
use App\Models\Flight;
use App\Services\BookingExpiryService;
use App\Services\FlightSearchService;
use App\Services\JourneyValidationService;
use Carbon\Carbon;

class FlightSearchController extends Controller
{
    public function __construct(
        private BookingExpiryService $expiryService,
        private FlightSearchService $searchService,
        private JourneyValidationService $journey,
    ) {}

    public function results(FlightSearchRequest $request)
    {
        $this->expiryService->cancelAllExpired();
        $data = $request->validated();

        // Bộ khung hành trình: mọi chặng chưa có flight_id. Tìm lại = làm lại từ đầu
        $pending = [
            'trip_type' => $request->tripType(),
            'fare_class_id' => (int) $data['fare_class_id'],
            'adults' => (int) $data['adults'],
            'children' => (int) ($data['children'] ?? 0),
            'infants' => (int) ($data['infants'] ?? 0),
            'legs' => array_map(fn($leg) => [
                'from' => $leg['from'],
                'to' => $leg['to'],
                'date' => Carbon::parse($leg['date'])->toDateString(), // chuẩn hóa Y-m-d
                'flight_id' => null,
            ], $request->legs()),
        ];

        session(['pending_selection' => $pending]);

        return $this->renderLeg($pending, 0, null);
    }

    // GET /booking/leg/{leg}: chọn chuyến cho chặng thứ $leg (đếm từ 0, chặng 0 đi qua results())
    public function leg(int $leg)
    {
        $pending = session('pending_selection');

        if (! $pending || $leg < 1 || ! isset($pending['legs'][$leg])) {
            return redirect()->route('home')
                ->with('error', 'Phiên tìm kiếm đã hết hạn, vui lòng tìm chuyến bay lại.');
        }

        // Chỉ được mở tới chặng đầu tiên chưa chọn; đã chọn đủ thì được mở lại để chọn lại
        $limit = $this->journey->firstUnchosenLeg($pending) ?? count($pending['legs']) - 1;

        if ($leg > $limit) {
            return redirect($this->journey->legUrl($pending, $limit));
        }

        $previous = Flight::find($pending['legs'][$leg - 1]['flight_id']);

        if (! $previous) {
            return redirect($this->journey->legUrl($pending, 0));
        }

        return $this->renderLeg($pending, $leg, $previous->getRawOriginal('arrival_time'));
    }

    private function renderLeg(array $pending, int $legIndex, ?string $afterUtc)
    {
        $leg = $pending['legs'][$legIndex];

        $departureAirport = Airport::findOrFail($leg['from']);
        $arrivalAirport = Airport::findOrFail($leg['to']);
        $fareClass = FareClass::findOrFail($pending['fare_class_id']);

        $flights = $this->searchService->searchLeg(
            $departureAirport,
            $arrivalAirport,
            $leg['date'],
            (int) $pending['fare_class_id'],
            $afterUtc
        );

        // Các chuyến đã chọn ở những chặng trước, theo thứ tự chặng, để hiển thị tóm tắt
        $byId = Flight::with(['departureAirport', 'arrivalAirport', 'aircraft.airline'])
            ->whereIn('id', collect($pending['legs'])->take($legIndex)->pluck('flight_id'))
            ->get()->keyBy('id');
        $chosenFlights = collect($pending['legs'])->take($legIndex)
            ->map(fn($l) => $byId->get($l['flight_id']))->filter()->values();

        return view('flights.results', [
            'flights' => $flights,
            'departureAirport' => $departureAirport,
            'arrivalAirport' => $arrivalAirport,
            'fareClass' => $fareClass,
            'departureDate' => $leg['date'],
            'adults' => $pending['adults'],
            'children' => $pending['children'],
            'infants' => $pending['infants'],
            'legIndex' => $legIndex,                  // B3 dùng cho "Chặng k/N"
            'totalLegs' => count($pending['legs']),
            'tripType' => $pending['trip_type'],
            'chosenFlights' => $chosenFlights,
            'backUrl' => $legIndex > 0 ? $this->journey->legUrl($pending, $legIndex - 1) : null, // chọn lại chặng trước
        ]);
    }
}
