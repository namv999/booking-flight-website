<?php

namespace Tests\Feature;

use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Seat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoundTripSearchTest extends TestCase
{
    use RefreshDatabase;

    private Airport $sgn;

    private Airport $han;

    private FareClass $fare;

    private $seats;

    private Aircraft $aircraft;

    private Carbon $day; // 00:00 UTC của ngày bay đi (SGN/HAN cùng UTC+7 nên 01:00 UTC = 08:00 local cùng ngày)

    protected function setUp(): void
    {
        parent::setUp();

        $vn = ['timezone' => 'Asia/Ho_Chi_Minh', 'country' => 'Vietnam'];
        $this->sgn = Airport::factory()->create($vn + ['iata_code' => 'SGN']);
        $this->han = Airport::factory()->create($vn + ['iata_code' => 'HAN']);
        $this->fare = FareClass::factory()->create();
        $this->aircraft = Aircraft::factory()->create();
        $this->seats = Seat::factory()->count(4)
            ->sequence(fn ($s) => ['seat_number' => ($s->index + 1).'A'])
            ->create(['aircraft_id' => $this->aircraft->id]);
        $this->day = now('UTC')->addDays(3)->startOfDay();
    }

    private function at(int $h, int $m = 0, int $plusDays = 0): string
    {
        return $this->day->copy()->addDays($plusDays)->setTime($h, $m)->toDateTimeString();
    }

    private function makeFlight(Airport $from, Airport $to, string $depUtc, string $arrUtc): Flight
    {
        $flight = Flight::factory()->create([
            'aircraft_id' => $this->aircraft->id,
            'departure_airport_id' => $from->id,
            'arrival_airport_id' => $to->id,
            'departure_time' => $depUtc,
            'arrival_time' => $arrUtc,
            'status' => 'scheduled',
        ]);

        foreach ($this->seats as $seat) {
            FlightSeat::factory()->create([
                'flight_id' => $flight->id,
                'seat_id' => $seat->id,
                'fare_class_id' => $this->fare->id,
                'price' => 1000000,
                'status' => 'available',
            ]);
        }

        return $flight;
    }

    private function searchParams(array $override = []): array
    {
        return array_merge([
            'trip_type' => 'round-trip',
            'departure_airport_id' => $this->sgn->id,
            'arrival_airport_id' => $this->han->id,
            'departure_date' => $this->day->toDateString(),
            'return_date' => $this->day->toDateString(),
            'fare_class_id' => $this->fare->id,
            'adults' => 1,
        ], $override);
    }

    // Outbound SGN->HAN 01:00-03:00 UTC, return HAN->SGN 03:01-05:00 UTC; session chưa chọn gì
    private function startRoundTrip(): array
    {
        $out = $this->makeFlight($this->sgn, $this->han, $this->at(1), $this->at(3));
        $ret = $this->makeFlight($this->han, $this->sgn, $this->at(3, 1), $this->at(5));

        $this->actingAs(User::factory()->create());
        session(['pending_selection' => $this->pendingSelection([$out, $ret], $this->fare, 1, 0, 0, false)]);

        return [$out, $ret];
    }

    private function chooseOutbound(Flight $out): void
    {
        $pending = session('pending_selection');
        $pending['legs'][0]['flight_id'] = $out->id;
        session(['pending_selection' => $pending]);
    }

    // FlightSearchRequest

    public function test_return_date_before_departure_is_rejected(): void
    {
        $params = $this->searchParams(['return_date' => $this->day->copy()->subDay()->toDateString()]);

        $this->get(route('flights.search.results', $params))->assertSessionHasErrors('return_date');
    }

    public function test_round_trip_requires_return_date(): void
    {
        $params = $this->searchParams();
        unset($params['return_date']);

        $this->get(route('flights.search.results', $params))->assertSessionHasErrors('return_date');
    }

    public function test_multi_city_is_not_accepted_in_phase_b(): void
    {
        $this->get(route('flights.search.results', $this->searchParams(['trip_type' => 'multi-city'])))
            ->assertSessionHasErrors('trip_type');
    }

    public function test_zero_adults_is_rejected_by_search_request(): void
    {
        $this->get(route('flights.search.results', $this->searchParams(['adults' => 0])))
            ->assertSessionHasErrors('adults');
    }

    public function test_round_trip_search_writes_skeleton_with_reversed_return_leg(): void
    {
        $this->get(route('flights.search.results', $this->searchParams()))->assertOk();

        $pending = session('pending_selection');

        $this->assertSame('round-trip', $pending['trip_type']);
        $this->assertCount(2, $pending['legs']);
        $this->assertSame($this->han->id, $pending['legs'][1]['from']);
        $this->assertSame($this->sgn->id, $pending['legs'][1]['to']);
        $this->assertNull($pending['legs'][0]['flight_id']);
        $this->assertNull($pending['legs'][1]['flight_id']);
    }

    // precheck

    public function test_choosing_outbound_goes_to_return_leg(): void
    {
        [$out] = $this->startRoundTrip();

        $this->post(route('booking.precheck'), ['flight_id' => $out->id, 'leg' => 0])
            ->assertRedirect(route('booking.leg', 1));

        $this->assertSame($out->id, session('pending_selection')['legs'][0]['flight_id']);
    }

    public function test_one_way_precheck_goes_to_passenger_form(): void
    {
        $out = $this->makeFlight($this->sgn, $this->han, $this->at(1), $this->at(3));
        $this->actingAs(User::factory()->create());
        session(['pending_selection' => $this->pendingSelection($out, $this->fare, 1, 0, 0, false)]);

        $this->post(route('booking.precheck'), ['flight_id' => $out->id, 'leg' => 0])
            ->assertRedirect(route('booking.passengers.form'));
    }

    public function test_precheck_ignores_passenger_counts_sent_by_client(): void
    {
        [$out] = $this->startRoundTrip();

        $this->post(route('booking.precheck'), [
            'flight_id' => $out->id,
            'leg' => 0,
            'adults' => 9,
            'fare_class_id' => 999,
        ]);

        $pending = session('pending_selection');
        $this->assertSame(1, $pending['adults']);
        $this->assertSame($this->fare->id, $pending['fare_class_id']);
    }

    public function test_precheck_without_session_redirects_home(): void
    {
        $this->actingAs(User::factory()->create());
        $out = $this->makeFlight($this->sgn, $this->han, $this->at(1), $this->at(3));

        $this->post(route('booking.precheck'), ['flight_id' => $out->id, 'leg' => 0])
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');
    }

    public function test_return_leg_rejects_flight_departing_before_outbound_arrives(): void
    {
        [$out] = $this->startRoundTrip();
        $early = $this->makeFlight($this->han, $this->sgn, $this->at(2, 30), $this->at(4)); // out hạ cánh 03:00
        $this->chooseOutbound($out);

        $this->post(route('booking.precheck'), ['flight_id' => $early->id, 'leg' => 1])
            ->assertSessionHas('error');

        $this->assertNull(session('pending_selection')['legs'][1]['flight_id']);
    }

    public function test_return_leg_accepts_flight_one_minute_after_arrival(): void
    {
        [$out, $ret] = $this->startRoundTrip(); // ret khởi hành 03:01, out hạ cánh 03:00
        $this->chooseOutbound($out);

        $this->post(route('booking.precheck'), ['flight_id' => $ret->id, 'leg' => 1])
            ->assertRedirect(route('booking.passengers.form'));

        $this->assertSame($ret->id, session('pending_selection')['legs'][1]['flight_id']);
    }

    public function test_wrong_route_is_rejected(): void
    {
        [$out] = $this->startRoundTrip();
        $dad = Airport::factory()->create(['timezone' => 'Asia/Ho_Chi_Minh', 'country' => 'Vietnam', 'iata_code' => 'DAD']);
        $wrong = $this->makeFlight($this->sgn, $dad, $this->at(1), $this->at(3));

        $this->post(route('booking.precheck'), ['flight_id' => $wrong->id, 'leg' => 0])
            ->assertSessionHas('error');

        $this->assertNull(session('pending_selection')['legs'][0]['flight_id']);
    }

    public function test_wrong_date_is_rejected(): void
    {
        [$out] = $this->startRoundTrip();
        $nextDay = $this->makeFlight($this->sgn, $this->han, $this->at(1, 0, 1), $this->at(3, 0, 1));

        $this->post(route('booking.precheck'), ['flight_id' => $nextDay->id, 'leg' => 0])
            ->assertSessionHas('error');

        $this->assertNull(session('pending_selection')['legs'][0]['flight_id']);
    }

    public function test_cannot_skip_to_return_leg_before_outbound_is_chosen(): void
    {
        [, $ret] = $this->startRoundTrip();

        $this->post(route('booking.precheck'), ['flight_id' => $ret->id, 'leg' => 1])
            ->assertRedirectContains('/flights/results');

        $this->assertNull(session('pending_selection')['legs'][1]['flight_id']);
    }

    public function test_rechoosing_outbound_clears_return_leg(): void
    {
        [$out, $ret] = $this->startRoundTrip();
        $out2 = $this->makeFlight($this->sgn, $this->han, $this->at(2), $this->at(4));
        $this->chooseOutbound($out);
        $pending = session('pending_selection');
        $pending['legs'][1]['flight_id'] = $ret->id;
        session(['pending_selection' => $pending]);

        $this->post(route('booking.precheck'), ['flight_id' => $out2->id, 'leg' => 0])
            ->assertRedirect(route('booking.leg', 1));

        $pending = session('pending_selection');
        $this->assertSame($out2->id, $pending['legs'][0]['flight_id']);
        $this->assertNull($pending['legs'][1]['flight_id']);
    }

    // booking.leg

    public function test_return_leg_page_lists_only_flights_after_outbound_arrival(): void
    {
        [$out, $ret] = $this->startRoundTrip();
        $early = $this->makeFlight($this->han, $this->sgn, $this->at(2, 30), $this->at(4));
        $this->chooseOutbound($out);

        $response = $this->get(route('booking.leg', 1))->assertOk();

        $ids = collect($response->viewData('flights')->items())->pluck('id');
        $this->assertTrue($ids->contains($ret->id));
        $this->assertFalse($ids->contains($early->id));
    }

    public function test_return_leg_page_redirects_to_search_when_outbound_not_chosen(): void
    {
        $this->startRoundTrip();

        $this->get(route('booking.leg', 1))->assertRedirectContains('/flights/results');
    }

    public function test_passenger_form_redirects_back_to_wizard_when_leg_unchosen(): void
    {
        [$out] = $this->startRoundTrip();
        $this->chooseOutbound($out);

        $this->get(route('booking.passengers.form'))->assertRedirect(route('booking.leg', 1));
    }
    // store() re-validate chuỗi chuyến

    // Giả định payload tối thiểu khớp PassengerBookingRequest; nếu thiếu field bắt buộc, copy mẫu từ PassengerBookingTest
    private function passengerPayload(): array
    {
        return ['adults' => [[
            'full_name' => 'Nguyen Van A',
            'document_number' => '012345678901',
            'date_of_birth' => '1990-01-01',
        ]]];
    }

    private function chooseBoth(Flight $out, Flight $ret): void
    {
        $pending = session('pending_selection');
        $pending['legs'][0]['flight_id'] = $out->id;
        $pending['legs'][1]['flight_id'] = $ret->id;
        session(['pending_selection' => $pending]);
    }

    public function test_store_rejects_when_a_leg_is_unchosen(): void
    {
        [$out] = $this->startRoundTrip();
        $this->chooseOutbound($out); // chặng về chưa chọn

        $this->post(route('booking.passengers.store'), $this->passengerPayload())
            ->assertRedirect(route('booking.leg', 1))
            ->assertSessionHas('error');

        $this->assertSame(0, Booking::count());
    }

    public function test_store_rejects_when_return_flight_cancelled_mid_flow(): void
    {
        [$out, $ret] = $this->startRoundTrip();
        $this->chooseBoth($out, $ret);
        $ret->update(['status' => 'cancelled']); // bị hủy sau khi khách chọn xong

        $this->post(route('booking.passengers.store'), $this->passengerPayload())
            ->assertRedirect(route('booking.leg', 1))
            ->assertSessionHas('error');

        $this->assertSame(0, Booking::count());
        $this->assertNull(session('pending_selection')['legs'][1]['flight_id']);
        $this->assertSame($out->id, session('pending_selection')['legs'][0]['flight_id']); // chặng đi giữ nguyên
        $this->assertSame(0, FlightSeat::where('status', 'held')->count());
    }

    public function test_store_rejects_when_outbound_cancelled_and_clears_all_legs(): void
    {
        [$out, $ret] = $this->startRoundTrip();
        $this->chooseBoth($out, $ret);
        $out->update(['status' => 'cancelled']);

        $this->post(route('booking.passengers.store'), $this->passengerPayload())
            ->assertRedirectContains('/flights/results');

        $pending = session('pending_selection');
        $this->assertNull($pending['legs'][0]['flight_id']);
        $this->assertNull($pending['legs'][1]['flight_id']);
        $this->assertSame(0, Booking::count());
    }

    public function test_store_rejects_when_return_departs_before_outbound_arrives(): void
    {
        [$out] = $this->startRoundTrip();
        $early = $this->makeFlight($this->han, $this->sgn, $this->at(2, 30), $this->at(4)); // out hạ cánh 03:00
        $this->chooseBoth($out, $early); // session bị dựng tay lệch chuỗi

        $this->post(route('booking.passengers.store'), $this->passengerPayload())
            ->assertRedirect(route('booking.leg', 1))
            ->assertSessionHas('error');

        $this->assertSame(0, Booking::count());
    }

    public function test_ordered_legs_follow_departure_time_not_row_id(): void
    {
        [$out, $ret] = $this->startRoundTrip();
        $booking = Booking::factory()->create();

        // tạo chặng về TRƯỚC để booking_flights.id của chặng về nhỏ hơn
        BookingFlight::factory()->create(['booking_id' => $booking->id, 'flight_id' => $ret->id]);
        BookingFlight::factory()->create(['booking_id' => $booking->id, 'flight_id' => $out->id]);

        $booking->load('bookingFlights.flight');

        $this->assertSame([$out->id, $ret->id], $booking->ordered_legs->pluck('flight_id')->all());
    }
}
