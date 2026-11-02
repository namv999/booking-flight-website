<?php

namespace Tests\Feature\Admin;

use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSidebarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // Trang nền để đọc sidebar. KHÔNG dùng admin.dashboard: DashboardController gọi MONTH() của MySQL
    // nên không render được trên SQLite của môi trường test (trang đó chỉ kiểm tra thủ công ở P4)
    private function page(User $admin): string
    {
        return $this->actingAs($admin)->get(route('admin.airlines.index'))->assertOk()->getContent();
    }

    // Lấy các href (và nhãn) trong thanh điều hướng của sidebar
    private function sidebarLinks(string $html): array
    {
        preg_match('/<nav class="bf-admin-nav".*?<\/nav>/s', $html, $nav);
        $this->assertNotEmpty($nav, 'Không thấy sidebar trong layout admin');
        preg_match_all('/<a class="([^"]*)" href="([^"]*)">.*?<\/span>([^<]+)<\/a>/s', $nav[0], $m, PREG_SET_ORDER);

        return array_map(fn ($x) => ['class' => $x[1], 'href' => $x[2], 'label' => trim($x[3])], $m);
    }

    public function test_layout_has_no_dead_links(): void
    {
        $html = $this->page($this->admin());

        $this->assertStringNotContainsString('href="#"', $html);
    }

    public function test_sidebar_lists_every_real_admin_section_in_order(): void
    {
        $html = $this->page($this->admin());
        $links = $this->sidebarLinks($html);

        $this->assertSame(
            ['Tổng quan', 'Chuyến bay', 'Đặt vé', 'Hãng bay', 'Máy bay', 'Sân bay', 'Hạng vé', 'Gói hành lý'],
            array_column($links, 'label')
        );
        $this->assertSame([
            route('admin.dashboard'),
            route('admin.flights.index'),
            route('admin.bookings.index'),
            route('admin.airlines.index'),
            route('admin.aircrafts.index'),
            route('admin.airports.index'),
            route('admin.fare-classes.index'),
            route('admin.baggage-addons.index'),
        ], array_column($links, 'href'));
    }

    public function test_sidebar_numbers_are_sequential(): void
    {
        $html = $this->page($this->admin());

        preg_match('/<nav class="bf-admin-nav".*?<\/nav>/s', $html, $nav);
        preg_match_all('/<span aria-hidden="true">(\d+)<\/span>/', $nav[0], $m);

        $this->assertSame(['01', '02', '03', '04', '05', '06', '07', '08'], $m[1]);
    }

    public function test_every_sidebar_link_and_avatar_opens_a_real_page(): void
    {
        $admin = $this->admin();
        $html = $this->page($admin);

        foreach ($this->sidebarLinks($html) as $link) {
            $this->actingAs($admin)->get($link['href'])->assertOk();
        }

        $this->assertStringContainsString('class="bf-avatar" href="'.route('profile.edit').'"', $html);
        $this->actingAs($admin)->get(route('profile.edit'))->assertOk();
    }

    public function test_removed_placeholder_items_are_gone(): void
    {
        $html = $this->page($this->admin());

        foreach (['Khách hàng', 'Khuyến mãi', 'Báo cáo'] as $label) {
            $this->assertStringNotContainsString($label, $html);
        }
        $this->assertStringNotContainsString('<a href="#">Mở trung tâm hỗ trợ</a>', $html);
        $this->assertStringContainsString('Mở trung tâm hỗ trợ', $html);
    }

    public function test_only_the_current_section_is_marked_active(): void
    {
        $admin = $this->admin();
        $airline = Airline::factory()->create();
        $aircraft = Aircraft::factory()->create();
        $airport = Airport::factory()->create();
        $fare = FareClass::factory()->create();
        $flight = Flight::factory()->create();

        $pages = [
            'Chuyến bay' => route('admin.flights.index'),
            'Đặt vé' => route('admin.bookings.index'),
            'Hãng bay' => route('admin.airlines.index'),
            'Máy bay' => route('admin.aircrafts.index'),
            'Sân bay' => route('admin.airports.index'),
            'Hạng vé' => route('admin.fare-classes.index'),
            'Gói hành lý' => route('admin.baggage-addons.index'),
            // trang con (show / edit) vẫn đánh dấu đúng mục cha
            'Hãng bay ' => route('admin.airlines.edit', $airline),
            'Máy bay ' => route('admin.aircrafts.show', $aircraft),
            'Sân bay ' => route('admin.airports.edit', $airport),
            'Hạng vé ' => route('admin.fare-classes.show', $fare),
            'Chuyến bay ' => route('admin.flights.show', $flight),
        ];

        foreach ($pages as $label => $url) {
            $links = $this->sidebarLinks($this->actingAs($admin)->get($url)->assertOk()->getContent());
            $active = array_values(array_filter($links, fn ($l) => str_contains($l['class'], 'bf-admin-nav__link--active')));

            $this->assertCount(1, $active, "Trang {$url} phải có đúng 1 mục active");
            $this->assertSame(trim($label), $active[0]['label'], "Trang {$url}");
        }
    }
}
