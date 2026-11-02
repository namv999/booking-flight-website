<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\Flight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_user_can_view_dashboard_with_real_bookings(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $airportSgn = Airport::factory()->create(['iata_code' => 'SGN', 'city' => 'Ho Chi Minh']);
        $airportHan = Airport::factory()->create(['iata_code' => 'HAN', 'city' => 'Ha Noi']);

        $flightUpcoming = Flight::factory()->create([
            'departure_airport_id' => $airportSgn->id,
            'arrival_airport_id' => $airportHan->id,
            'departure_time' => Carbon::now('UTC')->addDays(5),
            'arrival_time' => Carbon::now('UTC')->addDays(5)->addHours(2),
        ]);

        $bookingUpcoming = Booking::create([
            'user_id' => $user->id,
            'status' => 'paid',
            'total_amount' => 1500000,
        ]);

        BookingFlight::create([
            'booking_id' => $bookingUpcoming->id,
            'flight_id' => $flightUpcoming->id,
        ]);

        $flightCompleted = Flight::factory()->create([
            'departure_airport_id' => $airportHan->id,
            'arrival_airport_id' => $airportSgn->id,
            'departure_time' => Carbon::now('UTC')->subDays(10),
            'arrival_time' => Carbon::now('UTC')->subDays(10)->addHours(2),
        ]);

        $bookingCompleted = Booking::create([
            'user_id' => $user->id,
            'status' => 'paid',
            'total_amount' => 1200000,
        ]);

        BookingFlight::create([
            'booking_id' => $bookingCompleted->id,
            'flight_id' => $flightCompleted->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('upcomingCount', 1);
        $response->assertViewHas('completedCount', 1);
        $response->assertViewHas('totalBookings', 2);
        $response->assertSee('Ho Chi Minh');
        $response->assertSee('Ha Noi');
        $response->assertSee('SGN');
        $response->assertSee('HAN');
    }

    public function test_user_dashboard_empty_state_when_no_bookings(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('upcomingCount', 0);
        $response->assertViewHas('completedCount', 0);
        $response->assertViewHas('totalBookings', 0);
        $response->assertSee('Bạn chưa có chuyến bay nào sắp tới');
    }
}
