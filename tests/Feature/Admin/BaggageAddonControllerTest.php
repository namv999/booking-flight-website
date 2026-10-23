<?php

namespace Tests\Feature\Admin;

use App\Models\BaggageAddon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BaggageAddonControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('admin.baggage-addons.index'))
            ->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden_from_index(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.baggage-addons.index'))
            ->assertForbidden();
    }

    public function test_admin_can_open_index_create_and_edit_pages(): void
    {
        $admin = $this->admin();
        $addon = BaggageAddon::factory()->create();

        $this->actingAs($admin)->get(route('admin.baggage-addons.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.baggage-addons.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.baggage-addons.edit', $addon))->assertOk();
    }

    public function test_store_creates_baggage_addon(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.baggage-addons.store'), [
            'name' => 'Gói 15kg',
            'weight_kg' => 15,
            'price' => 150000,
        ]);

        $response->assertRedirect(route('admin.baggage-addons.index'));
        $this->assertDatabaseHas('baggage_addons', [
            'name' => 'Gói 15kg',
            'weight_kg' => 15,
            'price' => 150000,
        ]);
    }

    public static function invalidPayloads(): array
    {
        return [
            'ten rong' => [['name' => '', 'weight_kg' => 15, 'price' => 100000], 'name'],
            'ten dai hon 50 ky tu' => [['name' => str_repeat('a', 51), 'weight_kg' => 15, 'price' => 100000], 'name'],
            'weight bang 0' => [['name' => 'Goi', 'weight_kg' => 0, 'price' => 100000], 'weight_kg'],
            'weight khong phai so' => [['name' => 'Goi', 'weight_kg' => 'abc', 'price' => 100000], 'weight_kg'],
            'gia am' => [['name' => 'Goi', 'weight_kg' => 15, 'price' => -1], 'price'],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_store_rejects_invalid_data(array $payload, string $errorField): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.baggage-addons.store'), $payload);

        $response->assertSessionHasErrors($errorField);
        $this->assertDatabaseCount('baggage_addons', 0);
    }

    public function test_update_changes_baggage_addon(): void
    {
        $addon = BaggageAddon::factory()->create(['name' => 'Gói cũ', 'weight_kg' => 10, 'price' => 100000]);

        $response = $this->actingAs($this->admin())->put(route('admin.baggage-addons.update', $addon), [
            'name' => 'Gói mới',
            'weight_kg' => 20,
            'price' => 250000,
        ]);

        $response->assertRedirect(route('admin.baggage-addons.index'));
        $this->assertDatabaseHas('baggage_addons', [
            'id' => $addon->id,
            'name' => 'Gói mới',
            'weight_kg' => 20,
            'price' => 250000,
        ]);
    }

    public function test_destroy_soft_deletes_baggage_addon(): void
    {
        $addon = BaggageAddon::factory()->create();

        $response = $this->actingAs($this->admin())
            ->delete(route('admin.baggage-addons.destroy', $addon));

        $response->assertRedirect(route('admin.baggage-addons.index'));
        $this->assertSoftDeleted('baggage_addons', ['id' => $addon->id]);
    }

    public function test_search_returns_matching_addons_only(): void
    {
        BaggageAddon::factory()->create(['name' => 'Gói 15kg']);
        BaggageAddon::factory()->create(['name' => 'Gói 30kg']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.baggage-addons.index', ['search' => '15kg']));

        $response->assertOk();
        $addons = $response->viewData('baggageAddons');
        $this->assertCount(1, $addons);
        $this->assertEquals('Gói 15kg', $addons->first()->name);
    }

    public function test_search_excludes_soft_deleted_addons(): void
    {
        $deleted = BaggageAddon::factory()->create(['name' => 'Gói cũ 40kg']);
        $deleted->delete();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.baggage-addons.index', ['search' => '40kg']));

        $this->assertCount(0, $response->viewData('baggageAddons'));
    }

    public function test_index_is_paginated_and_keeps_search_term(): void
    {
        BaggageAddon::factory()->count(12)->create(['name' => 'Gói test']);
        $admin = $this->admin();

        $page1 = $this->actingAs($admin)->get(route('admin.baggage-addons.index'));
        $this->assertCount(10, $page1->viewData('baggageAddons'));

        $page2 = $this->actingAs($admin)->get(route('admin.baggage-addons.index', ['page' => 2]));
        $this->assertCount(2, $page2->viewData('baggageAddons'));

        $withSearch = $this->actingAs($admin)
            ->get(route('admin.baggage-addons.index', ['search' => 'test']));
        $this->assertStringContainsString('search=test', $withSearch->viewData('baggageAddons')->url(2));
    }
}