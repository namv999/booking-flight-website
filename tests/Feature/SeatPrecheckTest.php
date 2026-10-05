<?php

namespace Tests\Feature;

use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeatPrecheckTest extends TestCase
{
    use RefreshDatabase;

    // Chuyến scheduled + khởi hành tương lai, để bộ test vẫn đúng sau khi
    // precheck bổ sung kiểm tra trạng thái/thời gian chuyến (commit 2)
    private function makeFlight(array $overrides = []): Flight
    {
        return Flight::factory()->create(array_merge([
            'departure_time' => now('UTC')->addDays(2),
            'arrival_time' => now('UTC')->addDays(2)->addHours(2),
            'status' => 'scheduled',
        ], $overrides));
    }

    // $state: null (available) | heldExpired | heldValid | booked
    private function addSeats(Flight $flight, FareClass $fareClass, int $count, ?string $state = null): void
    {
        $factory = FlightSeat::factory()->count($count);
        $attrs = ['flight_id' => $flight->id, 'fare_class_id' => $fareClass->id];

        if ($state) {
            $factory = $factory->{$state}();
        } else {
            $attrs['status'] = 'available';
        }

        $factory->create($attrs);
    }

    private function payload(Flight $flight, FareClass $fareClass, array $overrides = []): array
    {
        return array_merge([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'adults' => 1,
            'children' => 0,
            'infants' => 0,
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $fareClass = FareClass::factory()->create();
        $flight = $this->makeFlight();

        $this->post(route('booking.precheck'), $this->payload($flight, $fareClass))
            ->assertRedirect(route('login'));
    }

    public function test_success_stores_selection_in_session_without_touching_seats(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $flight = $this->makeFlight();
        $this->addSeats($flight, $fareClass, 2);

        // 1 adult + 1 child + 1 infant chỉ cần 2 ghế (infant không chiếm ghế)
        $response = $this->actingAs($user)->post(
            route('booking.precheck'),
            $this->payload($flight, $fareClass, ['adults' => 1, 'children' => 1, 'infants' => 1])
        );

        $response->assertRedirect(route('booking.passengers.form'));

        $pending = session('pending_selection');
        $this->assertSame('one-way', $pending['trip_type']);
        $this->assertEquals($fareClass->id, $pending['fare_class_id']);
        $this->assertEquals([1, 1, 1], [$pending['adults'], $pending['children'], $pending['infants']]);
        $this->assertCount(1, $pending['legs']);
        $this->assertEquals($flight->id, $pending['legs'][0]['flight_id']);
        $this->assertEquals($flight->departure_airport_id, $pending['legs'][0]['from']);
        $this->assertEquals($flight->arrival_airport_id, $pending['legs'][0]['to']);

        // precheck KHÔNG giữ ghế: không đổi status, không held_by, không tạo booking
        $this->assertSame(2, FlightSeat::where('status', 'available')->count());
        $this->assertSame(0, FlightSeat::whereNotNull('held_by')->count());
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_not_enough_seats_returns_error_and_no_session(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $flight = $this->makeFlight();
        $this->addSeats($flight, $fareClass, 1);

        // adult + child cần 2 ghế, chỉ có 1
        $response = $this->actingAs($user)->post(
            route('booking.precheck'),
            $this->payload($flight, $fareClass, ['adults' => 1, 'children' => 1])
        );

        $response->assertSessionHas('error', 'Không đủ ghế trống cho hạng vé này, vui lòng thử lại hoặc chọn chuyến khác.');
        $response->assertSessionMissing('pending_selection');
    }

    public function test_expired_held_seat_counts_as_available_but_valid_held_does_not(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $flight = $this->makeFlight();
        $this->addSeats($flight, $fareClass, 1, 'heldExpired');
        $this->addSeats($flight, $fareClass, 1, 'heldValid');

        // Chỉ 1 ghế tính được (heldExpired) -> 2 người lớn thất bại
        $this->actingAs($user)->post(
            route('booking.precheck'),
            $this->payload($flight, $fareClass, ['adults' => 2])
        )
            ->assertSessionHas('error');

        // 1 người lớn thì đủ
        $this->actingAs($user)->post(
            route('booking.precheck'),
            $this->payload($flight, $fareClass, ['adults' => 1])
        )
            ->assertRedirect(route('booking.passengers.form'));
    }

    public function test_seats_of_other_fare_class_are_not_counted(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $otherFareClass = FareClass::factory()->create();
        $flight = $this->makeFlight();
        $this->addSeats($flight, $otherFareClass, 3);

        $response = $this->actingAs($user)->post(
            route('booking.precheck'),
            $this->payload($flight, $fareClass)
        );

        $response->assertSessionHas('error');
        $response->assertSessionMissing('pending_selection');
    }

    public function test_validation_errors_for_missing_or_unknown_flight_and_zero_adults(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $flight = $this->makeFlight();

        $this->actingAs($user)->post(
            route('booking.precheck'),
            $this->payload($flight, $fareClass, ['flight_id' => null])
        )
            ->assertSessionHasErrors('flight_id');

        $this->actingAs($user)->post(
            route('booking.precheck'),
            $this->payload($flight, $fareClass, ['flight_id' => 999999])
        )
            ->assertSessionHasErrors('flight_id');

        $this->actingAs($user)->post(
            route('booking.precheck'),
            $this->payload($flight, $fareClass, ['adults' => 0])
        )
            ->assertSessionHasErrors('adults');
    }

    public function test_cancelled_flight_is_rejected(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $flight = $this->makeFlight(['status' => 'cancelled']);
        $this->addSeats($flight, $fareClass, 2);

        $response = $this->actingAs($user)->post(route('booking.precheck'), $this->payload($flight, $fareClass));

        $response->assertSessionHas('error', 'Chuyến bay này không còn khả dụng, vui lòng chọn chuyến khác.');
        $response->assertSessionMissing('pending_selection');
    }

    public function test_flight_that_already_departed_is_rejected(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $flight = $this->makeFlight([
            'departure_time' => now('UTC')->subHour(),
            'arrival_time' => now('UTC')->addHour(),
        ]);
        $this->addSeats($flight, $fareClass, 2);

        $response = $this->actingAs($user)->post(route('booking.precheck'), $this->payload($flight, $fareClass));

        $response->assertSessionHas('error');
        $response->assertSessionMissing('pending_selection');
    }

    public function test_delayed_flight_is_accepted(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $flight = $this->makeFlight(['status' => 'delayed']);
        $this->addSeats($flight, $fareClass, 1);

        $this->actingAs($user)->post(route('booking.precheck'), $this->payload($flight, $fareClass))
            ->assertRedirect(route('booking.passengers.form'));
    }
}
