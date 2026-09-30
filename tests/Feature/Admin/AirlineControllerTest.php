<?php

namespace Tests\Feature\Admin;

use App\Models\Airline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AirlineControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $response = $this->get(route('admin.airlines.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden_from_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->get(route('admin.airlines.index'));
        $response->assertForbidden();
    }

    public function test_index_search_matches_name_or_code(): void
    {
        $admin = $this->admin();
        Airline::factory()->create(['name' => 'Vietnam Airlines', 'code' => 'VN']);
        Airline::factory()->create(['name' => 'VietJet Air', 'code' => 'VJ']);

        $response = $this->actingAs($admin)->get(route('admin.airlines.index', ['search' => 'VJ']));

        $response->assertOk();
        $airlines = $response->viewData('airlines');
        $this->assertCount(1, $airlines);
        $this->assertEquals('VJ', $airlines->first()->code);
    }

    public function test_soft_deleted_airline_excluded_from_search_even_when_code_matches(): void
    {
        $admin = $this->admin();
        $deleted = Airline::factory()->create(['name' => 'Old Airline', 'code' => 'ZZ']);
        $deleted->delete();

        // Search bằng field KHÔNG match (name) nhưng field match (code) -
        // đúng kịch bản bug orWhere cũ: nếu chưa fix, record này vẫn lọt qua
        $response = $this->actingAs($admin)->get(route('admin.airlines.index', ['search' => 'ZZ']));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('airlines'));
    }

    public function test_store_creates_airline(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.airlines.store'), [
            'name' => 'Bamboo Airways',
            'code' => 'QH',
            'logo_url' => null,
            'country' => 'Vietnam',
        ]);

        $response->assertRedirect(route('admin.airlines.index'));
        $this->assertDatabaseHas('airlines', ['code' => 'QH', 'name' => 'Bamboo Airways']);
    }

    public function test_store_fails_validation_on_missing_required_fields(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.airlines.store'), [
            'code' => 'QH',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('airlines', 0);
    }

    public function test_store_fails_validation_on_duplicate_code(): void
    {
        $admin = $this->admin();
        Airline::factory()->create(['code' => 'VN']);

        $response = $this->actingAs($admin)->post(route('admin.airlines.store'), [
            'name' => 'Another VN',
            'code' => 'VN',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertDatabaseCount('airlines', 1);
    }

    public function test_update_changes_airline_fields(): void
    {
        $admin = $this->admin();
        $airline = Airline::factory()->create(['name' => 'Old Name', 'code' => 'OL']);

        $response = $this->actingAs($admin)->put(route('admin.airlines.update', $airline), [
            'name' => 'New Name',
            'code' => 'OL', // giữ nguyên code chính nó - verify unique ignore() hoạt động
            'country' => 'Vietnam',
        ]);

        $response->assertRedirect(route('admin.airlines.index'));
        $this->assertDatabaseHas('airlines', ['id' => $airline->id, 'name' => 'New Name']);
    }

    public function test_destroy_soft_deletes_airline(): void
    {
        $admin = $this->admin();
        $airline = Airline::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.airlines.destroy', $airline));

        $response->assertRedirect(route('admin.airlines.index'));
        $this->assertSoftDeleted('airlines', ['id' => $airline->id]);

        // Đã xóa mềm -> không còn xuất hiện trong index mặc định
        $indexResponse = $this->actingAs($admin)->get(route('admin.airlines.index'));
        $this->assertCount(0, $indexResponse->viewData('airlines'));
    }

    public function test_show_returns_airline(): void
    {
        $admin = $this->admin();
        $airline = Airline::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.airlines.show', $airline));

        $response->assertOk();
        $this->assertEquals($airline->id, $response->viewData('airline')->id);
    }
}
