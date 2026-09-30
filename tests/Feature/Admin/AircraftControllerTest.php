<?php

namespace Tests\Feature\Admin;

use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Flight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AircraftControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $response = $this->get(route('admin.aircrafts.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden_from_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->get(route('admin.aircrafts.index'));
        $response->assertForbidden();
    }

    public function test_index_search_matches_model(): void
    {
        $admin = $this->admin();
        Aircraft::factory()->create(['model' => 'Airbus A321']);
        Aircraft::factory()->create(['model' => 'Boeing 787']);

        $response = $this->actingAs($admin)->get(route('admin.aircrafts.index', ['search' => 'Boeing']));

        $response->assertOk();
        $aircrafts = $response->viewData('aircrafts');
        $this->assertCount(1, $aircrafts);
        $this->assertEquals('Boeing 787', $aircrafts->first()->model);
    }

    public function test_soft_deleted_aircraft_excluded_from_index_search(): void
    {
        $admin = $this->admin();
        $deleted = Aircraft::factory()->create(['model' => 'Old Airbus A319']);
        $deleted->delete();

        $response = $this->actingAs($admin)->get(route('admin.aircrafts.index', ['search' => 'A319']));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('aircrafts'));
    }

    public function test_store_creates_aircraft(): void
    {
        $admin = $this->admin();
        $airline = Airline::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.aircrafts.store'), [
            'airline_id' => $airline->id,
            'model' => 'Airbus A350',
            'registration_number' => 'VN-A999',
            'total_seats' => 300,
        ]);

        $response->assertRedirect(route('admin.aircrafts.index'));
        $this->assertDatabaseHas('aircrafts', ['registration_number' => 'VN-A999']);
    }

    public function test_store_fails_validation_on_missing_required_fields(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.aircrafts.store'), [
            'model' => 'Incomplete Aircraft',
        ]);

        $response->assertSessionHasErrors('airline_id');
        $this->assertDatabaseCount('aircrafts', 0);
    }

    public function test_store_fails_validation_on_duplicate_registration_number(): void
    {
        $admin = $this->admin();
        Aircraft::factory()->create(['registration_number' => 'VN-A111']);

        $response = $this->actingAs($admin)->post(route('admin.aircrafts.store'), [
            'airline_id' => Airline::factory()->create()->id,
            'model' => 'Duplicate Reg',
            'registration_number' => 'VN-A111',
            'total_seats' => 180,
        ]);

        $response->assertSessionHasErrors('registration_number');
        $this->assertDatabaseCount('aircrafts', 1);
    }

    public function test_update_changes_aircraft_fields(): void
    {
        $admin = $this->admin();
        $aircraft = Aircraft::factory()->create(['model' => 'Old Model', 'registration_number' => 'VN-OLD']);

        $response = $this->actingAs($admin)->put(route('admin.aircrafts.update', $aircraft), [
            'airline_id' => $aircraft->airline_id,
            'model' => 'New Model',
            'registration_number' => 'VN-OLD', // giữ nguyên - verify unique ignore()
            'total_seats' => $aircraft->total_seats,
        ]);

        $response->assertRedirect(route('admin.aircrafts.index'));
        $this->assertDatabaseHas('aircrafts', ['id' => $aircraft->id, 'model' => 'New Model']);
    }

    public function test_destroy_soft_deletes_aircraft_without_flights(): void
    {
        $admin = $this->admin();
        $aircraft = Aircraft::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.aircrafts.destroy', $aircraft));

        $response->assertRedirect(route('admin.aircrafts.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('aircrafts', ['id' => $aircraft->id]);
    }

    public function test_destroy_soft_deletes_aircraft_even_when_it_has_flights(): void
    {
        $admin = $this->admin();
        $aircraft = Aircraft::factory()->create();
        Flight::factory()->create(['aircraft_id' => $aircraft->id]);

        $response = $this->actingAs($admin)->delete(route('admin.aircrafts.destroy', $aircraft));

        $response->assertRedirect(route('admin.aircrafts.index'));
        $response->assertSessionHas('success');

        // Soft-delete vẫn thành công dù có flight liên quan - đúng thiết kế,
        // vì UPDATE deleted_at không đụng tới FK RESTRICT (khác với hard delete)
        $this->assertSoftDeleted('aircrafts', ['id' => $aircraft->id]);

        // Flight liên quan vẫn còn nguyên, không bị ảnh hưởng
        $this->assertDatabaseHas('flights', ['aircraft_id' => $aircraft->id]);
    }

    public function test_show_returns_aircraft(): void
    {
        $this->markTestIncomplete('admin/aircrafts/show.blade.php chưa tồn tại — routes/controller đã sẵn sàng, chỉ thiếu view.');
    }
}
