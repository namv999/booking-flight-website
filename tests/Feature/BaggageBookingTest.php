<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\BaggageAddon;
use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Passenger;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class BaggageBookingTest extends TestCase
{
    use RefreshDatabase;

    private function addon(string $name = 'Goi 15kg', int $kg = 15, int $price = 200000): BaggageAddon
    {
        return BaggageAddon::create(['name' => $name, 'weight_kg' => $kg, 'price' => $price]);
    }

    private function flightAt(int $daysAhead): Flight
    {
        return Flight::factory()->create([
            'departure_time' => now('UTC')->addDays($daysAhead),
            'arrival_time' => now('UTC')->addDays($daysAhead)->addHours(2),
            'status' => 'scheduled',
        ]);
    }

    // Ghế available theo thứ tự giá; id tăng dần nên người lớn nhận ghế đầu, trẻ em ghế sau
    private function addSeats(Flight $flight, FareClass $fareClass, array $prices): void
    {
        foreach ($prices as $price) {
            FlightSeat::factory()->create([
                'flight_id' => $flight->id,
                'fare_class_id' => $fareClass->id,
                'price' => $price,
                'status' => 'available',
            ]);
        }
    }

    // Đặt vé nội địa 1 chặng: người lớn nhận ghế 1.500.000, trẻ em ghế 1.200.000
    private function domesticSetup(int $adults = 1, int $children = 0, int $infants = 0): array
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create(['checked_baggage_kg' => 10]);
        $flight = $this->flightAt(3);
        $this->addSeats($flight, $fareClass, array_merge(
            array_fill(0, $adults, 1500000),
            array_fill(0, $children, 1200000),
        ));
        session(['pending_selection' => $this->pendingSelection($flight, $fareClass, $adults, $children, $infants)]);

        return [$user, $flight, $fareClass];
    }

    private function submit(User $user, array $payload)
    {
        return $this->actingAs($user)->post(route('booking.passengers.store'), $payload);
    }

    private function ticketOf(string $name, ?int $flightId = null): Ticket
    {
        $query = Ticket::whereHas('passenger', fn ($q) => $q->where('full_name', $name));

        if ($flightId) {
            $query->whereHas('bookingFlight', fn ($q) => $q->where('flight_id', $flightId));
        }

        return $query->firstOrFail();
    }

    // ---------- Form ----------

    public function test_form_shows_baggage_select_for_adults_and_children_but_not_infants(): void
    {
        [$user] = $this->domesticSetup(1, 1, 1);
        $this->addon('Goi 15kg', 15, 200000);

        $response = $this->actingAs($user)->get(route('booking.passengers.form'))->assertOk();

        $response->assertSee('adults[0][baggage_addon_id]', false);
        $response->assertSee('children[0][baggage_addon_id]', false);
        $response->assertDontSee('infants[0][baggage_addon_id]', false);
        $response->assertSee('Không mua thêm');
        $response->assertSee('Goi 15kg (15kg) - 200.000 đ');
    }

    public function test_partial_renders_nothing_for_infants(): void
    {
        $data = ['errors' => new ViewErrorBag, 'i' => 0, 'baggageAddons' => collect([$this->addon()]), 'freeBaggageKg' => 10, 'pending' => ['legs' => [1]]];

        $this->assertSame('', trim(view('booking.partials.passenger-baggage', $data + ['group' => 'infants'])->render()));
        $this->assertStringContainsString('adults[0][baggage_addon_id]', view('booking.partials.passenger-baggage', $data + ['group' => 'adults'])->render());
        $this->assertStringContainsString('id="children_0_baggage"', view('booking.partials.passenger-baggage', $data + ['group' => 'children'])->render());
    }

    public function test_form_lists_addons_by_weight_and_hides_soft_deleted_ones(): void
    {
        [$user] = $this->domesticSetup();
        $this->addon('Goi 30kg', 30, 400000);
        $this->addon('Goi 15kg', 15, 200000);
        $this->addon('Goi Da Xoa', 20, 300000)->delete();

        $response = $this->actingAs($user)->get(route('booking.passengers.form'))->assertOk();

        $response->assertDontSee('Goi Da Xoa');
        $this->assertCount(2, $response->viewData('baggageAddons'));
        $this->assertSame([15, 30], $response->viewData('baggageAddons')->pluck('weight_kg')->all());
    }

    public function test_form_shows_free_allowance_of_the_selected_fare_class(): void
    {
        [$user] = $this->domesticSetup();
        $this->addon();

        $response = $this->actingAs($user)->get(route('booking.passengers.form'))->assertOk();

        $this->assertSame(10, $response->viewData('freeBaggageKg'));
        $response->assertSee('Hạng vé đã gồm 10kg ký gửi miễn phí.');
        $response->assertDontSee('/ chặng');
    }

    public function test_form_says_price_is_per_leg_for_multi_leg_journeys(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $out = $this->flightAt(2);
        $back = $this->flightAt(5);
        session(['pending_selection' => $this->pendingSelection([$out, $back], $fareClass, 1, 0, 0)]);
        $this->addon('Goi 15kg', 15, 200000);

        $this->actingAs($user)->get(route('booking.passengers.form'))->assertOk()
            ->assertSee('200.000 đ / chặng')
            ->assertSee('Giá gói tính cho mỗi chặng');
    }

    public function test_form_keeps_selected_addon_after_validation_error(): void
    {
        [$user] = $this->domesticSetup();
        $addon = $this->addon();

        $response = $this->from(route('booking.passengers.form'))
            ->actingAs($user)
            ->post(route('booking.passengers.store'), [
                'adults' => [['full_name' => '', 'baggage_addon_id' => $addon->id]],
            ]);
        $response->assertRedirect(route('booking.passengers.form'));
        $response->assertSessionHasErrors('adults.0.full_name');

        $this->actingAs($user)->get(route('booking.passengers.form'))->assertOk()
            ->assertSee('value="'.$addon->id.'" selected', false);
    }

    // ---------- Tính giá ----------

    public function test_no_addon_keeps_price_unchanged(): void
    {
        [$user] = $this->domesticSetup();

        $this->submit($user, ['adults' => [['full_name' => 'Adult One']]])->assertRedirect();

        $ticket = Ticket::firstOrFail();
        $this->assertEquals(1500000, $ticket->price);
        $this->assertNull($ticket->baggage_addon_id);
        $this->assertEquals(1500000, Booking::first()->total_amount);
    }

    public function test_empty_select_value_means_no_addon(): void
    {
        [$user] = $this->domesticSetup();

        $this->submit($user, ['adults' => [['full_name' => 'Adult One', 'baggage_addon_id' => '']]])->assertRedirect();

        $this->assertNull(Ticket::firstOrFail()->baggage_addon_id);
        $this->assertEquals(1500000, Ticket::firstOrFail()->price);
    }

    public function test_adult_addon_adds_price_and_is_recorded_on_ticket(): void
    {
        [$user] = $this->domesticSetup();
        $addon = $this->addon('Goi 15kg', 15, 200000);

        $this->submit($user, ['adults' => [['full_name' => 'Adult One', 'baggage_addon_id' => $addon->id]]])->assertRedirect();

        $ticket = Ticket::firstOrFail();
        $this->assertEquals(1700000, $ticket->price);       // ghế 1.500.000 + gói 200.000
        $this->assertSame($addon->id, $ticket->baggage_addon_id);
        $this->assertEquals(1700000, Booking::first()->total_amount);
    }

    public function test_child_addon_adds_price_too(): void
    {
        [$user] = $this->domesticSetup(1, 1);
        $addon = $this->addon('Goi 15kg', 15, 200000);

        $this->submit($user, [
            'adults' => [['full_name' => 'Adult One']],
            'children' => [['full_name' => 'Child One', 'baggage_addon_id' => $addon->id]],
        ])->assertRedirect();

        $adult = $this->ticketOf('Adult One');
        $child = $this->ticketOf('Child One');
        $this->assertEquals(1500000, $adult->price);
        $this->assertNull($adult->baggage_addon_id);
        $this->assertEquals(1400000, $child->price);        // ghế 1.200.000 + gói 200.000
        $this->assertSame($addon->id, $child->baggage_addon_id);
        $this->assertEquals(2900000, Booking::first()->total_amount);
    }

    public function test_each_passenger_can_pick_a_different_addon(): void
    {
        [$user] = $this->domesticSetup(2);
        $small = $this->addon('Goi 15kg', 15, 200000);
        $big = $this->addon('Goi 30kg', 30, 450000);

        $this->submit($user, ['adults' => [
            ['full_name' => 'Adult One', 'baggage_addon_id' => $small->id],
            ['full_name' => 'Adult Two', 'baggage_addon_id' => $big->id],
        ]])->assertRedirect();

        $this->assertEquals(1700000, $this->ticketOf('Adult One')->price);
        $this->assertEquals(1950000, $this->ticketOf('Adult Two')->price);
        $this->assertSame($big->id, $this->ticketOf('Adult Two')->baggage_addon_id);
        $this->assertEquals(3650000, Booking::first()->total_amount);
    }

    // ---------- Em bé ----------

    public function test_infant_cannot_buy_addon_even_if_field_is_sent(): void
    {
        [$user] = $this->domesticSetup(1, 0, 1);
        $addon = $this->addon();

        $this->submit($user, [
            'adults' => [['full_name' => 'Adult One']],
            'infants' => [['full_name' => 'Infant One', 'companion_adult_index' => 0, 'baggage_addon_id' => $addon->id]],
        ])->assertRedirect();

        $infant = $this->ticketOf('Infant One');
        $this->assertNull($infant->baggage_addon_id);
        $this->assertEquals(150000, $infant->price);        // 10% của 1.500.000
        $this->assertEquals(1650000, Booking::first()->total_amount);
    }

    public function test_infant_price_ignores_the_companion_adults_addon(): void
    {
        [$user] = $this->domesticSetup(1, 0, 1);
        $addon = $this->addon('Goi 30kg', 30, 450000);

        $this->submit($user, [
            'adults' => [['full_name' => 'Adult One', 'baggage_addon_id' => $addon->id]],
            'infants' => [['full_name' => 'Infant One', 'companion_adult_index' => 0]],
        ])->assertRedirect();

        $adult = $this->ticketOf('Adult One');
        $infant = $this->ticketOf('Infant One');
        $this->assertEquals(1950000, $adult->price);        // đã cộng hành lý
        $this->assertEquals(150000, $infant->price);        // vẫn 10% GIÁ GHẾ GỐC 1.500.000, không phải 10% của 1.950.000
        $this->assertNull($infant->baggage_addon_id);
        $this->assertEquals(2100000, Booking::first()->total_amount);
    }

    // ---------- Nhiều chặng ----------

    public function test_round_trip_charges_the_addon_on_both_legs(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $out = $this->flightAt(2);
        $back = $this->flightAt(5);
        $this->addSeats($out, $fareClass, [1000000, 1100000]);
        $this->addSeats($back, $fareClass, [2000000, 2100000]);
        $adultAddon = $this->addon('Goi 15kg', 15, 200000);
        $childAddon = $this->addon('Goi 10kg', 10, 100000);

        session(['pending_selection' => $this->pendingSelection([$out, $back], $fareClass, 1, 1, 1)]);

        $this->submit($user, [
            'adults' => [['full_name' => 'Adult One', 'baggage_addon_id' => $adultAddon->id]],
            'children' => [['full_name' => 'Child One', 'baggage_addon_id' => $childAddon->id]],
            'infants' => [['full_name' => 'Infant One', 'companion_adult_index' => 0]],
        ])->assertRedirect();

        $this->assertDatabaseCount('tickets', 6);

        // Người lớn: ghế + 200.000 trên MỖI chặng
        $this->assertEquals(1200000, $this->ticketOf('Adult One', $out->id)->price);
        $this->assertEquals(2200000, $this->ticketOf('Adult One', $back->id)->price);
        foreach ([$out->id, $back->id] as $flightId) {
            $this->assertSame($adultAddon->id, $this->ticketOf('Adult One', $flightId)->baggage_addon_id);
            $this->assertSame($childAddon->id, $this->ticketOf('Child One', $flightId)->baggage_addon_id);
            $this->assertNull($this->ticketOf('Infant One', $flightId)->baggage_addon_id);
        }
        // Trẻ em: ghế + 100.000 trên mỗi chặng
        $this->assertEquals(1200000, $this->ticketOf('Child One', $out->id)->price);
        $this->assertEquals(2200000, $this->ticketOf('Child One', $back->id)->price);
        // Em bé: 10% giá ghế gốc người lớn từng chặng (không cộng hành lý)
        $this->assertEquals(100000, $this->ticketOf('Infant One', $out->id)->price);
        $this->assertEquals(200000, $this->ticketOf('Infant One', $back->id)->price);

        $this->assertEquals(7100000, Booking::first()->total_amount);
        $this->assertDatabaseCount('passengers', 3); // hành khách tạo MỘT lần, dùng chung mọi chặng
    }

    public function test_three_leg_journey_charges_the_addon_on_every_leg(): void
    {
        $user = User::factory()->create();
        $fareClass = FareClass::factory()->create();
        $legs = [$this->flightAt(2), $this->flightAt(5), $this->flightAt(8)];
        foreach ($legs as $n => $flight) {
            $this->addSeats($flight, $fareClass, [1000000 + $n * 100000]);
        }
        $addon = $this->addon('Goi 15kg', 15, 200000);

        session(['pending_selection' => $this->pendingSelection($legs, $fareClass, 1, 0, 0)]);

        $this->submit($user, ['adults' => [['full_name' => 'Adult One', 'baggage_addon_id' => $addon->id]]])->assertRedirect();

        $this->assertDatabaseCount('tickets', 3);
        $this->assertEquals(1200000, $this->ticketOf('Adult One', $legs[0]->id)->price);
        $this->assertEquals(1300000, $this->ticketOf('Adult One', $legs[1]->id)->price);
        $this->assertEquals(1400000, $this->ticketOf('Adult One', $legs[2]->id)->price);
        $this->assertSame(3, Ticket::where('baggage_addon_id', $addon->id)->count());
        $this->assertEquals(3900000, Booking::first()->total_amount);
    }

    // ---------- Quốc tế ----------

    public function test_international_journey_accepts_addon_with_full_passport_data(): void
    {
        $user = User::factory()->create();
        $flight = Flight::factory()->create([
            'departure_airport_id' => Airport::factory()->create()->id,
            'arrival_airport_id' => Airport::factory()->foreign()->create()->id,
        ]);
        $fareClass = FareClass::factory()->create();
        $this->addSeats($flight, $fareClass, [1500000]);
        $addon = $this->addon('Goi 15kg', 15, 200000);

        session(['pending_selection' => $this->pendingSelection($flight, $fareClass, 1, 0, 0)]);

        $this->submit($user, ['adults' => [[
            'full_name' => 'nguyen van a',
            'document_type' => 'passport',
            'document_number' => 'c1234567',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'nationality' => 'VNM',
            'document_issued_country' => 'VNM',
            'document_issue_date' => now()->subYears(2)->toDateString(),
            'document_expiry_date' => now()->addYears(8)->toDateString(),
            'baggage_addon_id' => $addon->id,
        ]]])->assertRedirect();

        $ticket = Ticket::firstOrFail();
        $this->assertEquals(1700000, $ticket->price);
        $this->assertSame($addon->id, $ticket->baggage_addon_id);
        $this->assertSame('NGUYEN VAN A', Passenger::first()->full_name); // chuẩn hóa hộ chiếu vẫn chạy
    }

    // ---------- Validation ----------

    public function test_soft_deleted_addon_is_rejected_and_nothing_is_created(): void
    {
        [$user] = $this->domesticSetup();
        $addon = $this->addon();
        $addon->delete();

        $this->submit($user, ['adults' => [['full_name' => 'Adult One', 'baggage_addon_id' => $addon->id]]])
            ->assertSessionHasErrors('adults.0.baggage_addon_id');

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertSame(0, FlightSeat::where('status', 'held')->count());
    }

    public function test_unknown_or_malformed_addon_id_is_rejected_for_adults_and_children(): void
    {
        [$user] = $this->domesticSetup(1, 1);

        $this->submit($user, [
            'adults' => [['full_name' => 'Adult One', 'baggage_addon_id' => 999999]],
            'children' => [['full_name' => 'Child One', 'baggage_addon_id' => 'abc']],
        ])->assertSessionHasErrors(['adults.0.baggage_addon_id', 'children.0.baggage_addon_id']);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_addon_deleted_between_validation_and_booking_asks_the_customer_to_choose_again(): void
    {
        [$user] = $this->domesticSetup();
        $addon = $this->addon();

        // Xóa mềm gói ngay sau lúc validate (query exists) xong, trước khi controller nạp gói
        $done = false;
        DB::listen(function ($query) use (&$done, $addon) {
            if (! $done && str_contains($query->sql, 'baggage_addons') && str_contains($query->sql, 'count(*)')) {
                $done = true;
                DB::table('baggage_addons')->where('id', $addon->id)->update(['deleted_at' => now()]);
            }
        });

        $this->submit($user, ['adults' => [['full_name' => 'Adult One', 'baggage_addon_id' => $addon->id]]])
            ->assertSessionHas('error', 'Gói hành lý bạn chọn không còn được cung cấp. Vui lòng chọn lại.');

        $this->assertTrue($done, 'Hook xóa gói phải chạy');
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertSame(0, FlightSeat::where('status', 'held')->count());
    }

    // ---------- Lịch sử đặt vé ----------

    public function test_booking_history_shows_addon_name_and_weight_but_not_its_price(): void
    {
        [$user] = $this->domesticSetup();
        $addon = $this->addon('Goi Hanh Ly 15kg', 15, 234567);

        $this->submit($user, ['adults' => [['full_name' => 'Adult One', 'baggage_addon_id' => $addon->id]]])->assertRedirect();
        $booking = Booking::firstOrFail();

        $response = $this->actingAs($user)->get(route('booking-history.show', $booking->id))->assertOk();
        $response->assertSee('Goi Hanh Ly 15kg (+15kg)');
        $response->assertDontSee('234.567'); // giá gói có thể bị admin đổi sau nên không hiển thị
    }

    public function test_booking_history_shows_standard_allowance_when_no_addon_was_bought(): void
    {
        [$user] = $this->domesticSetup();

        $this->submit($user, ['adults' => [['full_name' => 'Adult One']]])->assertRedirect();

        $this->actingAs($user)->get(route('booking-history.show', Booking::firstOrFail()->id))->assertOk()
            ->assertSee('Tiêu chuẩn (7kg xách tay)');
    }

    public function test_old_ticket_still_shows_a_soft_deleted_addon_in_history(): void
    {
        $user = User::factory()->create();
        $addon = $this->addon('Goi Cu 20kg', 20, 300000);
        $booking = Booking::factory()->paid()->create(['user_id' => $user->id]);
        $passenger = Passenger::factory()->create(['booking_id' => $booking->id]);
        $bookingFlight = BookingFlight::factory()->create(['booking_id' => $booking->id]);
        Ticket::factory()->create([
            'booking_flight_id' => $bookingFlight->id,
            'passenger_id' => $passenger->id,
            'baggage_addon_id' => $addon->id,
        ]);

        $addon->delete();

        $this->actingAs($user)->get(route('booking-history.show', $booking->id))->assertOk()
            ->assertSee('Goi Cu 20kg (+20kg)');
    }
}
