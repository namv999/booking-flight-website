<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiLegBookingTest extends TestCase
{
    use RefreshDatabase;

    private function flightAt(int $daysAhead): Flight
    {
        return Flight::factory()->create([
            'departure_time' => now('UTC')->addDays($daysAhead),
            'arrival_time' => now('UTC')->addDays($daysAhead)->addHours(2),
            'status' => 'scheduled',
        ]);
    }

    // Tạo ghế available theo thứ tự giá; id tăng dần nên người lớn nhận ghế đầu, trẻ em ghế sau
    private function addSeats(Flight $flight, FareClass $fareClass, array $prices): void
    {
        foreach ($prices as $price) {
            FlightSeat::factory()->create([
                'flight_id' => $flight->id,
                'fare_class_id' => $fareClass->id,
                'price' => $price,
                'status' => 'available',
            ]);
        }
    }

    private function passengerPayload(): array
    {
        return [
            'adults' => [['full_name' => 'Adult One']],
            'children' => [['full_name' => 'Child One']],
            'infants' => [['full_name' => 'Infant One', 'companion_adult_index' => 0]],
        ];
    }

    public function test_two_legs_create_tickets_for_every_passenger_on_every_leg(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $out = $this->flightAt(2);
        $back = $this->flightAt(5);
        $this->addSeats($out, $fareClass, [1000000, 1100000]);
        $this->addSeats($back, $fareClass, [2000000, 2100000]);

        session(['pending_selection' => $this->pendingSelection([$out, $back], $fareClass, 1, 1, 1)]);

        $response = $this->actingAs($user)->post(route('booking.passengers.store'), $this->passengerPayload());

        $booking = Booking::first();
        $response->assertRedirect(route('payment.show', $booking));

        $this->assertDatabaseCount('booking_flights', 2);
        $this->assertDatabaseCount('passengers', 3);   // tạo 1 lần, không nhân theo chặng
        $this->assertDatabaseCount('tickets', 6);      // 3 hành khách x 2 chặng
        $this->assertSame(4, FlightSeat::where('status', 'held')->count());

        // Chặng đi: 1.000.000 + 1.100.000 + 100.000; chặng về: 2.000.000 + 2.100.000 + 200.000
        $this->assertEquals(6500000, $booking->fresh()->total_amount);
    }

    public function test_infant_price_is_ten_percent_of_companion_seat_price_on_each_leg(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $out = $this->flightAt(2);
        $back = $this->flightAt(5);
        $this->addSeats($out, $fareClass, [1000000, 1100000]);
        $this->addSeats($back, $fareClass, [2000000, 2100000]);

        session(['pending_selection' => $this->pendingSelection([$out, $back], $fareClass, 1, 1, 1)]);

        $this->actingAs($user)->post(route('booking.passengers.store'), $this->passengerPayload());

        $infantPrices = Ticket::whereHas('passenger', fn ($q) => $q->where('passenger_type', 'infant'))
            ->orderBy('price')->pluck('price')->map(fn ($p) => (float) $p)->all();

        $this->assertEquals([100000.0, 200000.0], $infantPrices);
    }

    public function test_leg_without_enough_seats_rolls_back_every_leg(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $out = $this->flightAt(2);
        $back = $this->flightAt(5);
        $this->addSeats($out, $fareClass, [1000000, 1100000]);
        $this->addSeats($back, $fareClass, [2000000]); // chặng về chỉ 1 ghế, cần 2

        session(['pending_selection' => $this->pendingSelection([$out, $back], $fareClass, 1, 1, 0)]);

        $response = $this->actingAs($user)->post(route('booking.passengers.store'), [
            'adults' => [['full_name' => 'Adult One']],
            'children' => [['full_name' => 'Child One']],
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('tickets', 0);
        // Ghế chặng đi KHÔNG được còn held dù chặng đi đủ ghế
        $this->assertSame(3, FlightSeat::where('status', 'available')->count());
        $this->assertSame(0, FlightSeat::whereNotNull('held_by')->count());
        $this->assertNotNull(session('pending_selection'));
    }

    public function test_same_flight_twice_in_legs_is_rejected(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $flight = $this->flightAt(2);
        $this->addSeats($flight, $fareClass, [1000000, 1100000]);

        session(['pending_selection' => $this->pendingSelection([$flight, $flight], $fareClass, 1, 0, 0)]);

        $response = $this->actingAs($user)->post(route('booking.passengers.store'), [
            'adults' => [['full_name' => 'Adult One']],
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('bookings', 0);
    }
}
