<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Passenger;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassengerBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_happy_path_creates_booking_for_single_adult(): void
    {
        $user = User::factory()->create();
        $flight = Flight::factory()->create();
        $fareClass = FareClass::factory()->create();

        $seat = FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'price' => 1500000,
            'status' => 'available',
        ]);

        session(['pending_selection' => [
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'adults' => 1,
            'children' => 0,
            'infants' => 0,
        ]]);

        $response = $this->actingAs($user)->post(route('booking.passengers.store'), [
            'adults' => [
                ['full_name' => 'Nguyen Van A', 'document_number' => '123456789', 'date_of_birth' => '1990-01-01'],
            ],
            'children' => [],
            'infants' => [],
        ]);

        $booking = Booking::first();

        $response->assertRedirect(route('payment.show', $booking));
        $response->assertSessionHas('status');
        $this->assertNull(session('pending_selection'));

        $this->assertNotNull($booking);
        $this->assertSame('pending', $booking->status);
        $this->assertEquals(1500000, $booking->total_amount);

        $this->assertDatabaseCount('passengers', 1);
        $passenger = Passenger::first();
        $this->assertSame('adult', $passenger->passenger_type);
        $this->assertSame('Nguyen Van A', $passenger->full_name);

        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseHas('tickets', [
            'passenger_id' => $passenger->id,
            'flight_seat_id' => $seat->id,
            'price' => 1500000,
        ]);

        $seat->refresh();
        $this->assertSame('held', $seat->status);
        $this->assertSame($user->id, $seat->held_by);
        $this->assertNotNull($seat->held_until);
    }

    public function test_infant_price_is_ten_percent_of_specific_companion_adult_not_average(): void
    {
        $user = User::factory()->create();
        $flight = Flight::factory()->create();
        $fareClass = FareClass::factory()->create();

        // 2 adult ghế giá KHÁC nhau — nếu code lỡ tính infant theo giá trung bình
        // hoặc theo giá adult đầu tiên thay vì đúng companion, test này sẽ bắt được
        $seatAdult1 = FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'price' => 1500000,
            'status' => 'available',
        ]);
        $seatAdult2 = FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'price' => 2000000,
            'status' => 'available',
        ]);
        $seatChild = FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'price' => 1200000,
            'status' => 'available',
        ]);

        session(['pending_selection' => [
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'adults' => 2,
            'children' => 1,
            'infants' => 1,
        ]]);

        $response = $this->actingAs($user)->post(route('booking.passengers.store'), [
            'adults' => [
                ['full_name' => 'Adult One', 'document_number' => '111', 'date_of_birth' => '1990-01-01'],
                ['full_name' => 'Adult Two', 'document_number' => '222', 'date_of_birth' => '1985-05-05'],
            ],
            'children' => [
                ['full_name' => 'Child One', 'document_number' => '333', 'date_of_birth' => '2018-01-01'],
            ],
            'infants' => [
                // companion_adult_index = 1 -> Adult Two (giá 2,000,000)
                ['full_name' => 'Infant One', 'date_of_birth' => '2024-01-01', 'companion_adult_index' => 1],
            ],
        ]);

        $booking = Booking::first();
        $response->assertRedirect(route('payment.show', $booking));

        $this->assertDatabaseCount('passengers', 4);
        $this->assertDatabaseCount('tickets', 4);

        $adultTwo = Passenger::where('full_name', 'Adult Two')->first();
        $infant = Passenger::where('passenger_type', 'infant')->first();

        // Giá infant PHẢI = 10% giá vé Adult Two (2,000,000 * 0.10 = 200,000)
        // KHÔNG được = 10% Adult One (150,000) hay 10% trung bình 2 adult (175,000)
        $this->assertDatabaseHas('tickets', [
            'passenger_id' => $infant->id,
            'flight_seat_id' => null,
            'companion_adult_passenger_id' => $adultTwo->id,
            'price' => 200000,
        ]);

        // Tổng tiền = 1,500,000 + 2,000,000 + 1,200,000 + 200,000
        $booking->refresh();
        $this->assertEquals(4900000, $booking->total_amount);

        // Infant không chiếm ghế -> chỉ 3 ghế (2 adult + 1 child) chuyển held
        $this->assertSame('held', $seatAdult1->fresh()->status);
        $this->assertSame('held', $seatAdult2->fresh()->status);
        $this->assertSame('held', $seatChild->fresh()->status);
    }

    public function test_not_enough_seats_rolls_back_entire_transaction(): void
    {
        $user = User::factory()->create();
        $flight = Flight::factory()->create();
        $fareClass = FareClass::factory()->create();

        // Chỉ 1 ghế available, nhưng cần 2 (1 adult + 1 child)
        $seat = FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'price' => 1500000,
            'status' => 'available',
        ]);

        session(['pending_selection' => [
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'adults' => 1,
            'children' => 1,
            'infants' => 0,
        ]]);

        $response = $this->actingAs($user)->post(route('booking.passengers.store'), [
            'adults' => [
                ['full_name' => 'Adult One', 'document_number' => '111', 'date_of_birth' => '1990-01-01'],
            ],
            'children' => [
                ['full_name' => 'Child One', 'document_number' => '222', 'date_of_birth' => '2018-01-01'],
            ],
            'infants' => [],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // KHÔNG booking/passenger/ticket nào được tạo — rollback toàn bộ closure
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_flights', 0);
        $this->assertDatabaseCount('passengers', 0);
        $this->assertDatabaseCount('tickets', 0);

        // Ghế available duy nhất KHÔNG bị đổi trạng thái (update seat cũng nằm trong
        // transaction bị rollback, dù nó chạy trước exception được throw)
        $seat->refresh();
        $this->assertSame('available', $seat->status);
        $this->assertNull($seat->held_by);
        $this->assertNull($seat->held_until);

        // pending_selection còn nguyên trong session -> user quay lại vẫn giữ được lựa chọn
        $this->assertNotNull(session('pending_selection'));
    }

    public function test_reclaims_expired_held_seat_and_nullifies_old_ticket_reference(): void
    {
        $oldUser = User::factory()->create();
        $newUser = User::factory()->create();
        $flight = Flight::factory()->create();
        $fareClass = FareClass::factory()->create();

        // Ghế đang held bởi booking cũ nhưng đã hết hạn (held_until quá khứ)
        $seat = FlightSeat::factory()->heldExpired()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'price' => 1500000,
            'held_by' => $oldUser->id,
        ]);

        // Booking cũ (mô phỏng: đã hết hạn nhưng BookingExpiryService chưa kịp dọn)
        $oldBooking = Booking::factory()->createdMinutesAgo(30)->create(['user_id' => $oldUser->id]);
        $oldBookingFlight = BookingFlight::factory()->create([
            'booking_id' => $oldBooking->id,
            'flight_id' => $flight->id,
        ]);
        $oldPassenger = Passenger::factory()->create(['booking_id' => $oldBooking->id]);
        $oldTicket = Ticket::factory()->create([
            'booking_flight_id' => $oldBookingFlight->id,
            'passenger_id' => $oldPassenger->id,
            'flight_seat_id' => $seat->id,
            'price' => 1500000,
        ]);

        session(['pending_selection' => [
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'adults' => 1,
            'children' => 0,
            'infants' => 0,
        ]]);

        $response = $this->actingAs($newUser)->post(route('booking.passengers.store'), [
            'adults' => [
                ['full_name' => 'New Adult', 'document_number' => '999', 'date_of_birth' => '1990-01-01'],
            ],
            'children' => [],
            'infants' => [],
        ]);

        $newBooking = Booking::where('user_id', $newUser->id)->first();
        $response->assertRedirect(route('payment.show', $newBooking));

        // Ticket cũ bị nullify flight_seat_id -> không còn trỏ vào ghế nữa
        $oldTicket->refresh();
        $this->assertNull($oldTicket->flight_seat_id);

        // Ticket mới trỏ đúng vào ghế vừa reclaim
        $newPassenger = Passenger::where('booking_id', $newBooking->id)->first();
        $this->assertDatabaseHas('tickets', [
            'passenger_id' => $newPassenger->id,
            'flight_seat_id' => $seat->id,
            'price' => 1500000,
        ]);

        // Ghế chuyển sang held bởi user MỚI, held_until được refresh sang tương lai
        $seat->refresh();
        $this->assertSame('held', $seat->status);
        $this->assertSame($newUser->id, $seat->held_by);
        $this->assertTrue($seat->held_until->isFuture());

        // Xác nhận không vi phạm uq_tickets_flightseat: chỉ đúng 1 ticket non-null trỏ vào seat này
        $this->assertDatabaseCount('tickets', 2); // ticket cũ (null) + ticket mới
        $this->assertSame(
            1,
            Ticket::where('flight_seat_id', $seat->id)->count()
        );
    }

    public function test_redirects_to_home_when_no_pending_selection_in_session(): void
    {
        $user = User::factory()->create();

        // Không set session('pending_selection') gì cả

        $response = $this->actingAs($user)->post(route('booking.passengers.store'), [
            'adults' => [
                ['full_name' => 'Someone', 'document_number' => '111', 'date_of_birth' => '1990-01-01'],
            ],
            'children' => [],
            'infants' => [],
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('passengers', 0);
        $this->assertDatabaseCount('tickets', 0);
    }
}
