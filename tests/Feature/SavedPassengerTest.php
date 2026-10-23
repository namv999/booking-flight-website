<?php

namespace Tests\Feature;

use App\Models\SavedPassenger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedPassengerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_shows_current_users_saved_passengers(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $passengerA = SavedPassenger::factory()->create(['user_id' => $userA->id]);
        SavedPassenger::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA)->get(route('saved-passengers.index'));

        $response->assertOk();
        $list = $response->viewData('savedPassengers');
        $this->assertCount(1, $list);
        $this->assertEquals($passengerA->id, $list->first()->id);
    }

    public function test_store_creates_saved_passenger_for_current_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('saved-passengers.store'), [
            'full_name' => 'Nguyen Van A',
            'document_number' => '123456789',
            'date_of_birth' => '1990-01-01',
            'passenger_type_default' => 'adult',
            'relationship' => null,
        ]);

        $response->assertRedirect(route('saved-passengers.index'));
        $this->assertDatabaseHas('saved_passengers', [
            'user_id' => $user->id,
            'full_name' => 'Nguyen Van A',
        ]);
    }

    public function test_store_fails_validation_on_missing_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('saved-passengers.store'), [
            'document_number' => '111',
        ]);

        $response->assertSessionHasErrors('full_name');
        $this->assertDatabaseCount('saved_passengers', 0);
    }

    public function test_document_number_unique_is_scoped_per_user_not_global(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        SavedPassenger::factory()->create(['user_id' => $userA->id, 'document_number' => '999888777']);

        // User B dùng TRÙNG document_number với user A -> phải OK, vì unique scoped theo user_id
        $response = $this->actingAs($userB)->post(route('saved-passengers.store'), [
            'full_name' => 'User B Passenger',
            'document_number' => '999888777',
            'passenger_type_default' => 'adult',
        ]);

        $response->assertRedirect(route('saved-passengers.index'));
        $this->assertDatabaseCount('saved_passengers', 2);
    }

    public function test_document_number_unique_blocks_duplicate_within_same_user(): void
    {
        $user = User::factory()->create();
        SavedPassenger::factory()->create(['user_id' => $user->id, 'document_number' => '111222333']);

        $response = $this->actingAs($user)->post(route('saved-passengers.store'), [
            'full_name' => 'Second Profile Same Doc',
            'document_number' => '111222333',
            'passenger_type_default' => 'adult',
        ]);

        $response->assertSessionHasErrors('document_number');
        $this->assertDatabaseCount('saved_passengers', 1);
    }

    public function test_update_ignores_unique_rule_against_its_own_record(): void
    {
        $user = User::factory()->create();
        $passenger = SavedPassenger::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Old Name',
            'document_number' => '555666777',
        ]);

        $response = $this->actingAs($user)->put(route('saved-passengers.update', $passenger->id), [
            'full_name' => 'New Name',
            'document_number' => '555666777', // giữ nguyên document_number của chính nó
            'passenger_type_default' => 'adult',
        ]);

        $response->assertRedirect(route('saved-passengers.index'));
        $this->assertDatabaseHas('saved_passengers', [
            'id' => $passenger->id,
            'full_name' => 'New Name',
            'document_number' => '555666777',
        ]);
    }

    public function test_user_cannot_edit_another_users_saved_passenger(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $passenger = SavedPassenger::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($stranger)->put(route('saved-passengers.update', $passenger->id), [
            'full_name' => 'Hacked Name',
            'passenger_type_default' => 'adult',
        ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('saved_passengers', ['id' => $passenger->id, 'full_name' => $passenger->full_name]);
    }

    public function test_user_cannot_delete_another_users_saved_passenger(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $passenger = SavedPassenger::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($stranger)->delete(route('saved-passengers.destroy', $passenger->id));

        $response->assertNotFound();
        $this->assertDatabaseHas('saved_passengers', ['id' => $passenger->id]);
    }

    public function test_destroy_removes_own_saved_passenger(): void
    {
        $user = User::factory()->create();
        $passenger = SavedPassenger::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('saved-passengers.destroy', $passenger->id));

        $response->assertRedirect(route('saved-passengers.index'));
        $this->assertDatabaseMissing('saved_passengers', ['id' => $passenger->id]);
    }

    public function test_guest_is_redirected_from_all_routes(): void
    {
        $response = $this->get(route('saved-passengers.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_store_saves_full_passport_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('saved-passengers.store'), [
            'full_name' => 'Nguyen Van A',
            'passenger_type_default' => 'adult',
            'document_type' => 'passport',
            'document_number' => 'C1234567',
            'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
            'gender' => 'male',
            'nationality' => 'VNM',
            'document_issued_country' => 'VNM',
            'document_issue_date' => now()->subYears(2)->format('Y-m-d'),
            'document_expiry_date' => now()->addYears(8)->format('Y-m-d'),
        ])->assertRedirect(route('saved-passengers.index'));

        $saved = SavedPassenger::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('passport', $saved->document_type);
        $this->assertSame('C1234567', $saved->document_number);
        $this->assertSame('male', $saved->gender);
        $this->assertSame('VNM', $saved->nationality);
        $this->assertSame('VNM', $saved->document_issued_country);
        $this->assertSame(now()->subYears(2)->format('Y-m-d'), $saved->document_issue_date->format('Y-m-d'));
        $this->assertSame(now()->addYears(8)->format('Y-m-d'), $saved->document_expiry_date->format('Y-m-d'));
        $this->assertSame('Vietnam (VNM)', $saved->nationality_label);
        $this->assertSame('Nam', $saved->gender_label);
    }

    public function test_store_rejects_unknown_country_code(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('saved-passengers.store'), [
            'full_name' => 'Nguyen Van A',
            'passenger_type_default' => 'adult',
            'nationality' => 'XXX',
            'document_issued_country' => 'XXX',
        ]);

        $response->assertSessionHasErrors(['nationality', 'document_issued_country']);
        $this->assertDatabaseCount('saved_passengers', 0);
    }

    public function test_edit_page_opens_for_legacy_profile_with_free_text_nationality(): void
    {
        $user = User::factory()->create();
        $passenger = SavedPassenger::factory()->create([
            'user_id' => $user->id,
            'nationality' => 'Vietnam',
            'document_issued_country' => null,
            'gender' => null,
        ]);

        $this->actingAs($user)
            ->get(route('saved-passengers.edit', $passenger->id))
            ->assertOk();
    }

    public function test_store_without_passport_fields_still_succeeds(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('saved-passengers.store'), [
            'full_name' => 'Chi Co Truong Cu',
            'passenger_type_default' => 'child',
        ])->assertRedirect(route('saved-passengers.index'));

        $this->assertDatabaseHas('saved_passengers', [
            'user_id' => $user->id,
            'full_name' => 'Chi Co Truong Cu',
            'nationality' => null,
        ]);
    }
}