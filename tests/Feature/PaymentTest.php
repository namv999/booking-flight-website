<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Passenger;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingBookingWithSeat(User $user): array
    {
        $flight = Flight::factory()->create();

        $seat = FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'status' => 'held',
            'held_by' => $user->id,
            'held_until' => now()->addMinutes(config('booking.seat_hold_minutes')),
            'price' => 1500000,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 1500000,
        ]);

        $bookingFlight = BookingFlight::factory()->create([
            'booking_id' => $booking->id,
            'flight_id' => $flight->id,
        ]);

        $passenger = Passenger::factory()->create(['booking_id' => $booking->id]);

        Ticket::factory()->create([
            'booking_flight_id' => $bookingFlight->id,
            'passenger_id' => $passenger->id,
            'flight_seat_id' => $seat->id,
            'price' => 1500000,
        ]);

        return compact('booking', 'seat');
    }

    public function test_successful_payment_marks_booking_paid_and_seat_booked(): void
    {
        $user = User::factory()->create();
        $data = $this->makePendingBookingWithSeat($user);

        $response = $this->actingAs($user)->post(route('payment.store', $data['booking']), [
            'method' => 'the_tin_dung',
            'simulate_result' => 'success',
        ]);

        $response->assertRedirect(route('booking-history.show', $data['booking']));
        $response->assertSessionHas('status');

        $data['booking']->refresh();
        $data['seat']->refresh();

        $this->assertSame('paid', $data['booking']->status);

        $this->assertDatabaseCount('payments', 1);
        $payment = Payment::first();
        $this->assertSame('success', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame($data['booking']->id, $payment->booking_id);

        // Fix vừa áp: ghế phải chuyển booked, không còn held
        $this->assertSame('booked', $data['seat']->status);
        $this->assertNull($data['seat']->held_by);
        $this->assertNull($data['seat']->held_until);
    }

    public function test_failed_payment_keeps_booking_pending_and_seat_unchanged(): void
    {
        $user = User::factory()->create();
        $data = $this->makePendingBookingWithSeat($user);

        $response = $this->actingAs($user)->post(route('payment.store', $data['booking']), [
            'method' => 'vi_dien_tu',
            'simulate_result' => 'failed',
        ]);

        $response->assertRedirect(route('payment.show', $data['booking']));
        $response->assertSessionHas('error');

        $data['booking']->refresh();
        $data['seat']->refresh();

        $this->assertSame('pending', $data['booking']->status);

        $this->assertDatabaseCount('payments', 1);
        $payment = Payment::first();
        $this->assertSame('failed', $payment->status);
        $this->assertNull($payment->paid_at);

        // Ghế KHÔNG bị đổi khi thanh toán fail - vẫn held như cũ
        $this->assertSame('held', $data['seat']->status);
        $this->assertSame($user->id, $data['seat']->held_by);
        $this->assertNotNull($data['seat']->held_until);
    }

    public function test_retry_after_failed_updates_same_payment_row_not_creating_new_one(): void
    {
        $user = User::factory()->create();
        $data = $this->makePendingBookingWithSeat($user);

        // Lần 1: failed
        $this->actingAs($user)->post(route('payment.store', $data['booking']), [
            'method' => 'the_tin_dung',
            'simulate_result' => 'failed',
        ]);

        $this->assertDatabaseCount('payments', 1);
        $firstPaymentId = Payment::first()->id;

        // Lần 2: retry với success
        $response = $this->actingAs($user)->post(route('payment.store', $data['booking']), [
            'method' => 'vi_dien_tu',
            'simulate_result' => 'success',
        ]);

        $response->assertRedirect(route('booking-history.show', $data['booking']));

        // Vẫn chỉ 1 row payment - updateOrCreate đè lên, không insert thêm
        $this->assertDatabaseCount('payments', 1);
        $payment = Payment::first();
        $this->assertSame($firstPaymentId, $payment->id);
        $this->assertSame('success', $payment->status);
        $this->assertSame('vi_dien_tu', $payment->method);
        $this->assertNotNull($payment->paid_at);

        $data['booking']->refresh();
        $this->assertSame('paid', $data['booking']->status);
    }

    public function test_show_aborts_403_when_booking_belongs_to_another_user(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $data = $this->makePendingBookingWithSeat($owner);

        $response = $this->actingAs($stranger)->get(route('payment.show', $data['booking']));

        $response->assertForbidden();
    }

    public function test_show_redirects_to_history_when_booking_already_cancelled(): void
    {
        $user = User::factory()->create();
        $data = $this->makePendingBookingWithSeat($user);
        $data['booking']->update(['status' => 'cancelled']);

        $response = $this->actingAs($user)->get(route('payment.show', $data['booking']));

        $response->assertRedirect(route('booking-history.show', $data['booking']));
        $response->assertSessionHas('error');
    }

    public function test_show_redirects_to_history_when_booking_already_paid(): void
    {
        $user = User::factory()->create();
        $data = $this->makePendingBookingWithSeat($user);
        $data['booking']->update(['status' => 'paid']);

        $response = $this->actingAs($user)->get(route('payment.show', $data['booking']));

        $response->assertRedirect(route('booking-history.show', $data['booking']));
        $response->assertSessionHas('status');
    }

    public function test_show_auto_cancels_and_redirects_when_booking_just_expired(): void
    {
        $user = User::factory()->create();
        $hold = config('booking.seat_hold_minutes');
        $data = $this->makePendingBookingWithSeat($user);

        // Đẩy created_at về quá khứ để booking đã hết hạn ngay tại thời điểm load trang
        $data['booking']->forceFill(['created_at' => now()->subMinutes($hold + 5)])->save();

        $response = $this->actingAs($user)->get(route('payment.show', $data['booking']));

        $response->assertRedirect(route('booking-history.show', $data['booking']));
        $response->assertSessionHas('error');

        $data['booking']->refresh();
        $data['seat']->refresh();

        // cancelIfExpired() trong show() phải đã kích hoạt, không chỉ redirect suông
        $this->assertSame('cancelled', $data['booking']->status);
        $this->assertSame('available', $data['seat']->status);
    }

    public function test_store_aborts_403_when_booking_belongs_to_another_user(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $data = $this->makePendingBookingWithSeat($owner);

        $response = $this->actingAs($stranger)->post(route('payment.store', $data['booking']), [
            'method' => 'the_tin_dung',
            'simulate_result' => 'success',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_store_redirects_when_booking_already_paid(): void
    {
        $user = User::factory()->create();
        $data = $this->makePendingBookingWithSeat($user);
        $data['booking']->update(['status' => 'paid']);

        // Payment "success" cũ đã tồn tại từ lần thanh toán trước
        Payment::factory()->create(['booking_id' => $data['booking']->id]);

        $response = $this->actingAs($user)->post(route('payment.store', $data['booking']), [
            'method' => 'the_tin_dung',
            'simulate_result' => 'success',
        ]);

        $response->assertRedirect(route('booking-history.show', $data['booking']));
        $response->assertSessionHas('status');

        // Không tạo thêm payment nào - vẫn đúng 1 row cũ, không bị đổi
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_store_auto_cancels_when_booking_expires_right_before_submit(): void
    {
        $user = User::factory()->create();
        $hold = config('booking.seat_hold_minutes');
        $data = $this->makePendingBookingWithSeat($user);

        $data['booking']->forceFill(['created_at' => now()->subMinutes($hold + 5)])->save();

        $response = $this->actingAs($user)->post(route('payment.store', $data['booking']), [
            'method' => 'the_tin_dung',
            'simulate_result' => 'success',
        ]);

        $response->assertRedirect(route('booking-history.show', $data['booking']));
        $response->assertSessionHas('error');

        $data['booking']->refresh();
        $this->assertSame('cancelled', $data['booking']->status);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_store_validation_fails_on_invalid_method(): void
    {
        $user = User::factory()->create();
        $data = $this->makePendingBookingWithSeat($user);

        $response = $this->actingAs($user)->post(route('payment.store', $data['booking']), [
            'method' => 'bitcoin', // không nằm trong enum cho phép
            'simulate_result' => 'success',
        ]);

        $response->assertSessionHasErrors('method');
        $this->assertDatabaseCount('payments', 0);
    }
}
