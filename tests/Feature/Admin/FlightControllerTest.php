<?php

namespace Tests\Feature\Admin;

use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\Flight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlightControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'aircraft_id' => Aircraft::factory()->create()->id,
            'departure_airport_id' => Airport::factory()->create()->id,
            'arrival_airport_id' => Airport::factory()->create()->id,
            'departure_time' => now()->addDays(2)->toDateTimeString(),
            'arrival_time' => now()->addDays(2)->addHours(2)->toDateTimeString(),
            'status' => 'scheduled',
        ], $overrides);
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $response = $this->get(route('admin.flights.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden_from_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->get(route('admin.flights.index'));
        $response->assertForbidden();
    }

    public function test_index_search_matches_departure_or_arrival_airport(): void
    {
        $admin = $this->admin();
        $sgn = Airport::factory()->create(['name' => 'Tan Son Nhat', 'iata_code' => 'SGN']);
        $han = Airport::factory()->create(['name' => 'Noi Bai', 'iata_code' => 'HAN']);
        $dad = Airport::factory()->create(['name' => 'Da Nang', 'iata_code' => 'DAD']);

        $matching = Flight::factory()->create([
            'departure_airport_id' => $sgn->id,
            'arrival_airport_id' => $han->id,
        ]);
        $notMatching = Flight::factory()->create([
            'departure_airport_id' => $dad->id,
            'arrival_airport_id' => $han->id,
        ]);

        // Search theo iata_code của SÂN BAY ĐẾN - verify fix cột iata_code (không phải "code")
        $response = $this->actingAs($admin)->get(route('admin.flights.index', ['search' => 'SGN']));

        $response->assertOk();
        $flights = $response->viewData('flights');
        $this->assertCount(1, $flights);
        $this->assertEquals($matching->id, $flights->first()->id);
    }

    public function test_store_creates_flight(): void
    {
        $admin = $this->admin();
        $payload = $this->validPayload();

        $response = $this->actingAs($admin)->post(route('admin.flights.store'), $payload);

        $response->assertRedirect(route('admin.flights.index'));
        $this->assertDatabaseHas('flights', ['aircraft_id' => $payload['aircraft_id']]);
    }

    public function test_store_fails_validation_when_departure_equals_arrival_airport(): void
    {
        $admin = $this->admin();
        $airport = Airport::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.flights.store'), $this->validPayload([
            'departure_airport_id' => $airport->id,
            'arrival_airport_id' => $airport->id,
        ]));

        $response->assertSessionHasErrors('departure_airport_id');
        $this->assertDatabaseCount('flights', 0);
    }

    public function test_store_fails_validation_when_departure_time_in_the_past(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.flights.store'), $this->validPayload([
            'departure_time' => now()->subDay()->toDateTimeString(),
            'arrival_time' => now()->addHours(2)->toDateTimeString(),
        ]));

        $response->assertSessionHasErrors('departure_time');
        $this->assertDatabaseCount('flights', 0);
    }

    public function test_store_fails_validation_when_arrival_before_departure(): void
    {
        $admin = $this->admin();
        $departure = now()->addDays(2);

        $response = $this->actingAs($admin)->post(route('admin.flights.store'), $this->validPayload([
            'departure_time' => $departure->toDateTimeString(),
            'arrival_time' => $departure->clone()->subHour()->toDateTimeString(), // đến TRƯỚC khi đi
        ]));

        $response->assertSessionHasErrors('arrival_time');
        $this->assertDatabaseCount('flights', 0);
    }

    public function test_store_fails_validation_on_invalid_aircraft_id(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.flights.store'), $this->validPayload([
            'aircraft_id' => 999999,
        ]));

        $response->assertSessionHasErrors('aircraft_id');
        $this->assertDatabaseCount('flights', 0);
    }

    public function test_update_changes_flight_fields(): void
    {
        $admin = $this->admin();
        $flight = Flight::factory()->create(['status' => 'scheduled']);

        $response = $this->actingAs($admin)->put(route('admin.flights.update', $flight), $this->validPayload([
            'aircraft_id' => $flight->aircraft_id,
            'departure_airport_id' => $flight->departure_airport_id,
            'arrival_airport_id' => $flight->arrival_airport_id,
            'status' => 'delayed',
        ]));

        $response->assertRedirect(route('admin.flights.index'));
        $this->assertDatabaseHas('flights', ['id' => $flight->id, 'status' => 'delayed']);
    }

    public function test_destroy_hard_deletes_flight_without_bookings(): void
    {
        $admin = $this->admin();
        $flight = Flight::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.flights.destroy', $flight));

        $response->assertRedirect(route('admin.flights.index'));
        $response->assertSessionHas('success');

        // Flight KHÔNG có SoftDeletes -> phải biến mất hoàn toàn khỏi DB
        $this->assertDatabaseMissing('flights', ['id' => $flight->id]);
    }

    public function test_destroy_blocked_with_friendly_message_when_flight_has_bookings(): void
    {
        $admin = $this->admin();
        $flight = Flight::factory()->create();

        $booking = Booking::factory()->create();
        BookingFlight::factory()->create([
            'booking_id' => $booking->id,
            'flight_id' => $flight->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.flights.destroy', $flight));

        $response->assertRedirect(route('admin.flights.index'));
        $response->assertSessionHas('error');

        // Verify try/catch thật sự bắt được QueryException - flight vẫn còn nguyên
        $this->assertDatabaseHas('flights', ['id' => $flight->id]);
    }

    public function test_show_returns_flight(): void
    {
        $this->markTestIncomplete('admin/flights/show.blade.php chưa tồn tại — routes/controller đã sẵn sàng, chỉ thiếu view.');
    }
}
