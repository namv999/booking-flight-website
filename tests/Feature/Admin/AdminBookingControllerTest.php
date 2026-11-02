<?php

namespace Tests\Feature\Admin;

use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\BaggageAddon;
use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Passenger;
use App\Models\Payment;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminBookingControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // Tên và email cố định, không chứa chữ số: tránh tìm kiếm theo id / mã vé khớp nhầm ngẫu nhiên
    private function customer(string $name, string $email): User
    {
        return User::factory()->create(['name' => $name, 'email' => $email]);
    }

    // Thêm một chặng vào booking, kèm vé cho từng hành khách. Giờ bay tính từ $daysAhead (ngày tương đối)
    private function addLeg(Booking $booking, array $passengers, int $daysAhead = 10, ?Flight $flight = null, array $ticketOverrides = []): BookingFlight
    {
        $flight ??= Flight::factory()->create([
            'departure_time' => now()->utc()->addDays($daysAhead)->setTime(9, 0),
            'arrival_time' => now()->utc()->addDays($daysAhead)->setTime(11, 0),
        ]);
        $bookingFlight = BookingFlight::factory()->create(['booking_id' => $booking->id, 'flight_id' => $flight->id]);

        foreach ($passengers as $n => $passenger) {
            $seat = Seat::factory()->create(['aircraft_id' => $flight->aircraft_id, 'seat_number' => (10 + $n).'A']);
            $flightSeat = FlightSeat::factory()->create(['flight_id' => $flight->id, 'seat_id' => $seat->id]);
            Ticket::factory()->create(array_merge([
                'booking_flight_id' => $bookingFlight->id,
                'passenger_id' => $passenger->id,
                'flight_seat_id' => $flightSeat->id,
            ], $ticketOverrides));
        }

        return $bookingFlight;
    }

    private function bookingWithOneLeg(array $bookingState = []): Booking
    {
        $booking = Booking::factory()->create($bookingState);
        $passenger = Passenger::factory()->create(['booking_id' => $booking->id]);
        $this->addLeg($booking, [$passenger]);

        return $booking;
    }

    public function test_admin_can_view_index_and_show(): void
    {
        $booking = $this->bookingWithOneLeg();
        Payment::factory()->create(['booking_id' => $booking->id]);

        $this->actingAs($this->admin())->get(route('admin.bookings.index'))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('#BK-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $booking = $this->bookingWithOneLeg();

        $this->actingAs($user)->get(route('admin.bookings.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.bookings.show', $booking))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $booking = $this->bookingWithOneLeg();

        $this->get(route('admin.bookings.index'))->assertRedirect(route('login'));
        $this->get(route('admin.bookings.show', $booking))->assertRedirect(route('login'));
    }

    public function test_admin_has_no_write_routes_for_bookings(): void
    {
        $booking = $this->bookingWithOneLeg();
        $admin = $this->admin();

        $this->actingAs($admin)->delete('/admin/bookings/'.$booking->id)->assertStatus(405);
        $this->actingAs($admin)->put('/admin/bookings/'.$booking->id)->assertStatus(405);
        $this->actingAs($admin)->post('/admin/bookings')->assertStatus(405);
    }

    public function test_show_returns_404_for_missing_booking(): void
    {
        $this->actingAs($this->admin())->get(route('admin.bookings.show', 999999))->assertNotFound();
    }

    public function test_search_by_booking_id(): void
    {
        $alice = $this->customer('Alice Tran', 'alice@example.test');
        $bob = $this->customer('Bob Le', 'bob@example.test');
        $target = Booking::factory()->create(['user_id' => $alice->id]);
        Booking::factory()->create(['user_id' => $bob->id]);
        $admin = $this->admin();

        foreach ([(string) $target->id, 'BK-'.str_pad($target->id, 5, '0', STR_PAD_LEFT), '#BK-'.str_pad($target->id, 5, '0', STR_PAD_LEFT)] as $term) {
            $bookings = $this->actingAs($admin)->get(route('admin.bookings.index', ['search' => $term]))
                ->assertOk()->viewData('bookings');

            $this->assertCount(1, $bookings, "Tìm theo '{$term}'");
            $this->assertSame($target->id, $bookings->first()->id);
        }
    }

    public function test_search_by_email_and_name(): void
    {
        $alice = $this->customer('Alice Tran', 'alice@example.test');
        $bob = $this->customer('Bob Le', 'bob@example.test');
        Booking::factory()->create(['user_id' => $alice->id]);
        Booking::factory()->create(['user_id' => $bob->id]);
        $admin = $this->admin();

        $byEmail = $this->actingAs($admin)->get(route('admin.bookings.index', ['search' => 'alice@']))->viewData('bookings');
        $this->assertCount(1, $byEmail);
        $this->assertSame($alice->id, $byEmail->first()->user_id);

        $byName = $this->actingAs($admin)->get(route('admin.bookings.index', ['search' => 'Bob']))->viewData('bookings');
        $this->assertCount(1, $byName);
        $this->assertSame($bob->id, $byName->first()->user_id);
    }

    public function test_search_by_ticket_code(): void
    {
        $alice = $this->customer('Alice Tran', 'alice@example.test');
        $bob = $this->customer('Bob Le', 'bob@example.test');
        $target = Booking::factory()->create(['user_id' => $alice->id]);
        $other = Booking::factory()->create(['user_id' => $bob->id]);
        $this->addLeg($target, [Passenger::factory()->create(['booking_id' => $target->id])], 10, null, ['ticket_code' => 'ZQXWVUTS']);
        $this->addLeg($other, [Passenger::factory()->create(['booking_id' => $other->id])], 10, null, ['ticket_code' => 'MNBVCXAB']);

        $bookings = $this->actingAs($this->admin())
            ->get(route('admin.bookings.index', ['search' => 'ZQXWVU']))
            ->assertOk()->viewData('bookings');

        $this->assertCount(1, $bookings);
        $this->assertSame($target->id, $bookings->first()->id);
    }

    public function test_filter_by_status(): void
    {
        Booking::factory()->count(2)->paid()->create();
        Booking::factory()->count(3)->cancelled()->create();
        Booking::factory()->create();
        $admin = $this->admin();

        $this->assertCount(2, $this->actingAs($admin)->get(route('admin.bookings.index', ['status' => 'paid']))->viewData('bookings'));
        $this->assertCount(3, $this->actingAs($admin)->get(route('admin.bookings.index', ['status' => 'cancelled']))->viewData('bookings'));
        $this->assertCount(1, $this->actingAs($admin)->get(route('admin.bookings.index', ['status' => 'pending']))->viewData('bookings'));
        // status lạ bị bỏ qua, không lỗi và không lọc
        $this->assertCount(6, $this->actingAs($admin)->get(route('admin.bookings.index', ['status' => 'hack']))->viewData('bookings'));
    }

    public function test_search_does_not_leak_past_status_filter(): void
    {
        // Nếu orWhere không bọc trong where(function), đơn pending của Bob lọt vào kết quả lọc 'paid'
        $alice = $this->customer('Alice Tran', 'alice@example.test');
        $bob = $this->customer('Bob Le', 'bob@example.test');
        Booking::factory()->paid()->create(['user_id' => $alice->id]);
        Booking::factory()->create(['user_id' => $bob->id]);

        $bookings = $this->actingAs($this->admin())
            ->get(route('admin.bookings.index', ['status' => 'paid', 'search' => 'bob']))
            ->assertOk()->viewData('bookings');

        $this->assertCount(0, $bookings);
    }

    public function test_index_paginates_ten_per_page_and_keeps_filters(): void
    {
        Booking::factory()->count(12)->paid()->create();
        Booking::factory()->count(2)->create();
        $admin = $this->admin();

        $page1 = $this->actingAs($admin)->get(route('admin.bookings.index', ['status' => 'paid']))->viewData('bookings');
        $this->assertCount(10, $page1);
        $this->assertSame(12, $page1->total());

        $page2 = $this->actingAs($admin)->get(route('admin.bookings.index', ['status' => 'paid', 'page' => 2]))->viewData('bookings');
        $this->assertCount(2, $page2);

        $this->actingAs($admin)->get(route('admin.bookings.index', ['status' => 'paid']))
            ->assertSee('status=paid', false);
    }

    public function test_show_survives_soft_deleted_airline_airport_and_aircraft(): void
    {
        $airline = Airline::factory()->create(['name' => 'Hang Bay Da Xoa']);
        $aircraft = Aircraft::factory()->create(['airline_id' => $airline->id, 'model' => 'Boeing 787']);
        $dep = Airport::factory()->create(['iata_code' => 'AAA', 'city' => 'Thanh Pho Di']);
        $arr = Airport::factory()->create(['iata_code' => 'BBB', 'city' => 'Thanh Pho Den']);
        $flight = Flight::factory()->create([
            'aircraft_id' => $aircraft->id,
            'departure_airport_id' => $dep->id,
            'arrival_airport_id' => $arr->id,
        ]);
        $booking = Booking::factory()->paid()->create();
        $this->addLeg($booking, [Passenger::factory()->create(['booking_id' => $booking->id])], 10, $flight);

        $airline->delete();
        $aircraft->delete();
        $dep->delete();
        $arr->delete();

        $this->actingAs($this->admin())->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Hang Bay Da Xoa')
            ->assertSee('Boeing 787')
            ->assertSee('AAA')
            ->assertSee('BBB');
    }

    public function test_show_displays_soft_deleted_baggage_addon_name(): void
    {
        $addon = BaggageAddon::create(['name' => 'Goi 15kg Cu', 'weight_kg' => 15, 'price' => 200000]);
        $booking = Booking::factory()->paid()->create();
        $passenger = Passenger::factory()->create(['booking_id' => $booking->id]);
        $this->addLeg($booking, [$passenger], 10, null, ['baggage_addon_id' => $addon->id]);

        $addon->delete();

        $this->actingAs($this->admin())->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Goi 15kg Cu')
            ->assertSee('15kg');
    }

    public function test_show_displays_passport_details(): void
    {
        $booking = Booking::factory()->paid()->create();
        $passenger = Passenger::factory()->create([
            'booking_id' => $booking->id,
            'full_name' => 'NGUYEN VAN A',
            'document_type' => 'passport',
            'document_number' => 'C1234567',
            'nationality' => 'VNM',
            'document_issued_country' => 'THA',
            'gender' => 'male',
            'document_issue_date' => '2020-01-15',
            'document_expiry_date' => '2030-01-14',
        ]);
        $this->addLeg($booking, [$passenger]);

        $this->actingAs($this->admin())->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('C1234567')
            ->assertSee('Vietnam (VNM)')
            ->assertSee('Thailand (THA)')
            ->assertSee('Nam')
            ->assertSee('15/01/2020')
            ->assertSee('14/01/2030');
    }

    public function test_show_handles_legacy_passport_without_dates(): void
    {
        $booking = Booking::factory()->paid()->create();
        $passenger = Passenger::factory()->create([
            'booking_id' => $booking->id,
            'document_type' => 'passport',
            'document_number' => 'B7654321',
            'nationality' => null,
            'document_issued_country' => null,
            'gender' => null,
            'document_issue_date' => null,
            'document_expiry_date' => null,
        ]);
        $this->addLeg($booking, [$passenger]);

        $this->actingAs($this->admin())->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('B7654321');
    }

    public function test_show_orders_three_legs_by_departure_time_with_leg_labels(): void
    {
        $booking = Booking::factory()->paid()->create();
        $passenger = Passenger::factory()->create(['booking_id' => $booking->id]);

        // Open-jaw: điểm đi chặng sau KHÁC điểm đến chặng trước
        $a = Airport::factory()->create(['iata_code' => 'AAA']);
        $b = Airport::factory()->create(['iata_code' => 'BBB']);
        $c = Airport::factory()->create(['iata_code' => 'CCC']);
        $d = Airport::factory()->create(['iata_code' => 'DDD']);
        $e = Airport::factory()->create(['iata_code' => 'EEE']);
        $f = Airport::factory()->create(['iata_code' => 'FFF']);

        $mk = fn (Airport $from, Airport $to, int $days) => Flight::factory()->create([
            'departure_airport_id' => $from->id,
            'arrival_airport_id' => $to->id,
            'departure_time' => now()->utc()->addDays($days)->setTime(9, 0),
            'arrival_time' => now()->utc()->addDays($days)->setTime(11, 0),
        ]);
        // flights.id VÀ booking_flights.id đều ngược thứ tự bay (chặng muộn nhất tạo trước), nên chỉ
        // sắp theo departure_time mới ra đúng thứ tự; sắp theo id (hoặc theo index DB) sẽ sai
        $leg3 = $mk($e, $f, 14);
        $leg1 = $mk($a, $b, 10);
        $leg2 = $mk($c, $d, 12);

        $this->addLeg($booking, [$passenger], 0, $leg3);
        $this->addLeg($booking, [$passenger], 0, $leg1);
        $this->addLeg($booking, [$passenger], 0, $leg2);

        $html = $this->actingAs($this->admin())->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Chặng 1')->assertSee('Chặng 2')->assertSee('Chặng 3')
            ->assertDontSee('Chặng 4')
            ->assertDontSee('Chuyến đi')
            ->getContent();

        $pos = fn (string $needle) => strpos($html, $needle);
        $this->assertLessThan($pos('Chặng 2'), $pos('Chặng 1'));
        $this->assertLessThan($pos('Chặng 3'), $pos('Chặng 2'));
        // chặng 1 là AAA → BBB, chặng 3 là EEE → FFF
        $this->assertLessThan($pos('FFF'), $pos('AAA'));
        $this->assertLessThan($pos('EEE'), $pos('CCC'));
    }

    public function test_show_labels_round_trip_and_single_leg(): void
    {
        $a = Airport::factory()->create(['iata_code' => 'AAA']);
        $b = Airport::factory()->create(['iata_code' => 'BBB']);
        $mk = fn (Airport $from, Airport $to, int $days) => Flight::factory()->create([
            'departure_airport_id' => $from->id,
            'arrival_airport_id' => $to->id,
            'departure_time' => now()->utc()->addDays($days)->setTime(9, 0),
            'arrival_time' => now()->utc()->addDays($days)->setTime(11, 0),
        ]);

        $round = Booking::factory()->paid()->create();
        $p = Passenger::factory()->create(['booking_id' => $round->id]);
        $this->addLeg($round, [$p], 0, $mk($b, $a, 15)); // chặng về tạo trước
        $this->addLeg($round, [$p], 0, $mk($a, $b, 10));

        $html = $this->actingAs($this->admin())->get(route('admin.bookings.show', $round))
            ->assertOk()->assertSee('Chuyến đi')->assertSee('Chuyến về')->assertDontSee('Chặng 1')
            ->getContent();
        // Chuyến đi là AAA → BBB (bay trước) nên AAA phải xuất hiện trước BBB
        $this->assertLessThan(strpos($html, 'BBB'), strpos($html, 'AAA'));

        $single = $this->bookingWithOneLeg();
        $this->actingAs($this->admin())->get(route('admin.bookings.show', $single))
            ->assertOk()->assertDontSee('Chuyến đi')->assertDontSee('Chặng 1');
    }

    public function test_two_leg_trip_that_is_not_a_reversal_uses_numbered_labels(): void
    {
        $booking = Booking::factory()->paid()->create();
        $p = Passenger::factory()->create(['booking_id' => $booking->id]);
        $this->addLeg($booking, [$p], 10);
        $this->addLeg($booking, [$p], 12);

        $this->actingAs($this->admin())->get(route('admin.bookings.show', $booking))
            ->assertOk()->assertSee('Chặng 1')->assertSee('Chặng 2')->assertDontSee('Chuyến về');
    }

    public function test_show_displays_times_in_each_airports_timezone(): void
    {
        $dep = Airport::factory()->create(['timezone' => 'Asia/Ho_Chi_Minh']);
        $arr = Airport::factory()->create(['timezone' => 'Asia/Tokyo']);
        $day = now()->utc()->addDays(10);
        $flight = Flight::factory()->create([
            'departure_airport_id' => $dep->id,
            'arrival_airport_id' => $arr->id,
            'departure_time' => $day->copy()->setTime(2, 0),  // 09:00 giờ Việt Nam (UTC+7)
            'arrival_time' => $day->copy()->setTime(7, 30),   // 16:30 giờ Tokyo (UTC+9)
        ]);
        $booking = Booking::factory()->paid()->create();
        $this->addLeg($booking, [Passenger::factory()->create(['booking_id' => $booking->id])], 0, $flight);

        $this->actingAs($this->admin())->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('09:00 '.$day->format('d/m/Y'))
            ->assertSee('16:30 '.$day->format('d/m/Y'))
            ->assertSee('UTC+07:00')
            ->assertSee('UTC+09:00')
            ->assertSee('5h 30m');
    }

    public function test_show_survives_invalid_airport_timezone(): void
    {
        $dep = Airport::factory()->create(['timezone' => 'Khong/Hop_Le']);
        $flight = Flight::factory()->create(['departure_airport_id' => $dep->id]);
        $booking = Booking::factory()->paid()->create();
        $this->addLeg($booking, [Passenger::factory()->create(['booking_id' => $booking->id])], 0, $flight);

        $this->actingAs($this->admin())->get(route('admin.bookings.show', $booking))->assertOk();
    }

    public function test_show_lists_infant_with_companion_and_no_seat(): void
    {
        $booking = Booking::factory()->paid()->create();
        $adult = Passenger::factory()->create(['booking_id' => $booking->id, 'full_name' => 'Nguoi Lon Mot']);
        $infant = Passenger::factory()->infant()->create(['booking_id' => $booking->id, 'full_name' => 'Em Be Mot']);
        $bf = $this->addLeg($booking, [$adult]);
        Ticket::factory()->infantOf($adult, 1500000)->create([
            'booking_flight_id' => $bf->id,
            'passenger_id' => $infant->id,
        ]);

        $this->actingAs($this->admin())->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Em Be Mot')
            ->assertSee('Đi cùng Nguoi Lon Mot')
            ->assertSee('Ngồi cùng người lớn');
    }

    public function test_show_does_not_run_n_plus_one_queries(): void
    {
        $countQueries = function (Booking $booking): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($this->admin())->get(route('admin.bookings.show', $booking))->assertOk();
            $n = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $n;
        };

        $small = Booking::factory()->paid()->create();
        $this->addLeg($small, [Passenger::factory()->create(['booking_id' => $small->id])]);

        $big = Booking::factory()->paid()->create();
        $passengers = Passenger::factory()->count(6)->create(['booking_id' => $big->id]);
        foreach ([10, 12, 14] as $days) {
            $this->addLeg($big, $passengers->all(), $days);
        }

        $this->assertSame($countQueries($small), $countQueries($big));
    }

    public function test_index_does_not_run_n_plus_one_queries(): void
    {
        Booking::factory()->count(2)->create();
        $admin = $this->admin();

        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('admin.bookings.index'))->assertOk();
        $few = count(DB::getQueryLog());

        Booking::factory()->count(8)->create();
        DB::flushQueryLog();
        $this->actingAs($admin)->get(route('admin.bookings.index'))->assertOk();
        $many = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($few, $many);
    }
}
