<?php

namespace Tests\Feature\Admin;

use App\Models\Airport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AirportControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $response = $this->get(route('admin.airports.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden_from_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->get(route('admin.airports.index'));
        $response->assertForbidden();
    }

    public function test_index_search_matches_name_city_or_iata_code(): void
    {
        $admin = $this->admin();
        Airport::factory()->create(['name' => 'Tan Son Nhat', 'city' => 'Ho Chi Minh City', 'iata_code' => 'SGN']);
        Airport::factory()->create(['name' => 'Noi Bai', 'city' => 'Hanoi', 'iata_code' => 'HAN']);

        $response = $this->actingAs($admin)->get(route('admin.airports.index', ['search' => 'Hanoi']));

        $response->assertOk();
        $airports = $response->viewData('airports');
        $this->assertCount(1, $airports);
        $this->assertEquals('HAN', $airports->first()->iata_code);
    }

    public function test_soft_deleted_airport_excluded_from_search_even_when_iata_matches(): void
    {
        $admin = $this->admin();
        $deleted = Airport::factory()->create(['name' => 'Old Airport', 'iata_code' => 'ZZZ']);
        $deleted->delete();

        $response = $this->actingAs($admin)->get(route('admin.airports.index', ['search' => 'ZZZ']));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('airports'));
    }

    public function test_store_creates_airport(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.airports.store'), [
            'iata_code' => 'DAD',
            'name' => 'Da Nang International Airport',
            'city' => 'Da Nang',
            'country' => 'Vietnam',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $response->assertRedirect(route('admin.airports.index'));
        $this->assertDatabaseHas('airports', ['iata_code' => 'DAD']);
    }

    public function test_store_fails_validation_on_missing_required_fields(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.airports.store'), [
            'name' => 'Missing Fields Airport',
        ]);

        $response->assertSessionHasErrors('iata_code');
        $this->assertDatabaseCount('airports', 0);
    }

    public function test_store_fails_validation_on_duplicate_iata_code(): void
    {
        $admin = $this->admin();
        Airport::factory()->create(['iata_code' => 'SGN']);

        $response = $this->actingAs($admin)->post(route('admin.airports.store'), [
            'iata_code' => 'SGN',
            'name' => 'Duplicate Airport',
            'city' => 'HCMC',
            'country' => 'Vietnam',
            'timezone' => 'Asia/Ho_Chi_Minh',
        ]);

        $response->assertSessionHasErrors('iata_code');
        $this->assertDatabaseCount('airports', 1);
    }

    public function test_update_changes_airport_fields(): void
    {
        $admin = $this->admin();
        $airport = Airport::factory()->create(['name' => 'Old Name', 'iata_code' => 'OLD']);

        $response = $this->actingAs($admin)->put(route('admin.airports.update', $airport), [
            'iata_code' => 'OLD', // giữ nguyên - verify unique ignore() hoạt động
            'name' => 'New Name',
            'city' => $airport->city,
            'country' => $airport->country,
            'timezone' => $airport->timezone,
        ]);

        $response->assertRedirect(route('admin.airports.index'));
        $this->assertDatabaseHas('airports', ['id' => $airport->id, 'name' => 'New Name']);
    }

    public function test_destroy_soft_deletes_airport(): void
    {
        $admin = $this->admin();
        $airport = Airport::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.airports.destroy', $airport));

        $response->assertRedirect(route('admin.airports.index'));
        $this->assertSoftDeleted('airports', ['id' => $airport->id]);
    }

    public function test_show_returns_airport(): void
    {
        $this->markTestIncomplete('admin/airports/show.blade.php chưa tồn tại — routes/controller đã sẵn sàng, chỉ thiếu view.');
    }
}
