<?php

namespace Tests\Feature\Admin;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Passenger;
use App\Models\PassportUpdateRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_dashboard(): void
    {
        $response = $this->actingAs($this->customer())->get(route('admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_dashboard_and_view_rendered_metrics(): void
    {
        $admin = $this->admin();

        // Dựng dữ liệu chuyến bay, ghế, thanh toán thành công
        $airline = Airline::factory()->create(['name' => 'Jet Airlines']);
        $dep = Airport::factory()->create(['iata_code' => 'SGN']);
        $arr = Airport::factory()->create(['iata_code' => 'HAN']);
        $flight = Flight::factory()->create([
            'departure_airport_id' => $dep->id,
            'arrival_airport_id' => $arr->id,
            'departure_time' => now()->setTime(9, 0),
        ]);
        $fare = FareClass::factory()->create();

        FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fare->id,
            'status' => 'booked',
        ]);
        FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fare->id,
            'status' => 'available',
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $admin->id,
            'total_amount' => 2500000,
            'status' => 'paid',
        ]);

        Payment::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 2500000,
            'status' => 'success',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas([
            'monthlyRevenue',
            'airlineNames',
            'airlineFlightCounts',
            'occupancyRate',
            'emptyRate',
            'totalSeats',
            'bookedSeats',
            'totalRevenue',
            'totalBookings',
            'paidBookingsCount',
            'pendingBookingsCount',
            'todayFlightsCount',
            'recentBookings',
        ]);

        $response->assertSee('Bảng điều khiển Tổng quan');
        $response->assertSee('revenueChart');
        $response->assertSee('occupancyChart');
        $response->assertSee('airlineFlightChart');
        $response->assertSee('2.500.000');
    }

    public function test_dashboard_displays_pending_passport_alert_when_requests_exist(): void
    {
        $admin = $this->admin();
        $booking = Booking::factory()->create(['user_id' => $admin->id]);
        $passenger = Passenger::factory()->create(['booking_id' => $booking->id]);

        PassportUpdateRequest::create([
            'booking_id' => $booking->id,
            'passenger_id' => $passenger->id,
            'user_id' => $admin->id,
            'new_data' => ['document_number' => 'C9999999'],
            'old_data' => ['document_number' => 'C1111111'],
            'reason' => 'Đổi số hộ chiếu mới làm lại',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('yêu cầu sửa đổi hộ chiếu đang chờ phê duyệt');
        $response->assertSee('Đổi số hộ chiếu mới');
    }
}
