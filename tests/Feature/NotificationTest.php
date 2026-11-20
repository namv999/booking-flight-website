<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function make(User $user, array $data = [], $at = null, $readAt = null): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => AppNotification::class,
            'data' => array_merge([
                'type' => AppNotification::ANNOUNCEMENT,
                'title' => 'Tiêu đề',
                'message' => 'Nội dung',
                'url' => '/news',
            ], $data),
            'read_at' => $readAt,
            'created_at' => $at ?? now(),
            'updated_at' => $at ?? now(),
        ]);
    }

    public function test_guest_cannot_use_notification_endpoints(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
        $this->getJson(route('notifications.feed'))->assertUnauthorized();
        $this->post(route('notifications.read-all'))->assertRedirect(route('login'));
        $this->postJson(route('notifications.read', (string) Str::uuid()))->assertUnauthorized();
    }

    public function test_feed_returns_own_latest_items_and_unread_count(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        foreach (range(0, 9) as $i) {
            $this->make($user, ['title' => "N{$i}"], now()->subMinutes($i), $i >= 7 ? now() : null);
        }
        $this->make($other, ['title' => 'Của người khác']);

        $this->actingAs($user)->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('unread_count', 7)
            ->assertJsonCount(8, 'items')
            ->assertJsonPath('items.0.title', 'N0')
            ->assertJsonPath('items.0.read', false)
            ->assertJsonPath('items.7.title', 'N7')
            ->assertJsonPath('items.7.read', true);
    }

    public function test_feed_urls_are_relative(): void
    {
        $user = User::factory()->create();
        $this->make($user);

        $item = $this->actingAs($user)->getJson(route('notifications.feed'))->json('items.0');

        $this->assertStringStartsWith('/notifications/', $item['read_url']);
        $this->assertSame('/news', $item['url']);
    }

    public function test_read_marks_notification_and_redirects_to_its_url(): void
    {
        $user = User::factory()->create();
        $n = $this->make($user, ['url' => '/news']);

        $this->actingAs($user)->post(route('notifications.read', $n->id))->assertRedirect('/news');
        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_read_returns_json_when_requested(): void
    {
        $user = User::factory()->create();
        $n = $this->make($user);
        $this->make($user);

        $this->actingAs($user)->postJson(route('notifications.read', $n->id))
            ->assertOk()
            ->assertJsonPath('url', '/news')
            ->assertJsonPath('unread_count', 1);
    }

    public function test_cannot_read_someone_elses_notification(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $n = $this->make($owner);

        $this->actingAs($intruder)->post(route('notifications.read', $n->id))->assertNotFound();
        $this->assertNull($n->fresh()->read_at);
    }

    public function test_unsafe_target_urls_fall_back_to_the_notification_page(): void
    {
        $user = User::factory()->create();

        foreach (['https://evil.example/x', '//evil.example', 'javascript:alert(1)', null] as $bad) {
            $n = $this->make($user, ['url' => $bad]);
            $this->actingAs($user)->post(route('notifications.read', $n->id))
                ->assertRedirect(route('notifications.index'));
        }
    }

    public function test_read_all_only_affects_the_current_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->make($user);
        $this->make($user);
        $this->make($other);

        $this->actingAs($user)->post(route('notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
    }

    public function test_index_filters_unread_and_paginates(): void
    {
        $user = User::factory()->create();

        foreach (range(0, 15) as $i) {
            $this->make($user, [], now()->subMinutes($i), $i < 4 ? now() : null);
        }

        $all = $this->actingAs($user)->get(route('notifications.index'))->assertOk();
        $this->assertCount(15, $all->viewData('notifications'));
        $this->assertSame(12, $all->viewData('unreadCount'));

        $unread = $this->actingAs($user)->get(route('notifications.index', ['filter' => 'unread']))->assertOk();
        $this->assertSame(12, $unread->viewData('notifications')->total());
    }

    public function test_index_opens_for_both_roles(): void
    {
        $this->actingAs(User::factory()->create())->get(route('notifications.index'))->assertOk();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('notifications.index'))->assertOk();
    }
}
