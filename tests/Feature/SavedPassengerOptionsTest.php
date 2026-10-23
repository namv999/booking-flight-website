<?php

namespace Tests\Feature;

use App\Models\SavedPassenger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedPassengerOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('saved-passengers.options'))
            ->assertRedirect(route('login'));
    }

    public function test_user_sees_own_saved_passengers(): void
    {
        $user = User::factory()->create();
        $mine = SavedPassenger::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Nguyen Van A',
        ]);

        $response = $this->actingAs($user)->getJson(route('saved-passengers.options'));

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $mine->id)
            ->assertJsonPath('0.full_name', 'Nguyen Van A');
    }

    public function test_other_users_saved_passengers_are_not_exposed(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        SavedPassenger::factory()->create(['user_id' => $userA->id, 'full_name' => 'Of User A']);
        SavedPassenger::factory()->create([
            'user_id' => $userB->id,
            'full_name' => 'Secret Of User B',
            'document_number' => 'B9999999',
        ]);

        $response = $this->actingAs($userA)->getJson(route('saved-passengers.options'));

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonMissing(['full_name' => 'Secret Of User B'])
            ->assertJsonMissing(['document_number' => 'B9999999']);
    }

    public function test_json_structure_and_date_format(): void
    {
        $user = User::factory()->create();
        $dob = now()->subYears(30);
        $issue = now()->subYears(2);
        $expiry = now()->addYears(8);

        SavedPassenger::factory()->create([
            'user_id' => $user->id,
            'document_type' => 'passport',
            'document_number' => 'C1234567',
            'date_of_birth' => $dob,
            'gender' => 'male',
            'nationality' => 'VNM',
            'document_issued_country' => 'VNM',
            'document_issue_date' => $issue,
            'document_expiry_date' => $expiry,
            'relationship' => 'self',
        ]);

        $response = $this->actingAs($user)->getJson(route('saved-passengers.options'));

        $response->assertOk()->assertJsonStructure([
            '*' => [
                'id',
                'full_name',
                'passenger_type_default',
                'document_type',
                'document_number',
                'date_of_birth',
                'gender',
                'nationality',
                'document_issued_country',
                'document_issue_date',
                'document_expiry_date',
                'relationship',
            ],
        ]);

        $item = $response->json('0');
        $this->assertSame($dob->format('Y-m-d'), $item['date_of_birth']);
        $this->assertSame($issue->format('Y-m-d'), $item['document_issue_date']);
        $this->assertSame($expiry->format('Y-m-d'), $item['document_expiry_date']);
        $this->assertSame('male', $item['gender']);
        $this->assertSame('VNM', $item['nationality']);
    }

    public function test_profile_without_passport_returns_null_fields(): void
    {
        $user = User::factory()->create();
        SavedPassenger::factory()->create([
            'user_id' => $user->id,
            'document_expiry_date' => null,
            'document_issue_date' => null,
        ]);

        $item = $this->actingAs($user)->getJson(route('saved-passengers.options'))->json('0');

        $this->assertNull($item['document_issue_date']);
        $this->assertNull($item['document_expiry_date']);
    }

    public function test_user_with_no_saved_passengers_gets_empty_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('saved-passengers.options'));

        $response->assertOk();
        $this->assertSame([], $response->json());
    }

    public function test_results_are_ordered_by_full_name(): void
    {
        $user = User::factory()->create();
        SavedPassenger::factory()->create(['user_id' => $user->id, 'full_name' => 'Tran B']);
        SavedPassenger::factory()->create(['user_id' => $user->id, 'full_name' => 'Le A']);

        $names = collect($this->actingAs($user)->getJson(route('saved-passengers.options'))->json())
            ->pluck('full_name')->all();

        $this->assertSame(['Le A', 'Tran B'], $names);
    }
}