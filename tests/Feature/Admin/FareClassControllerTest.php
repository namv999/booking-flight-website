<?php

namespace Tests\Feature\Admin;

use App\Models\FareClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FareClassControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $response = $this->get(route('admin.fare-classes.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden_from_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->get(route('admin.fare-classes.index'));
        $response->assertForbidden();
    }

    public function test_index_search_matches_name_or_description(): void
    {
        $admin = $this->admin();
        FareClass::factory()->create(['name' => 'Economy', 'description' => 'Hạng phổ thông']);
        FareClass::factory()->create(['name' => 'Business', 'description' => 'Hạng thương gia']);

        $response = $this->actingAs($admin)->get(route('admin.fare-classes.index', ['search' => 'thương gia']));

        $response->assertOk();
        $fareClasses = $response->viewData('fareClasses');
        $this->assertCount(1, $fareClasses);
        $this->assertEquals('Business', $fareClasses->first()->name);
    }

    public function test_soft_deleted_fare_class_excluded_from_search_even_when_description_matches(): void
    {
        $admin = $this->admin();
        $deleted = FareClass::factory()->create(['name' => 'Old Class', 'description' => 'Gói cũ đặc biệt']);
        $deleted->delete();

        $response = $this->actingAs($admin)->get(route('admin.fare-classes.index', ['search' => 'đặc biệt']));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('fareClasses'));
    }

    public function test_store_creates_fare_class(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.fare-classes.store'), [
            'name' => 'Premium Economy',
            'base_price' => 2200000,
            'seat_selection_fee' => 100000,
            'checked_baggage_kg' => 15,
            'carry_on_baggage_kg' => 7,
            'description' => null,
        ]);

        $response->assertRedirect(route('admin.fare-classes.index'));
        $this->assertDatabaseHas('fare_classes', ['name' => 'Premium Economy']);
    }

    public function test_store_fails_validation_on_missing_required_fields(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.fare-classes.store'), [
            'name' => 'Incomplete',
        ]);

        $response->assertSessionHasErrors('base_price');
        $this->assertDatabaseCount('fare_classes', 0);
    }

    public function test_store_fails_validation_on_negative_price(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.fare-classes.store'), [
            'name' => 'Negative Price',
            'base_price' => -100,
            'seat_selection_fee' => 0,
            'checked_baggage_kg' => 10,
            'carry_on_baggage_kg' => 7,
        ]);

        $response->assertSessionHasErrors('base_price');
        $this->assertDatabaseCount('fare_classes', 0);
    }

    public function test_update_changes_fare_class_fields(): void
    {
        $admin = $this->admin();
        $fareClass = FareClass::factory()->create(['name' => 'Old Name', 'base_price' => 1500000]);

        $response = $this->actingAs($admin)->put(route('admin.fare-classes.update', $fareClass), [
            'name' => 'New Name',
            'base_price' => 1800000,
            'seat_selection_fee' => $fareClass->seat_selection_fee,
            'checked_baggage_kg' => $fareClass->checked_baggage_kg,
            'carry_on_baggage_kg' => $fareClass->carry_on_baggage_kg,
        ]);

        $response->assertRedirect(route('admin.fare-classes.index'));
        $this->assertDatabaseHas('fare_classes', ['id' => $fareClass->id, 'base_price' => 1800000]);
    }

    public function test_destroy_soft_deletes_fare_class(): void
    {
        $admin = $this->admin();
        $fareClass = FareClass::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.fare-classes.destroy', $fareClass));

        $response->assertRedirect(route('admin.fare-classes.index'));
        $this->assertSoftDeleted('fare_classes', ['id' => $fareClass->id]);
    }

    public function test_show_returns_fare_class(): void
    {
        $admin = $this->admin();
        $fareClass = FareClass::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.fare-classes.show', $fareClass));

        $response->assertOk();
        $this->assertEquals($fareClass->id, $response->viewData('fareClass')->id);
    }
}
