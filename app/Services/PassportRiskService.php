<?php

namespace App\Services;

use App\Models\Airport;
use App\Models\Booking;
use App\Models\Passenger;
use Carbon\Carbon;

class PassportRiskService
{
    public function __construct(private PassportService $passport) {}

    // level: null | 'yellow' | 'red'
    // refDate = ngày hạ cánh (giờ địa phương) của chặng quốc tế CUỐI cùng
    // red: hết hạn < refDate; yellow: refDate <= hết hạn < refDate + 6 tháng
    public function assess(Booking $booking): array
    {
        $booking->loadMissing('bookingFlights.flight');
        $flights = $booking->bookingFlights->pluck('flight')->filter();

        // withTrashed: sân bay bị soft delete sau khi đặt vé vẫn phải tính đúng quốc gia
        $airports = Airport::withTrashed()
            ->whereIn('id', $flights->flatMap(fn ($f) => [$f->departure_airport_id, $f->arrival_airport_id])->unique())
            ->get()->keyBy('id');

        $refDate = null;

        foreach ($flights as $flight) {
            $from = $airports->get($flight->departure_airport_id);
            $to = $airports->get($flight->arrival_airport_id);

            if (! $this->passport->differentCountry($from, $to)) {
                continue;
            }

            $arrival = Carbon::parse($flight->getRawOriginal('arrival_time'), 'UTC')
                ->setTimezone($to->timezone)
                ->toDateString();

            if ($refDate === null || $arrival > $refDate) { // so chuỗi Y-m-d
                $refDate = $arrival;
            }
        }

        if ($refDate === null) {
            return ['level' => null, 'ref_date' => null, 'items' => []];
        }

        $limit = Carbon::parse($refDate)->addMonthsNoOverflow(6)->toDateString();
        $items = [];

        $passengers = Passenger::where('booking_id', $booking->id)
            ->whereNotNull('document_expiry_date')
            ->orderBy('id')
            ->get();

        foreach ($passengers as $passenger) {
            $expiry = $passenger->document_expiry_date->toDateString();
            $level = null;

            if ($expiry < $refDate) {
                $level = 'red';
            } elseif ($expiry < $limit) {
                $level = 'yellow';
            }

            if ($level) {
                $items[] = [
                    'passenger' => $passenger,
                    'level' => $level,
                    'expiry_date' => $expiry,
                    // mốc "hết hạn trừ 6 tháng": ngày bay muộn nhất để hộ chiếu còn đủ 6 tháng
                    'six_month_date' => Carbon::parse($expiry)->subMonthsNoOverflow(6)->toDateString(),
                ];
            }
        }

        $overall = collect($items)->contains('level', 'red') ? 'red' : (count($items) ? 'yellow' : null);

        return ['level' => $overall, 'ref_date' => $refDate, 'items' => $items];
    }
}
