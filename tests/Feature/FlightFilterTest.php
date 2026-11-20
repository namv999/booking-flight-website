<?php

namespace Tests\Feature;

use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlightFilterTest extends TestCase
{
    use RefreshDatabase;

    private Airport $sgn;

    private Airport $han;

    private FareClass $fare;

    private Aircraft $vnJet;

    private Aircraft $vjJet;

    private string $vnCode;

    private string $vjCode;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $vn = ['timezone' => 'Asia/Ho_Chi_Minh', 'country' => 'Vietnam'];
        $this->sgn = Airport::factory()->create($vn + ['iata_code' => 'SGN']);
        $this->han = Airport::factory()->create($vn + ['iata_code' => 'HAN']);
        $this->fare = FareClass::factory()->create();
        $vnAirline = Airline::factory()->create();
        $vjAirline = Airline::factory()->create();
        $this->vnCode = $vnAirline->code;
        $this->vjCode = $vjAirline->code;
        $this->vnJet = Aircraft::factory()->create(['airline_id' => $vnAirline->id]);
        $this->vjJet = Aircraft::factory()->create(['airline_id' => $vjAirline->id]);
        $this->date = now('Asia/Ho_Chi_Minh')->addDays(3)->toDateString();
    }

    // $localTime is the departure airport's local time, for example '07:30'.
    private function makeFlight(Aircraft $jet, string $localTime, int $price, int $minutes = 120): Flight
    {
        $dep = Carbon::parse("{$this->date} {$localTime}", 'Asia/Ho_Chi_Minh')->setTimezone('UTC');

        $flight = Flight::factory()->create([
            'aircraft_id' => $jet->id,
            'departure_airport_id' => $this->sgn->id,
            'arrival_airport_id' => $this->han->id,
            'departure_time' => $dep,
            'arrival_time' => $dep->copy()->addMinutes($minutes),
            'status' => 'scheduled',
        ]);

        FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $this->fare->id,
            'price' => $price,
            'status' => 'available',
        ]);

        return $flight;
    }

    private function startSearch(): void
    {
        $this->get(route('flights.search.results', [
            'departure_airport_id' => $this->sgn->id,
            'arrival_airport_id' => $this->han->id,
            'departure_date' => $this->date,
            'fare_class_id' => $this->fare->id,
            'adults' => 1,
        ]))->assertOk();
    }

    // Start a search to create pending_selection, then filter through booking.leg as a guest.
    private function filter(array $query = [])
    {
        $this->startSearch();

        return $this->get(route('booking.leg', ['leg' => 0] + $query));
    }

    private function ids($response): array
    {
        return $response->viewData('flights')->pluck('id')->all();
    }

    public function test_filter_by_airline(): void
    {
        $vn = $this->makeFlight($this->vnJet, '08:00', 1000000);
        $this->makeFlight($this->vjJet, '09:00', 1000000);

        $this->assertSame([$vn->id], $this->ids($this->filter(['airlines' => [$this->vnCode]])));
    }

    public function test_filter_by_price_range(): void
    {
        $this->makeFlight($this->vnJet, '08:00', 1000000);
        $mid = $this->makeFlight($this->vnJet, '09:00', 2000000);
        $this->makeFlight($this->vnJet, '10:00', 3000000);

        $response = $this->filter(['price_min' => 1500000, 'price_max' => 2500000]);

        $this->assertSame([$mid->id], $this->ids($response));
    }

    public function test_filter_by_departure_slot_uses_airport_timezone(): void
    {
        $early = $this->makeFlight($this->vnJet, '05:30', 1000000);
        $this->makeFlight($this->vnJet, '07:00', 1000000);
        $evening = $this->makeFlight($this->vnJet, '19:00', 1000000);

        $this->assertSame([$early->id], $this->ids($this->filter(['dep_time' => ['early_morning']])));

        $both = $this->ids($this->filter(['dep_time' => ['early_morning', 'evening']]));
        $this->assertEqualsCanonicalizing([$early->id, $evening->id], $both);
    }

    public function test_filter_by_arrival_slot_handles_overnight_arrival(): void
    {
        $this->makeFlight($this->vnJet, '08:00', 1000000, 120); // arrives at 10:00
        $night = $this->makeFlight($this->vnJet, '20:00', 1000000, 240); // arrives at 00:00 next day

        $this->assertSame([$night->id], $this->ids($this->filter(['arr_time' => ['early_morning']])));
    }

    public function test_multiple_criteria_are_combined_with_and(): void
    {
        $match = $this->makeFlight($this->vnJet, '07:00', 1200000);
        $this->makeFlight($this->vnJet, '19:00', 1200000); // wrong time slot
        $this->makeFlight($this->vjJet, '07:00', 1200000); // wrong airline
        $this->makeFlight($this->vnJet, '07:30', 3000000); // over price limit

        $response = $this->filter([
            'airlines' => [$this->vnCode],
            'dep_time' => ['morning'],
            'price_max' => 2000000,
        ]);

        $this->assertSame([$match->id], $this->ids($response));
    }

    public function test_sort_options(): void
    {
        $f1 = $this->makeFlight($this->vnJet, '08:00', 2000000, 180); // arrives at 11:00
        $f2 = $this->makeFlight($this->vnJet, '09:00', 1000000, 90); // arrives at 10:30
        $f3 = $this->makeFlight($this->vnJet, '10:00', 3000000, 120); // arrives at 12:00

        $this->startSearch();

        $expected = [
            'price_asc' => [$f2, $f1, $f3],
            'price_desc' => [$f3, $f1, $f2],
            'departure_asc' => [$f1, $f2, $f3],
            'departure_desc' => [$f3, $f2, $f1],
            'arrival_asc' => [$f2, $f1, $f3],
            'duration_asc' => [$f2, $f3, $f1],
        ];

        foreach ($expected as $sort => $order) {
            $response = $this->get(route('booking.leg', ['leg' => 0, 'sort' => $sort]));
            $this->assertSame(collect($order)->pluck('id')->all(), $this->ids($response), "sort={$sort}");
        }
    }

    public function test_default_sort_is_price_ascending(): void
    {
        $expensive = $this->makeFlight($this->vnJet, '08:00', 3000000);
        $cheap = $this->makeFlight($this->vnJet, '09:00', 1000000);

        $this->assertSame([$cheap->id, $expensive->id], $this->ids($this->filter()));
    }

    public function test_filtering_does_not_reset_pending_selection(): void
    {
        $this->makeFlight($this->vnJet, '08:00', 1000000);
        $this->startSearch();
        $before = session('pending_selection');

        $this->get(route('booking.leg', ['leg' => 0, 'airlines' => [$this->vnCode]]))->assertOk();

        $this->assertSame($before, session('pending_selection'));
    }

    public function test_pagination_links_keep_filter_query(): void
    {
        for ($i = 0; $i < 11; $i++) {
            $this->makeFlight($this->vnJet, sprintf('%02d:00', 6 + $i), 1000000 + $i);
        }

        $response = $this->filter(['airlines' => [$this->vnCode], 'sort' => 'price_desc']);
        $next = $response->viewData('flights')->nextPageUrl();

        $this->assertStringContainsString('sort=price_desc', $next);
        $this->assertStringContainsString('airlines%5B0%5D='.$this->vnCode, $next);
    }

    public function test_facets_ignore_active_filters(): void
    {
        $this->makeFlight($this->vnJet, '08:00', 1000000);
        $this->makeFlight($this->vjJet, '09:00', 2000000);

        $response = $this->filter(['airlines' => [$this->vnCode]]);
        $facets = $response->viewData('facets');

        $this->assertEqualsCanonicalizing([$this->vnCode, $this->vjCode], $facets['airlines']->pluck('code')->all());
        $this->assertSame(2, $facets['total']);
        $this->assertSame(1000000, $facets['price_min']);
        $this->assertSame(2000000, $facets['price_max']);
    }

    public function test_invalid_sort_and_price_range_are_rejected(): void
    {
        $this->startSearch();

        $this->get(route('booking.leg', ['leg' => 0, 'sort' => 'hack']))
            ->assertSessionHasErrors('sort');

        $this->get(route('booking.leg', ['leg' => 0, 'price_min' => 5000000, 'price_max' => 1000000]))
            ->assertSessionHasErrors('price_max');
    }
}
