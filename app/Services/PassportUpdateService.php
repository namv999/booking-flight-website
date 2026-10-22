<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Passenger;
use App\Models\PassportUpdateRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Quy trình khách gửi yêu cầu cập nhật hộ chiếu sau khi đặt vé, admin duyệt hoặc từ chối.
// Lỗi nghiệp vụ ném DomainException với thông báo tiếng Việt để controller hiển thị cho người dùng.
class PassportUpdateService
{
    private const DATE_FIELDS = ['date_of_birth', 'document_issue_date', 'document_expiry_date'];

    public function __construct(private PassportService $passport) {}

    // Lý do ĐƠN này chưa gửi được yêu cầu (null = được phép)
    public function bookingError(Booking $booking): ?string
    {
        if ($booking->status !== 'paid') {
            return 'Chỉ đơn đã thanh toán mới có thể gửi yêu cầu cập nhật hộ chiếu.';
        }

        if (! $this->passport->isInternationalBooking($booking)) {
            return 'Đơn nội địa không yêu cầu hộ chiếu nên không có gì để cập nhật.';
        }

        if ($this->firstFlightDeparted($booking)) {
            return 'Chuyến bay đầu tiên đã cất cánh, không thể gửi yêu cầu cập nhật hộ chiếu.';
        }

        return null;
    }

    // Lý do HÀNH KHÁCH này chưa gửi được yêu cầu (null = được phép)
    public function passengerError(Booking $booking, Passenger $passenger): ?string
    {
        if ($error = $this->bookingError($booking)) {
            return $error;
        }

        if ($passenger->passportUpdateRequests()->pending()->exists()) {
            return 'Hành khách này đã có một yêu cầu đang chờ duyệt.';
        }

        return null;
    }

    // Chuyến đầu tiên (theo departure_time) đã cất cánh chưa. Không có chuyến nào thì coi như đã qua.
    public function firstFlightDeparted(Booking $booking): bool
    {
        $booking->loadMissing('bookingFlights.flight');
        $first = $booking->ordered_legs->first()?->flight;

        if (! $first) {
            return true;
        }

        return Carbon::parse($first->getRawOriginal('departure_time'), 'UTC')->lte(now('UTC'));
    }

    // Khách gửi yêu cầu. $data là dữ liệu đã validate và chuẩn hóa (8 trường hộ chiếu).
    public function submit(Booking $booking, Passenger $passenger, User $user, array $data, string $reason): PassportUpdateRequest
    {
        return DB::transaction(function () use ($booking, $passenger, $user, $data, $reason) {
            // Khóa hàng hành khách để hai yêu cầu gửi cùng lúc không cùng vượt qua kiểm tra "chưa có yêu cầu chờ"
            Passenger::whereKey($passenger->id)->lockForUpdate()->first();

            if ($error = $this->passengerError($booking, $passenger)) {
                throw new \DomainException($error);
            }

            return PassportUpdateRequest::create([
                'booking_id' => $booking->id,
                'passenger_id' => $passenger->id,
                'user_id' => $user->id,
                'new_data' => $this->payload($data),
                'reason' => $reason,
                'status' => 'pending',
            ]);
        });
    }

    // Admin duyệt: trong MỘT transaction chụp dữ liệu cũ vào old_data rồi cập nhật bảng passengers.
    // passengers dùng chung cho mọi chặng nên vé của mọi chặng tự cập nhật theo.
    public function approve(PassportUpdateRequest $request, User $admin, ?string $note = null): void
    {
        DB::transaction(function () use ($request, $admin, $note) {
            $locked = $this->lockPending($request);

            if (Booking::whereKey($locked->booking_id)->value('status') !== 'paid') {
                throw new \DomainException('Đơn không còn ở trạng thái đã thanh toán nên không thể duyệt yêu cầu này.');
            }

            $passenger = Passenger::whereKey($locked->passenger_id)->lockForUpdate()->firstOrFail();
            $old = $this->snapshot($passenger);

            $passenger->update($locked->new_data + ['document_type' => 'passport']);

            $locked->update([
                'status' => 'approved',
                'old_data' => $old,
                'admin_id' => $admin->id,
                'admin_note' => $note,
                'resolved_at' => now(),
            ]);
        });
    }

    // Admin từ chối: không đụng tới bảng passengers
    public function reject(PassportUpdateRequest $request, User $admin, string $note): void
    {
        DB::transaction(function () use ($request, $admin, $note) {
            $this->lockPending($request)->update([
                'status' => 'rejected',
                'admin_id' => $admin->id,
                'admin_note' => $note,
                'resolved_at' => now(),
            ]);
        });
    }

    // Khóa và đọc lại yêu cầu từ DB: hai admin bấm cùng lúc thì chỉ một người xử lý được
    private function lockPending(PassportUpdateRequest $request): PassportUpdateRequest
    {
        $locked = PassportUpdateRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

        if (! $locked->isPending()) {
            throw new \DomainException('Yêu cầu này đã được xử lý trước đó.');
        }

        return $locked;
    }

    // Chỉ giữ 8 trường hộ chiếu; ngày về dạng Y-m-d
    private function payload(array $data): array
    {
        $out = [];

        foreach (PassportUpdateRequest::FIELDS as $field) {
            $value = $data[$field] ?? null;
            $out[$field] = ($value && in_array($field, self::DATE_FIELDS, true))
                ? Carbon::parse($value)->toDateString()
                : $value;
        }

        return $out;
    }

    // Dữ liệu hộ chiếu hiện tại của hành khách, cùng dạng với new_data
    private function snapshot(Passenger $passenger): array
    {
        $out = [];

        foreach (PassportUpdateRequest::FIELDS as $field) {
            $value = $passenger->{$field};
            $out[$field] = $value instanceof Carbon ? $value->toDateString() : $value;
        }

        return $out;
    }
}
