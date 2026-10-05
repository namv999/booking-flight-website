<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\FlightSeat;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SeatSelectionController extends Controller
{
    public function precheck(Request $request)
    {
        $data = $request->validate([
            'flight_id' => ['required', 'exists:flights,id'],
            'fare_class_id' => ['required', 'exists:fare_classes,id'],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
            'infants' => ['nullable', 'integer', 'min:0'],
        ]);

        // Chuyến phải còn hiệu lực. Kiểm tra bằng SQL với now('UTC') vì departure_time lưu UTC
        $flight = Flight::with('departureAirport')
            ->whereKey($data['flight_id'])
            ->whereIn('status', ['scheduled', 'delayed'])
            ->where('departure_time', '>', now('UTC'))
            ->first();

        if (! $flight) {
            return back()->with('error', 'Chuyến bay này không còn khả dụng, vui lòng chọn chuyến khác.');
        }

        $seatsNeeded = $data['adults'] + ($data['children'] ?? 0);

        $availableCount = FlightSeat::where('flight_id', $flight->id)
            ->where('fare_class_id', $data['fare_class_id'])
            ->where(function ($q) {
                $q->where('status', 'available')
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'held')
                            ->where('held_until', '<', now());
                    });
            })
            ->count();

        if ($availableCount < $seatsNeeded) {
            return back()->with('error', 'Không đủ ghế trống cho hạng vé này, vui lòng thử lại hoặc chọn chuyến khác.');
        }

        // Ngày local của chặng: đọc giá trị UTC thô từ DB rồi đổi sang timezone sân bay đi
        // (getRawOriginal để không phụ thuộc cast timezone của model)
        $localDate = Carbon::parse($flight->getRawOriginal('departure_time'), 'UTC')
            ->setTimezone($flight->departureAirport->timezone)
            ->toDateString();

        session(['pending_selection' => [
            'trip_type' => 'one-way',
            'fare_class_id' => $data['fare_class_id'],
            'adults' => $data['adults'],
            'children' => $data['children'] ?? 0,
            'infants' => $data['infants'] ?? 0,
            'legs' => [[
                'from' => $flight->departure_airport_id,
                'to' => $flight->arrival_airport_id,
                'date' => $localDate,
                'flight_id' => $flight->id,
            ]],
        ]]);

        return redirect()->route('booking.passengers.form');
    }
}
