<?php

namespace Tests\Feature\Admin;

use App\Models\NotificationBroadcast;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationBroadcastControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'type' => 'promotion',
            'title' => 'Ưu đãi cuối năm',
            'message' => 'Giảm giá vé nội địa dịp cuối năm.',
            'url' => '/news',
        ], $override);
    }

    public function test_guest_and_regular_user_are_blocked(): void
    {
        $this->get(route('admin.notification-broadcasts.index'))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.notification-broadcasts.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.notification-broadcasts.store'), $this->payload())->assertForbidden();
    }

    public function test_store_sends_to_every_customer_but_not_to_admins(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();
        $customers = User::factory()->count(3)->create();

        $this->actingAs($admin)->post(route('admin.notification-broadcasts.store'), $this->payload())
            ->assertRedirect(route('admin.notification-broadcasts.index'))
            ->assertSessionHas('success');

        foreach ($customers as $customer) {
            $n = $customer->notifications()->sole();
            $this->assertSame(AppNotification::PROMOTION, $n->data['type']);
            $this->assertSame('Ưu đãi cuối năm', $n->data['title']);
            $this->assertSame('/news', $n->data['url']);
        }

        $this->assertSame(0, $admin->notifications()->count());
        $this->assertSame(0, $otherAdmin->notifications()->count());

        $record = NotificationBroadcast::sole();
        $this->assertSame(3, $record->recipient_count);
        $this->assertSame($admin->id, $record->admin_id);
    }

    public function test_url_is_optional_and_internal_paths_with_fragments_are_accepted(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.notification-broadcasts.store'), $this->payload(['url' => null]))
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.notification-broadcasts.store'), $this->payload(['url' => '/#search-box']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, NotificationBroadcast::count());
    }

    public function test_validation_rejects_bad_input(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.notification-broadcasts.store'), $this->payload([
            'type' => 'hack', 'title' => '', 'message' => '',
        ]))->assertSessionHasErrors(['type', 'title', 'message']);

        foreach (['//evil.example', 'javascript:alert(1)', 'https://evil.example', 'no-slash', '/co khoang trang'] as $bad) {
            $this->actingAs($admin)->post(route('admin.notification-broadcasts.store'), $this->payload(['url' => $bad]))
                ->assertSessionHasErrors('url');
        }

        $this->assertSame(0, NotificationBroadcast::count());
    }

    public function test_index_search_filter_and_pagination(): void
    {
        $admin = $this->admin();

        foreach (range(1, 11) as $i) {
            NotificationBroadcast::create([
                'admin_id' => $admin->id, 'type' => 'promotion',
                'title' => "Khuyến mãi số {$i}", 'message' => 'Nội dung', 'recipient_count' => 1,
            ]);
        }
        $special = NotificationBroadcast::create([
            'admin_id' => $admin->id, 'type' => 'announcement',
            'title' => 'Bảo trì hệ thống', 'message' => 'Nội dung', 'recipient_count' => 1,
        ]);

        $page = fn (array $query) => $this->actingAs($admin)
            ->get(route('admin.notification-broadcasts.index', $query))->assertOk()->viewData('broadcasts');

        $this->assertCount(10, $page([]));
        $this->assertSame([$special->id], $page(['search' => 'Bảo trì'])->pluck('id')->all());
        $this->assertSame([$special->id], $page(['type' => 'announcement'])->pluck('id')->all());
        $this->assertSame(11, $page(['type' => 'promotion'])->total());
    }
}
