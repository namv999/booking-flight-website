<?php

namespace Tests\Feature;

use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Seat;
use App\Models\User;
use App\Services\JourneyValidationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiCitySearchTest extends TestCase
{
    use RefreshDatabase;

    private Airport $sgn;

    private Airport $han;

    private Airport $dad;

    private Airport $pqc;

    private FareClass $fare;

    private Aircraft $aircraft;

    private $seats;

    private Carbon $day; // 00:00 UTC ngày bay chặng 1 (mọi sân bay cùng UTC+7)

    protected function setUp(): void
    {
        parent::setUp();

        $vn = ['timezone' => 'Asia/Ho_Chi_Minh', 'country' => 'Vietnam'];
        $this->sgn = Airport::factory()->create($vn + ['iata_code' => 'SGN']);
        $this->han = Airport::factory()->create($vn + ['iata_code' => 'HAN']);
        $this->dad = Airport::factory()->create($vn + ['iata_code' => 'DAD']);
        $this->pqc = Airport::factory()->create($vn + ['iata_code' => 'PQC']);
        $this->fare = FareClass::factory()->create();
        $this->aircraft = Aircraft::factory()->create();
        $this->seats = Seat::factory()->count(4)
            ->sequence(fn($s) => ['seat_number' => ($s->index + 1) . 'A'])
            ->create(['aircraft_id' => $this->aircraft->id]);
        $this->day = now('UTC')->addDays(3)->startOfDay();

        $this->actingAs(User::factory()->create());
    }

    private function at(int $h, int $m = 0, int $plusDays = 0): string
    {
        return $this->day->copy()->addDays($plusDays)->setTime($h, $m)->toDateTimeString();
    }

    private function date(int $plusDays = 0): string
    {
        return $this->day->copy()->addDays($plusDays)->toDateString();
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

    // Mặc định: SGN->HAN (D), HAN->DAD (D+1), PQC->SGN (D+2). Chặng 3 là open-jaw (đi từ PQC, không phải DAD)
    private function params(array $override = []): array
    {
        return array_merge([
            'trip_type' => 'multi-city',
            'departure_airport_id' => $this->sgn->id,
            'arrival_airport_id' => $this->han->id,
            'departure_date' => $this->date(),
            'fare_class_id' => $this->fare->id,
            'adults' => 1,
            'extra_legs' => [
                ['departure_airport_id' => $this->han->id, 'arrival_airport_id' => $this->dad->id, 'date' => $this->date(1)],
                ['departure_airport_id' => $this->pqc->id, 'arrival_airport_id' => $this->sgn->id, 'date' => $this->date(2)],
            ],
        ], $override);
    }

    private function search(array $override = [])
    {
        return $this->get(route('flights.search.results', $this->params($override)));
    }

    // FlightSearchRequest

    public function test_multi_city_accepts_open_jaw_and_writes_three_leg_skeleton(): void
    {
        $this->search()->assertOk();

        $pending = session('pending_selection');

        $this->assertSame('multi-city', $pending['trip_type']);
        $this->assertCount(3, $pending['legs']);
        $this->assertSame(
            [$this->sgn->id, $this->han->id, $this->date()],
            [$pending['legs'][0]['from'], $pending['legs'][0]['to'], $pending['legs'][0]['date']]
        );
        $this->assertSame(
            [$this->han->id, $this->dad->id, $this->date(1)],
            [$pending['legs'][1]['from'], $pending['legs'][1]['to'], $pending['legs'][1]['date']]
        );
        // chặng 3 đi từ PQC, không nối tiếp điểm đến DAD của chặng 2
        $this->assertSame(
            [$this->pqc->id, $this->sgn->id, $this->date(2)],
            [$pending['legs'][2]['from'], $pending['legs'][2]['to'], $pending['legs'][2]['date']]
        );
        $this->assertNull($pending['legs'][0]['flight_id']);
        $this->assertNull($pending['legs'][1]['flight_id']);
        $this->assertNull($pending['legs'][2]['flight_id']);
    }

    public function test_multi_city_is_accepted_as_trip_type_but_requires_extra_legs(): void
    {
        $params = $this->params();
        unset($params['extra_legs']);

        $this->get(route('flights.search.results', $params))
            ->assertSessionDoesntHaveErrors('trip_type')
            ->assertSessionHasErrors('extra_legs');
    }

    public function test_more_than_three_extra_legs_is_rejected(): void
    {
        $leg = fn(int $d) => ['departure_airport_id' => $this->han->id, 'arrival_airport_id' => $this->dad->id, 'date' => $this->date($d)];

        $this->search(['extra_legs' => [$leg(1), $leg(2), $leg(3), $leg(4)]])
            ->assertSessionHasErrors('extra_legs');
    }

    public function test_extra_leg_with_same_departure_and_arrival_is_rejected(): void
    {
        $this->search(['extra_legs' => [
            ['departure_airport_id' => $this->han->id, 'arrival_airport_id' => $this->han->id, 'date' => $this->date(1)],
        ]])->assertSessionHasErrors('extra_legs.0.departure_airport_id');
    }

    public function test_extra_leg_missing_a_field_is_rejected(): void
    {
        $this->search(['extra_legs' => [
            ['departure_airport_id' => $this->han->id, 'date' => $this->date(1)], // thiếu điểm đến
        ]])->assertSessionHasErrors('extra_legs.0.arrival_airport_id');
    }

    public function test_extra_leg_dated_before_previous_leg_is_rejected(): void
    {
        $this->search(['extra_legs' => [
            ['departure_airport_id' => $this->han->id, 'arrival_airport_id' => $this->dad->id, 'date' => $this->date(2)],
            ['departure_airport_id' => $this->dad->id, 'arrival_airport_id' => $this->sgn->id, 'date' => $this->date(1)],
        ]])->assertSessionHasErrors('extra_legs.1.date');
    }

    public function test_extra_leg_on_same_day_as_previous_leg_is_allowed(): void
    {
        $this->search(['extra_legs' => [
            ['departure_airport_id' => $this->han->id, 'arrival_airport_id' => $this->dad->id, 'date' => $this->date()],
        ]])->assertSessionDoesntHaveErrors();
    }

    public function test_extra_legs_are_ignored_for_one_way(): void
    {
        $this->search(['trip_type' => 'one-way', 'extra_legs' => [['arrival_airport_id' => 'abc']]])
            ->assertOk()
            ->assertSessionDoesntHaveErrors();

        $this->assertCount(1, session('pending_selection')['legs']);
    }

    public function test_leg_url_rebuilds_a_search_the_request_accepts(): void
    {
        $this->search()->assertOk();
        $before = session('pending_selection');

        $url = app(JourneyValidationService::class)->legUrl($before, 0);

        $this->get($url)->assertOk()->assertSessionDoesntHaveErrors();
        $this->assertSame($before['legs'], session('pending_selection')['legs']);
    }

    // wizard

    public function test_open_jaw_leg_needs_no_continuity_with_previous_arrival_airport(): void
    {
        // 2 chặng: SGN->HAN rồi DAD->SGN (không đi từ HAN)
        $this->search(['extra_legs' => [
            ['departure_airport_id' => $this->dad->id, 'arrival_airport_id' => $this->sgn->id, 'date' => $this->date(1)],
        ]]);
        $f0 = $this->makeFlight($this->sgn, $this->han, $this->at(1), $this->at(3));
        $f1 = $this->makeFlight($this->dad, $this->sgn, $this->at(1, 0, 1), $this->at(3, 0, 1));

        $this->post(route('booking.precheck'), ['flight_id' => $f0->id, 'leg' => 0])
            ->assertRedirect(route('booking.leg', 1));

        $this->post(route('booking.precheck'), ['flight_id' => $f1->id, 'leg' => 1])
            ->assertRedirect(route('booking.passengers.form'));
    }

    public function test_three_leg_wizard_reaches_passenger_form(): void
    {
        $this->search();
        $f0 = $this->makeFlight($this->sgn, $this->han, $this->at(1), $this->at(3));
        $f1 = $this->makeFlight($this->han, $this->dad, $this->at(1, 0, 1), $this->at(3, 0, 1));
        $f2 = $this->makeFlight($this->pqc, $this->sgn, $this->at(1, 0, 2), $this->at(3, 0, 2));

        $this->post(route('booking.precheck'), ['flight_id' => $f0->id, 'leg' => 0])
            ->assertRedirect(route('booking.leg', 1));
        $this->get(route('booking.leg', 1))->assertOk();

        $this->post(route('booking.precheck'), ['flight_id' => $f1->id, 'leg' => 1])
            ->assertRedirect(route('booking.leg', 2));
        $this->get(route('booking.leg', 2))->assertOk();

        $this->post(route('booking.precheck'), ['flight_id' => $f2->id, 'leg' => 2])
            ->assertRedirect(route('booking.passengers.form'));

        $this->assertSame(
            [$f0->id, $f1->id, $f2->id],
            collect(session('pending_selection')['legs'])->pluck('flight_id')->all()
        );
    }

    public function test_cannot_skip_to_third_leg_before_second_is_chosen(): void
    {
        $this->search();
        $f0 = $this->makeFlight($this->sgn, $this->han, $this->at(1), $this->at(3));

        $this->post(route('booking.precheck'), ['flight_id' => $f0->id, 'leg' => 0]);

        // mới chọn chặng 1, mở chặng 3 phải bị đẩy về chặng 2
        $this->get(route('booking.leg', 2))->assertRedirect(route('booking.leg', 1));
    }

    public function test_rechoosing_first_leg_clears_all_later_legs(): void
    {
        $this->search();
        $f0 = $this->makeFlight($this->sgn, $this->han, $this->at(1), $this->at(3));
        $f0b = $this->makeFlight($this->sgn, $this->han, $this->at(2), $this->at(4));
        $f1 = $this->makeFlight($this->han, $this->dad, $this->at(1, 0, 1), $this->at(3, 0, 1));
        $f2 = $this->makeFlight($this->pqc, $this->sgn, $this->at(1, 0, 2), $this->at(3, 0, 2));

        $pending = session('pending_selection');
        $pending['legs'][0]['flight_id'] = $f0->id;
        $pending['legs'][1]['flight_id'] = $f1->id;
        $pending['legs'][2]['flight_id'] = $f2->id;
        session(['pending_selection' => $pending]);

        $this->post(route('booking.precheck'), ['flight_id' => $f0b->id, 'leg' => 0])
            ->assertRedirect(route('booking.leg', 1));

        $pending = session('pending_selection');
        $this->assertSame($f0b->id, $pending['legs'][0]['flight_id']);
        $this->assertNull($pending['legs'][1]['flight_id']);
        $this->assertNull($pending['legs'][2]['flight_id']);
    }
    public function test_leg_page_labels_third_leg_as_leg_number_not_return_trip(): void
    {
        $this->search();
        $f0 = $this->makeFlight($this->sgn, $this->han, $this->at(1), $this->at(3));
        $f1 = $this->makeFlight($this->han, $this->dad, $this->at(1, 0, 1), $this->at(3, 0, 1));
        $this->makeFlight($this->pqc, $this->sgn, $this->at(1, 0, 2), $this->at(3, 0, 2));

        $this->post(route('booking.precheck'), ['flight_id' => $f0->id, 'leg' => 0]);
        $this->post(route('booking.precheck'), ['flight_id' => $f1->id, 'leg' => 1]);

        $this->get(route('booking.leg', 2))
            ->assertOk()
            ->assertDontSee('Chuyến về');
    }
}
