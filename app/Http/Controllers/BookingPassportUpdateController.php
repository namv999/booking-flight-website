<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePassportUpdateRequest;
use App\Services\PassportUpdateService;

// Khách gửi yêu cầu cập nhật hộ chiếu cho một hành khách của đơn đã đặt
class BookingPassportUpdateController extends Controller
{
    public function store(StorePassportUpdateRequest $request, PassportUpdateService $service, $id, $passenger)
    {
        $booking = $request->user()->bookings()->findOrFail($id);
        $passengerModel = $booking->passengers()->findOrFail($passenger);
        $data = $request->validated();

        try {
            $service->submit($booking, $passengerModel, $request->user(), $data, $data['reason']);
        } catch (\DomainException $e) {
            return redirect()->route('booking-history.show', $booking->id)
                ->with('passport_error', $e->getMessage());
        }

        return redirect()->route('booking-history.show', $booking->id)
            ->with('passport_status', 'Đã gửi yêu cầu cập nhật hộ chiếu. Vui lòng chờ quản trị viên xem xét.');
    }
}
