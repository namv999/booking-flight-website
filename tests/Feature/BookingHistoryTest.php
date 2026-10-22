<?php

namespace Tests\Feature;

use App\Models\Aircraft;
use App\Models\Airline;
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

class BookingHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeBookingWithFullRelations(User $user, array $bookingOverrides = []): Booking
    {
        $flight = Flight::factory()->create();
        $seat = FlightSeat::factory()->create(['flight_id' => $flight->id]);

        $booking = Booking::factory()->create(array_merge([
            'user_id' => $user->id,
        ], $bookingOverrides));

        $bookingFlight = BookingFlight::factory()->create([
            'booking_id' => $booking->id,
            'flight_id' => $flight->id,
        ]);

        $passenger = Passenger::factory()->create(['booking_id' => $booking->id]);

        Ticket::factory()->create([
            'booking_flight_id' => $bookingFlight->id,
            'passenger_id' => $passenger->id,
            'flight_seat_id' => $seat->id,
        ]);

        return $booking;
    }

    public function test_index_only_shows_current_users_bookings(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $bookingA = $this->makeBookingWithFullRelations($userA);
        $this->makeBookingWithFullRelations($userB);

        $response = $this->actingAs($userA)->get(route('booking-history.index'));

        $response->assertOk();
        $bookings = $response->viewData('bookings');

        $this->assertCount(1, $bookings);
        $this->assertEquals($bookingA->id, $bookings->first()->id);
    }

    public function test_index_sorts_bookings_newest_first(): void
    {
        $user = User::factory()->create();

        $older = $this->makeBookingWithFullRelations($user);
        $older->forceFill(['created_at' => now()->subDays(2)])->save();

        $newer = $this->makeBookingWithFullRelations($user);
        $newer->forceFill(['created_at' => now()->subHour()])->save();

        $response = $this->actingAs($user)->get(route('booking-history.index'));

        $bookings = $response->viewData('bookings');
        $this->assertEquals($newer->id, $bookings->first()->id);
        $this->assertEquals($older->id, $bookings->last()->id);
    }

    public function test_show_returns_own_booking_with_full_relations(): void
    {
        $user = User::factory()->create();
        $booking = $this->makeBookingWithFullRelations($user);
        Payment::factory()->create(['booking_id' => $booking->id]);

        $response = $this->actingAs($user)->get(route('booking-history.show', $booking->id));

        $response->assertOk();
        $viewBooking = $response->viewData('booking');

        $this->assertEquals($booking->id, $viewBooking->id);
        $this->assertNotNull($viewBooking->payment);
        $this->assertCount(1, $viewBooking->passengers);
        $this->assertCount(1, $viewBooking->bookingFlights);
        $this->assertCount(1, $viewBooking->bookingFlights->first()->tickets);
    }

    public function test_show_returns_404_for_other_users_booking(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $booking = $this->makeBookingWithFullRelations($owner);

        $response = $this->actingAs($stranger)->get(route('booking-history.show', $booking->id));

        // Scoped qua auth()->user()->bookings()->findOrFail() -> 404, KHÔNG phải 403
        $response->assertNotFound();
    }

    public function test_show_returns_404_for_nonexistent_booking_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('booking-history.show', 999999));

        $response->assertNotFound();
    }

    public function test_show_loads_data_even_when_airline_is_soft_deleted(): void
    {
        $user = User::factory()->create();
        $airline = Airline::factory()->create();
        $aircraft = Aircraft::factory()->create(['airline_id' => $airline->id]);
        $flight = Flight::factory()->create(['aircraft_id' => $aircraft->id]);
        $seat = FlightSeat::factory()->create(['flight_id' => $flight->id]);

        $booking = Booking::factory()->create(['user_id' => $user->id]);
        $bookingFlight = BookingFlight::factory()->create([
            'booking_id' => $booking->id,
            'flight_id' => $flight->id,
        ]);
        $passenger = Passenger::factory()->create(['booking_id' => $booking->id]);
        Ticket::factory()->create([
            'booking_flight_id' => $bookingFlight->id,
            'passenger_id' => $passenger->id,
            'flight_seat_id' => $seat->id,
        ]);

        // Soft-delete airline SAU khi booking đã tồn tại - đúng kịch bản thật:
        // admin xóa hãng bay, booking cũ vẫn phải xem lại được
        $airline->delete();

        $response = $this->actingAs($user)->get(route('booking-history.show', $booking->id));

        $response->assertOk();
        $viewBooking = $response->viewData('booking');

        $loadedAirline = $viewBooking->bookingFlights->first()->flight->aircraft->airline;
        $this->assertNotNull($loadedAirline);
        $this->assertEquals($airline->id, $loadedAirline->id);
        $this->assertNotNull($loadedAirline->deleted_at);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('booking-history.index'));
        $response->assertRedirect(route('login'));

        $booking = $this->makeBookingWithFullRelations(User::factory()->create());
        $response = $this->get(route('booking-history.show', $booking->id));
        $response->assertRedirect(route('login'));
    }
}
