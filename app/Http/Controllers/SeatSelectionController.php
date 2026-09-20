<?php

namespace App\Http\Controllers;

use App\Models\FlightSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeatSelectionController extends Controller
{
    public function precheck(Request $request)
    {
        $data = $request->validate([
            'flight_id'     => ['required', 'exists:flights,id'],
            'fare_class_id' => ['required', 'exists:fare_classes,id'],
            'adults'        => ['required', 'integer', 'min:1'],
            'children'      => ['nullable', 'integer', 'min:0'],
            'infants'       => ['nullable', 'integer', 'min:0'],
        ]);

        $seatsNeeded = $data['adults'] + ($data['children'] ?? 0);

        $availableCount = FlightSeat::where('flight_id', $data['flight_id'])
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

        session(['pending_selection' => [
            'flight_id'     => $data['flight_id'],
            'fare_class_id' => $data['fare_class_id'],
            'adults'        => $data['adults'],
            'children'      => $data['children'] ?? 0,
            'infants'       => $data['infants'] ?? 0,
        ]]);

        return redirect()->route('booking.passengers.form');
    }
}