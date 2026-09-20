<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\FlightSeat;
use Illuminate\Support\Facades\DB;

class BookingExpiryService
{
    /**
     * KIỂM TRA và HỦY 1 booking cụ thể nếu đã quá hạn thanh toán.
     * Dùng lockForUpdate() để tránh đụng độ với request khác đang xử lý cùng booking.
     * Trả về true nếu vừa hủy, false nếu không cần hủy (chưa hết hạn / không còn pending).
     */
    public function cancelIfExpired(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== 'pending') {
                return false;
            }

            $expiresAt = $locked->created_at->addMinutes(config('booking.seat_hold_minutes'));
            if (now()->lessThan($expiresAt)) {
                return false;
            }

            $this->releaseSeatsAndCancel($locked);

            return true;
        });
    }

    /**
     * Quét toàn bộ booking pending quá hạn và hủy hàng loạt.
     * Mỗi booking xử lý trong transaction riêng (không ôm chung 1 transaction lớn,
     * để 1 booking lỗi không kéo rollback các booking khác.
     * Trả về số lượng booking đã hủy.
     */
    public function cancelAllExpired(): int
    {
        $expireMinutes = config('booking.seat_hold_minutes');

        $expiredIds = Booking::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes($expireMinutes))
            ->pluck('id');

        $cancelledCount = 0;

        foreach ($expiredIds as $id) {
            $booking = Booking::find($id);
            if ($booking && $this->cancelIfExpired($booking)) {
                $cancelledCount++;
            }
        }

        return $cancelledCount;
    }

    private function releaseSeatsAndCancel(Booking $booking): void
    {
        $tickets = $booking->bookingFlights()
            ->with('tickets')
            ->get()
            ->pluck('tickets')
            ->flatten();

        $flightSeatIds = $tickets->pluck('flight_seat_id')->filter()->values();

        if ($flightSeatIds->isNotEmpty()) {
            // Gỡ liên kết ghế khỏi ticket cũ TRƯỚC — tránh vi phạm UNIQUE
            // khi ghế này được đặt lại bởi booking khác sau này
            \App\Models\Ticket::whereIn('flight_seat_id', $flightSeatIds)
                ->update(['flight_seat_id' => null]);

            FlightSeat::whereIn('id', $flightSeatIds)
                ->update([
                    'status' => 'available',
                    'held_by' => null,
                    'held_until' => null,
                ]);
        }

        $booking->update(['status' => 'cancelled']);
    }
}