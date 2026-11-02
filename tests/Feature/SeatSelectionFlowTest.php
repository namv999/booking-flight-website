<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\Booking;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SeatSelectionFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createFlightWithSeats(FareClass $fareClass, int $availableCount = 6): array
    {
        $dep = Airport::factory()->create(['iata_code' => 'SGN', 'timezone' => 'Asia/Ho_Chi_Minh', 'country' => 'Vietnam']);
        $arr = Airport::factory()->create(['iata_code' => 'HAN', 'timezone' => 'Asia/Ho_Chi_Minh', 'country' => 'Vietnam']);

        $flight = Flight::factory()->create([
            'departure_airport_id' => $dep->id,
            'arrival_airport_id' => $arr->id,
            'departure_time' => Carbon::now('UTC')->addDays(3),
            'arrival_time' => Carbon::now('UTC')->addDays(3)->addHours(2),
            'status' => 'scheduled',
        ]);

        $seats = [];
        $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
        for ($i = 0; $i < $availableCount; $i++) {
            $col = $letters[$i % 6];
            $row = intdiv($i, 6) + 1;
            $seatNumber = "{$row}{$col}";

            $seat = Seat::create([
                'aircraft_id' => $flight->aircraft_id,
                'seat_number' => $seatNumber,
                'seat_class' => 'economy',
                'position' => in_array($col, ['A', 'F']) ? 'window' : (in_array($col, ['C', 'D']) ? 'aisle' : 'middle'),
            ]);

            $flightSeat = FlightSeat::create([
                'flight_id' => $flight->id,
                'seat_id' => $seat->id,
                'fare_class_id' => $fareClass->id,
                'price' => 1000000,
                'status' => 'available',
            ]);

            $seats[] = $flightSeat;
        }

        return [$flight, $seats];
    }

    public function test_guest_is_redirected_from_seats_page(): void
    {
        $response = $this->get(route('booking.seats.show'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_session_is_redirected(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('booking.seats.show'));
        $response->assertRedirect(route('home'));
    }

    public function test_user_with_pending_session_can_view_seats_page(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create(['seat_selection_fee' => 50000]);
        [$flight, $seats] = $this->createFlightWithSeats($fareClass);

        session([
            'pending_selection' => $this->pendingSelection($flight, $fareClass, 1, 0, 0),
            'pending_passengers' => [
                'adults' => [
                    ['full_name' => 'Nguyen Van A', 'document_number' => '123456789', 'date_of_birth' => '1990-01-01'],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('booking.seats.show'));

        $response->assertOk();
        $response->assertSee('Chọn ghế');
        $response->assertSee('Nguyen Van A');
        $response->assertViewHas('fareClass');
        $response->assertViewHas('mapUrls');
    }

    public function test_map_endpoint_returns_valid_json_grid(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create(['seat_selection_fee' => 60000]);
        [$flight, $seats] = $this->createFlightWithSeats($fareClass, 6);

        session([
            'pending_selection' => $this->pendingSelection($flight, $fareClass, 1, 0, 0),
            'pending_passengers' => [
                'adults' => [
                    ['full_name' => 'Nguyen Van A'],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson(route('booking.seats.map', ['leg' => 0]));

        $response->assertOk();
        $response->assertJsonStructure([
            'rows' => [
                '*' => [
                    'row',
                    'seats' => [
                        '*' => [
                            'flight_seat_id',
                            'seat_number',
                            'seat_class',
                            'position',
                            'col',
                            'aisle_after',
                            'status',
                            'selectable',
                            'fee',
                        ],
                    ],
                ],
            ],
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['rows']);
        $this->assertSame(60000.0, (float) $data['rows'][0]['seats'][0]['fee']);
        $this->assertTrue($data['rows'][0]['seats'][0]['selectable']);
    }

    public function test_booking_passengers_next_saves_to_session_and_redirects(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        [$flight, $seats] = $this->createFlightWithSeats($fareClass);

        session([
            'pending_selection' => $this->pendingSelection($flight, $fareClass, 1, 0, 0),
        ]);

        $response = $this->actingAs($user)->post(route('booking.passengers.next'), [
            'adults' => [
                ['full_name' => 'Tran Van B', 'document_number' => '987654321', 'date_of_birth' => '1995-05-05'],
            ],
        ]);

        $response->assertRedirect(route('booking.seats.show'));
        $this->assertNotNull(session('pending_passengers'));
        $this->assertSame('Tran Van B', session('pending_passengers')['adults'][0]['full_name']);
    }

    public function test_booking_passengers_next_with_skip_seats_creates_booking(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        [$flight, $seats] = $this->createFlightWithSeats($fareClass);

        session([
            'pending_selection' => $this->pendingSelection($flight, $fareClass, 1, 0, 0),
        ]);

        $response = $this->actingAs($user)->post(route('booking.passengers.next'), [
            'skip_seats' => '1',
            'adults' => [
                ['full_name' => 'Le Van C', 'document_number' => '112233445', 'date_of_birth' => '1992-02-02'],
            ],
        ]);

        $booking = Booking::first();
        $this->assertNotNull($booking);
        $response->assertRedirect(route('payment.show', $booking));
        $ticket = Ticket::first();
        $this->assertFalse($ticket->is_self_selected);
    }

    public function test_store_seat_selection_creates_booking_with_seat_fee_and_flag(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create(['seat_selection_fee' => 75000]);
        [$flight, $seats] = $this->createFlightWithSeats($fareClass, 4);

        $chosenSeat = $seats[0]; // Price: 1,000,000

        session([
            'pending_selection' => $this->pendingSelection($flight, $fareClass, 1, 0, 0),
            'pending_passengers' => [
                'adults' => [
                    ['full_name' => 'Pham Van D', 'document_number' => '998877665', 'date_of_birth' => '1988-08-08'],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->post(route('booking.seats.store'), [
            'seats' => [
                0 => [
                    'adults' => [
                        0 => $chosenSeat->id,
                    ],
                ],
            ],
        ]);

        $booking = Booking::first();
        $this->assertNotNull($booking);
        $response->assertRedirect(route('payment.show', $booking));

        // Base seat price 1,000,000 + Seat fee 75,000 = 1,075,000
        $this->assertEquals(1075000, $booking->total_amount);

        $ticket = Ticket::first();
        $this->assertNotNull($ticket);
        $this->assertTrue($ticket->is_self_selected);
        $this->assertEquals(1075000, $ticket->price);
        $this->assertSame($chosenSeat->id, $ticket->flight_seat_id);

        $chosenSeat->refresh();
        $this->assertSame('held', $chosenSeat->status);
        $this->assertSame($user->id, $chosenSeat->held_by);
    }

    public function test_store_seat_selection_auto_assigns_when_seats_not_provided(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create(['seat_selection_fee' => 75000]);
        [$flight, $seats] = $this->createFlightWithSeats($fareClass, 4);

        session([
            'pending_selection' => $this->pendingSelection($flight, $fareClass, 1, 0, 0),
            'pending_passengers' => [
                'adults' => [
                    ['full_name' => 'Vu Van E', 'document_number' => '445566778', 'date_of_birth' => '1985-05-05'],
                ],
            ],
        ]);

        // Auto assignment: no 'seats' input
        $response = $this->actingAs($user)->post(route('booking.seats.store'), []);

        $booking = Booking::first();
        $this->assertNotNull($booking);
        $response->assertRedirect(route('payment.show', $booking));

        // Base price 1,000,000 without seat fee
        $this->assertEquals(1000000, $booking->total_amount);

        $ticket = Ticket::first();
        $this->assertNotNull($ticket);
        $this->assertFalse($ticket->is_self_selected);
        $this->assertEquals(1000000, $ticket->price);
    }
}
