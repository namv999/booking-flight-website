<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\NotificationBroadcast;
use App\Models\PassportUpdateRequest;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function passportSubmitted(PassportUpdateRequest $request): void
    {
        rescue(function () use ($request) {
            $request->loadMissing(['user', 'passenger']);

            Notification::send($this->admins(), new AppNotification(
                AppNotification::PASSPORT_SUBMITTED,
                'Yêu cầu cập nhật hộ chiếu mới',
                sprintf(
                    '%s gửi yêu cầu cập nhật hộ chiếu cho hành khách %s (đơn #%d).',
                    $request->user?->name ?? 'Khách hàng',
                    $request->passenger?->full_name ?? '-',
                    $request->booking_id
                ),
                route('admin.bookings.show', $request->booking_id, false).'#passport-update-requests'
            ));
        });
    }

    public function passportResolved(PassportUpdateRequest $request): void
    {
        rescue(function () use ($request) {
            $user = User::find($request->user_id);

            if (! $user) {
                return;
            }

            $request->loadMissing('passenger');
            $name = $request->passenger?->full_name ?? '-';
            $approved = $request->status === 'approved';

            $user->notify(new AppNotification(
                $approved ? AppNotification::PASSPORT_APPROVED : AppNotification::PASSPORT_REJECTED,
                $approved ? 'Yêu cầu hộ chiếu đã được duyệt' : 'Yêu cầu hộ chiếu bị từ chối',
                $approved
                    ? sprintf('Thông tin hộ chiếu của %s (đơn #%d) đã được cập nhật.', $name, $request->booking_id)
                    : sprintf('Yêu cầu của %s (đơn #%d) bị từ chối. Lý do: %s', $name, $request->booking_id, $request->admin_note ?? 'Không có ghi chú.'),
                route('booking-history.show', $request->booking_id, false).'#passport-update'
            ));
        });
    }

    public function bookingPaid(Booking $booking): void
    {
        rescue(function () use ($booking) {
            $booking->loadMissing('user');
            $amount = number_format((float) $booking->total_amount, 0, ',', '.').' VND';

            $booking->user?->notify(new AppNotification(
                AppNotification::BOOKING_PAID,
                'Thanh toán thành công',
                sprintf('Đơn #%d đã thanh toán %s. Vé của bạn đã sẵn sàng trong lịch sử đặt vé.', $booking->id, $amount),
                route('booking-history.show', $booking->id, false)
            ));

            Notification::send($this->admins(), new AppNotification(
                AppNotification::ORDER_PAID,
                'Đơn đặt vé mới đã thanh toán',
                sprintf('%s vừa thanh toán đơn #%d (%s).', $booking->user?->name ?? 'Khách hàng', $booking->id, $amount),
                route('admin.bookings.show', $booking->id, false)
            ));
        });
    }

    public function broadcast(?User $admin, string $type, string $title, string $message, ?string $url): NotificationBroadcast
    {
        return DB::transaction(function () use ($admin, $type, $title, $message, $url) {
            $count = 0;

            User::where('role', 'user')->chunkById(200, function ($users) use (&$count, $type, $title, $message, $url) {
                Notification::send($users, new AppNotification($type, $title, $message, $url));
                $count += $users->count();
            });

            return NotificationBroadcast::create([
                'admin_id' => $admin?->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'url' => $url,
                'recipient_count' => $count,
            ]);
        });
    }

    private function admins()
    {
        return User::where('role', 'admin')->get();
    }
}
