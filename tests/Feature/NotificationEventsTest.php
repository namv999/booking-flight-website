<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\PassportUpdateRequest;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesInternationalBooking;
use Tests\TestCase;

class NotificationEventsTest extends TestCase
{
    use CreatesInternationalBooking;
    use RefreshDatabase;

    private function submitRequest(): array
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner);
        $passenger = $this->addPassenger($booking);

        $this->actingAs($owner)->post(
            route('booking-history.passport-update.store', [$booking->id, $passenger->id]),
            $this->validPassportPayload()
        )->assertSessionHas('passport_status');

        return [$owner, $booking, PassportUpdateRequest::firstOrFail()];
    }

    public function test_submitting_a_request_notifies_every_admin_only(): void
    {
        $admins = User::factory()->count(2)->create(['role' => 'admin']);
        $bystander = User::factory()->create();

        [$owner, $booking] = $this->submitRequest();

        foreach ($admins as $admin) {
            $n = $admin->notifications()->sole();
            $this->assertSame(AppNotification::PASSPORT_SUBMITTED, $n->data['type']);
            $this->assertStringContainsString('/admin/bookings/'.$booking->id, $n->data['url']);
            $this->assertStringContainsString('#passport-update-requests', $n->data['url']);
        }

        $this->assertSame(0, $owner->notifications()->count());
        $this->assertSame(0, $bystander->notifications()->count());
    }

    public function test_approving_notifies_the_requester(): void
    {
        [$owner, $booking, $request] = $this->submitRequest();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.passport-update-requests.approve', $request), ['admin_note' => 'OK']);

        $n = $owner->notifications()->sole();
        $this->assertSame(AppNotification::PASSPORT_APPROVED, $n->data['type']);
        $this->assertStringContainsString('/booking-history/'.$booking->id, $n->data['url']);
        $this->assertStringContainsString('#passport-update', $n->data['url']);
    }

    public function test_rejecting_notifies_the_requester_with_the_reason(): void
    {
        [$owner, , $request] = $this->submitRequest();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.passport-update-requests.reject', $request), ['admin_note' => 'Anh ho chieu bi mo']);

        $n = $owner->notifications()->sole();
        $this->assertSame(AppNotification::PASSPORT_REJECTED, $n->data['type']);
        $this->assertStringContainsString('Anh ho chieu bi mo', $n->data['message']);
    }

    public function test_an_already_resolved_request_does_not_notify_again(): void
    {
        [$owner, , $request] = $this->submitRequest();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.passport-update-requests.approve', $request));
        $this->actingAs($admin)->post(route('admin.passport-update-requests.approve', $request))
            ->assertSessionHas('passport_error');

        $this->assertSame(1, $owner->notifications()->count());
    }

    public function test_booking_paid_notifies_owner_and_admins(): void
    {
        $admins = User::factory()->count(2)->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $booking = Booking::factory()->paid()->create(['user_id' => $owner->id, 'total_amount' => 1500000]);

        app(NotificationService::class)->bookingPaid($booking);

        $mine = $owner->notifications()->sole();
        $this->assertSame(AppNotification::BOOKING_PAID, $mine->data['type']);
        $this->assertStringContainsString('1.500.000 VND', $mine->data['message']);
        $this->assertStringContainsString('/booking-history/'.$booking->id, $mine->data['url']);

        foreach ($admins as $admin) {
            $n = $admin->notifications()->sole();
            $this->assertSame(AppNotification::ORDER_PAID, $n->data['type']);
            $this->assertStringContainsString('/admin/bookings/'.$booking->id, $n->data['url']);
        }
    }
}
