<?php

namespace App\Http\Controllers;

use App\Models\FlightSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeatSelectionController extends Controller
{
    public function hold(Request $request)
    {
        $data = $request->validate([
            'flight_id'     => ['required', 'exists:flights,id'],
            'fare_class_id' => ['required', 'exists:fare_classes,id'],
            'adults'        => ['required', 'integer', 'min:1'],
            'children'      => ['nullable', 'integer', 'min:0'],
            'infants'       => ['nullable', 'integer', 'min:0'],
        ]);

        $seatsNeeded = $data['adults'] + ($data['children'] ?? 0); // infant không tính ghế riêng

        try {
            $heldSeatIds = DB::transaction(function () use ($data, $seatsNeeded) {
                $candidates = FlightSeat::where('flight_id', $data['flight_id'])
                    ->where('fare_class_id', $data['fare_class_id'])
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

                $ids = $candidates->pluck('id');

                FlightSeat::whereIn('id', $ids)->update([
                    'status'     => 'held',
                    'held_by'    => auth()->id(),
                    'held_until' => now()->addMinutes(10),
                ]);

                return $ids;
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'NOT_ENOUGH_SEATS') {
                return back()->with('error', 'Không đủ ghế trống cho hạng vé này, vui lòng thử lại hoặc chọn chuyến khác.');
            }
            throw $e;
        }

        session(['pending_hold' => [
            'flight_id'       => $data['flight_id'],
            'fare_class_id'   => $data['fare_class_id'],
            'flight_seat_ids' => $heldSeatIds->toArray(),
            'adults'          => $data['adults'],
            'children'        => $data['children'] ?? 0,
            'infants'         => $data['infants'] ?? 0,
            'expires_at'      => now()->addMinutes(10)->toDateTimeString(),
        ]]);

        // NOTE(Namv): route booking.create chưa build, tạm redirect về results
        return redirect()->route('flights.search.form')->with('status', 'Đã giữ ghế 10 phút, tiếp tục nhập thông tin hành khách.');
    }
}