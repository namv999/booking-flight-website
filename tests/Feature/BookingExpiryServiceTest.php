<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Passenger;
use App\Models\Ticket;
use App\Models\User;
use App\Services\BookingExpiryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingExpiryServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeBookingWithSeat(int $createdMinutesAgo, string $status = 'pending'): array
    {
        $user = User::factory()->create();
        $flight = Flight::factory()->create();

        $seat = FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'status' => 'held',
            'held_by' => $user->id,
            'held_until' => now()->addMinutes(config('booking.seat_hold_minutes')),
        ]);

        $booking = Booking::factory()
            ->createdMinutesAgo($createdMinutesAgo)
            ->create(['user_id' => $user->id, 'status' => $status]);

        $bookingFlight = BookingFlight::factory()->create([
            'booking_id' => $booking->id,
            'flight_id' => $flight->id,
        ]);

        $passenger = Passenger::factory()->create(['booking_id' => $booking->id]);

        $ticket = Ticket::factory()->create([
            'booking_flight_id' => $bookingFlight->id,
            'passenger_id' => $passenger->id,
            'flight_seat_id' => $seat->id,
        ]);

        return compact('booking', 'seat', 'ticket');
    }

    public function test_does_not_cancel_pending_booking_not_yet_expired(): void
    {
        $hold = config('booking.seat_hold_minutes');
        $data = $this->makeBookingWithSeat(createdMinutesAgo: $hold - 5); // chưa quá hạn

        $result = (new BookingExpiryService())->cancelIfExpired($data['booking']);

        $this->assertFalse($result);

        $data['booking']->refresh();
        $data['seat']->refresh();
        $data['ticket']->refresh();

        $this->assertSame('pending', $data['booking']->status);
        $this->assertSame('held', $data['seat']->status);
        $this->assertNotNull($data['ticket']->flight_seat_id);
    }

    public function test_cancels_pending_booking_and_releases_seat_when_expired(): void
    {
        $hold = config('booking.seat_hold_minutes');
        $data = $this->makeBookingWithSeat(createdMinutesAgo: $hold + 5); // đã quá hạn

        $result = (new BookingExpiryService())->cancelIfExpired($data['booking']);

        $this->assertTrue($result);

        $data['booking']->refresh();
        $data['seat']->refresh();
        $data['ticket']->refresh();

        $this->assertSame('cancelled', $data['booking']->status);

        $this->assertSame('available', $data['seat']->status);
        $this->assertNull($data['seat']->held_by);
        $this->assertNull($data['seat']->held_until);

        // Ticket cũ phải bị nullify flight_seat_id TRƯỚC khi ghế available trở lại
        // -> tránh vi phạm uq_tickets_flightseat khi ghế được đặt lại sau này
        $this->assertNull($data['ticket']->flight_seat_id);
    }
    public function test_does_not_cancel_paid_or_cancelled_booking_even_if_past_threshold(): void
    {
        $hold = config('booking.seat_hold_minutes');

        $paidData = $this->makeBookingWithSeat(createdMinutesAgo: $hold + 30, status: 'paid');
        $cancelledData = $this->makeBookingWithSeat(createdMinutesAgo: $hold + 30, status: 'cancelled');

        $service = new BookingExpiryService();

        $this->assertFalse($service->cancelIfExpired($paidData['booking']));
        $this->assertFalse($service->cancelIfExpired($cancelledData['booking']));

        // Không có gì bị đổi - guard status !== 'pending' chặn ngay từ đầu
        $paidData['booking']->refresh();
        $paidData['seat']->refresh();
        $this->assertSame('paid', $paidData['booking']->status);
        $this->assertSame('held', $paidData['seat']->status);

        $cancelledData['booking']->refresh();
        $cancelledData['seat']->refresh();
        $this->assertSame('cancelled', $cancelledData['booking']->status);
        $this->assertSame('held', $cancelledData['seat']->status);
    }

    public function test_release_handles_infant_ticket_with_null_seat_without_error(): void
    {
        $hold = config('booking.seat_hold_minutes');

        $user = User::factory()->create();
        $flight = Flight::factory()->create();

        $adultSeat = FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'status' => 'held',
            'held_by' => $user->id,
            'held_until' => now()->addMinutes($hold),
        ]);

        $booking = Booking::factory()
            ->createdMinutesAgo($hold + 5)
            ->create(['user_id' => $user->id, 'status' => 'pending']);

        $bookingFlight = BookingFlight::factory()->create([
            'booking_id' => $booking->id,
            'flight_id' => $flight->id,
        ]);

        $adultPassenger = Passenger::factory()->create(['booking_id' => $booking->id]);
        $infantPassenger = Passenger::factory()->infant()->create(['booking_id' => $booking->id]);

        $adultTicket = Ticket::factory()->create([
            'booking_flight_id' => $bookingFlight->id,
            'passenger_id' => $adultPassenger->id,
            'flight_seat_id' => $adultSeat->id,
        ]);

        // Infant: flight_seat_id NULL ngay từ đầu, companion trỏ về adult
        $infantTicket = Ticket::factory()->create([
            'booking_flight_id' => $bookingFlight->id,
            'passenger_id' => $infantPassenger->id,
            'flight_seat_id' => null,
            'companion_adult_passenger_id' => $adultPassenger->id,
            'price' => 150000,
        ]);

        $result = (new BookingExpiryService())->cancelIfExpired($booking);

        $this->assertTrue($result);

        $booking->refresh();
        $adultSeat->refresh();
        $infantTicket->refresh();

        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('available', $adultSeat->status);
        // Infant ticket vốn đã null, không có gì để nullify thêm, nhưng ->filter()
        // trong releaseSeatsAndCancel() phải loại nó ra mà không lỗi
        $this->assertNull($infantTicket->flight_seat_id);
    }

    public function test_cancel_all_expired_only_cancels_pending_bookings_past_threshold(): void
    {
        $hold = config('booking.seat_hold_minutes');

        $expiredPending = $this->makeBookingWithSeat(createdMinutesAgo: $hold + 10, status: 'pending');
        $notYetExpiredPending = $this->makeBookingWithSeat(createdMinutesAgo: $hold - 5, status: 'pending');
        $expiredButPaid = $this->makeBookingWithSeat(createdMinutesAgo: $hold + 10, status: 'paid');
        $expiredButCancelled = $this->makeBookingWithSeat(createdMinutesAgo: $hold + 10, status: 'cancelled');

        $count = (new BookingExpiryService())->cancelAllExpired();

        $this->assertSame(1, $count);

        $this->assertSame('cancelled', $expiredPending['booking']->fresh()->status);
        $this->assertSame('available', $expiredPending['seat']->fresh()->status);

        $this->assertSame('pending', $notYetExpiredPending['booking']->fresh()->status);
        $this->assertSame('held', $notYetExpiredPending['seat']->fresh()->status);

        $this->assertSame('paid', $expiredButPaid['booking']->fresh()->status);
        $this->assertSame('held', $expiredButPaid['seat']->fresh()->status);

        $this->assertSame('cancelled', $expiredButCancelled['booking']->fresh()->status);
        $this->assertSame('held', $expiredButCancelled['seat']->fresh()->status);
    }
}
