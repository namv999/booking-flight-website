<?php

namespace Tests\Feature;

use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlightSearchTest extends TestCase
{
    use RefreshDatabase;

    private function validParams(array $overrides = []): array
    {
        return array_merge([
            'departure_date' => now()->addDay()->toDateString(),
            'adults' => 1,
            'children' => 0,
            'infants' => 0,
        ], $overrides);
    }

    public function test_happy_path_returns_flight_with_correct_min_price_and_available_seats(): void
    {
        $departureAirport = Airport::factory()->create();
        $arrivalAirport = Airport::factory()->create();
        $fareClass = FareClass::factory()->create();

        // Giờ khởi hành giữa trưa local -> tránh chạm edge case timezone (case 4/5 xử lý riêng)
        $departureTime = now($departureAirport->timezone)->addDay()->setTime(12, 0);

        $flight = Flight::factory()->create([
            'aircraft_id' => Aircraft::factory()->create(),
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'departure_time' => $departureTime->clone()->setTimezone('UTC'),
            'arrival_time' => $departureTime->clone()->addHours(2)->setTimezone('UTC'),
            'status' => 'scheduled',
        ]);

        FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'price' => 1800000,
            'status' => 'available',
        ]);
        FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'price' => 1500000, // seat rẻ hơn -> phải là min_price
            'status' => 'available',
        ]);

        $response = $this->get(route('flights.search.results', $this->validParams([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'fare_class_id' => $fareClass->id,
        ])));

        $response->assertOk();
        $flights = $response->viewData('flights');

        $this->assertCount(1, $flights);
        $result = $flights->first();
        $this->assertEquals($flight->id, $result->id);
        $this->assertEquals(1500000, $result->min_price);
        $this->assertEquals(2, $result->available_seats);
    }

    public function test_flight_excluded_when_only_other_fare_class_has_available_seats(): void
    {
        $departureAirport = Airport::factory()->create();
        $arrivalAirport = Airport::factory()->create();
        $searchedFareClass = FareClass::factory()->create();
        $otherFareClass = FareClass::factory()->create();

        $departureTime = now($departureAirport->timezone)->addDay()->setTime(12, 0);

        $flight = Flight::factory()->create([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'departure_time' => $departureTime->clone()->setTimezone('UTC'),
            'arrival_time' => $departureTime->clone()->addHours(2)->setTimezone('UTC'),
            'status' => 'scheduled',
        ]);

        // Fare class được search: hết ghế (booked)
        FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $searchedFareClass->id,
            'status' => 'booked',
        ]);
        // Fare class KHÁC: còn ghế trống -> không được tính vào kết quả search fare class kia
        FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $otherFareClass->id,
            'status' => 'available',
        ]);

        $response = $this->get(route('flights.search.results', $this->validParams([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'fare_class_id' => $searchedFareClass->id,
        ])));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('flights'));
    }

    public function test_held_but_expired_seat_still_counts_as_available_in_search(): void
    {
        $departureAirport = Airport::factory()->create();
        $arrivalAirport = Airport::factory()->create();
        $fareClass = FareClass::factory()->create();

        $departureTime = now($departureAirport->timezone)->addDay()->setTime(12, 0);

        $flight = Flight::factory()->create([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'departure_time' => $departureTime->clone()->setTimezone('UTC'),
            'arrival_time' => $departureTime->clone()->addHours(2)->setTimezone('UTC'),
            'status' => 'scheduled',
        ]);

        // Ghế held nhưng held_until đã qua -> vẫn phải tính là available trong search
        FlightSeat::factory()->heldExpired()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'price' => 1500000,
        ]);
        // Ghế held CÒN hạn -> không được tính
        FlightSeat::factory()->heldValid()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'price' => 2000000,
        ]);

        $response = $this->get(route('flights.search.results', $this->validParams([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'fare_class_id' => $fareClass->id,
        ])));

        $response->assertOk();
        $flights = $response->viewData('flights');

        $this->assertCount(1, $flights);
        $this->assertEquals(1, $flights->first()->available_seats);
        $this->assertEquals(1500000, $flights->first()->min_price);
    }

    public function test_early_morning_local_departure_is_found_by_correct_local_date(): void
    {
        // Sân bay đi ở VN (UTC+7). Chuyến khởi hành 01:00 giờ VN ngày 15/10
        // = 18:00 UTC ngày 14/10 trong DB. Đây chính là case bug cũ: whereDate() cũ
        // sẽ so khớp '2026-10-14' (ngày UTC) thay vì '2026-10-15' (ngày local đúng).
        $departureAirport = Airport::factory()->create(['timezone' => 'Asia/Ho_Chi_Minh']);
        $arrivalAirport = Airport::factory()->create();
        $fareClass = FareClass::factory()->create();

        $localDeparture = now('Asia/Ho_Chi_Minh')->addDays(2)->setTime(1, 0);

        $flight = Flight::factory()->create([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'departure_time' => $localDeparture->clone()->setTimezone('UTC'), // = 2026-10-14 18:00 UTC
            'arrival_time' => $localDeparture->clone()->addHours(2)->setTimezone('UTC'),
            'status' => 'scheduled',
        ]);

        FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'status' => 'available',
        ]);

        // Search đúng ngày LOCAL (15/10) -> phải tìm thấy
        $response = $this->get(route('flights.search.results', $this->validParams([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'fare_class_id' => $fareClass->id,
            'departure_date' => $localDeparture->toDateString(),
        ])));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('flights'));
        $this->assertEquals($flight->id, $response->viewData('flights')->first()->id);
    }

    public function test_early_morning_local_departure_not_matched_by_utc_date(): void
    {
        // Cùng chuyến bay như case trên (01:00 local 15/10 = 18:00 UTC 14/10),
        // nhưng lần này search bằng đúng NGÀY UTC (14/10) -> KHÔNG được khớp,
        // vì ngày UTC 14/10 không phải ngày khởi hành theo giờ hành khách nhìn thấy.
        $departureAirport = Airport::factory()->create(['timezone' => 'Asia/Ho_Chi_Minh']);
        $arrivalAirport = Airport::factory()->create();
        $fareClass = FareClass::factory()->create();

        $localDeparture = now('Asia/Ho_Chi_Minh')->addDays(2)->setTime(1, 0);

        $flight = Flight::factory()->create([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'departure_time' => $localDeparture->clone()->setTimezone('UTC'),
            'arrival_time' => $localDeparture->clone()->addHours(2)->setTimezone('UTC'),
            'status' => 'scheduled',
        ]);

        FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'status' => 'available',
        ]);

        $response = $this->get(route('flights.search.results', $this->validParams([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'fare_class_id' => $fareClass->id,
            'departure_date' => $localDeparture->clone()->setTimezone('UTC')->toDateString(),
        ])));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('flights'));
    }

    public function test_results_are_sorted_by_min_price_ascending(): void
    {
        $departureAirport = Airport::factory()->create();
        $arrivalAirport = Airport::factory()->create();
        $fareClass = FareClass::factory()->create();
        $departureTime = now($departureAirport->timezone)->addDay()->setTime(12, 0);

        $expensiveFlight = Flight::factory()->create([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'departure_time' => $departureTime->clone()->setTimezone('UTC'),
            'arrival_time' => $departureTime->clone()->addHours(2)->setTimezone('UTC'),
            'status' => 'scheduled',
        ]);
        FlightSeat::factory()->create([
            'flight_id' => $expensiveFlight->id,
            'fare_class_id' => $fareClass->id,
            'price' => 3000000,
            'status' => 'available',
        ]);

        $cheapFlight = Flight::factory()->create([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'departure_time' => $departureTime->clone()->addHours(3)->setTimezone('UTC'),
            'arrival_time' => $departureTime->clone()->addHours(5)->setTimezone('UTC'),
            'status' => 'scheduled',
        ]);
        FlightSeat::factory()->create([
            'flight_id' => $cheapFlight->id,
            'fare_class_id' => $fareClass->id,
            'price' => 1200000,
            'status' => 'available',
        ]);

        $response = $this->get(route('flights.search.results', $this->validParams([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'fare_class_id' => $fareClass->id,
        ])));

        $flights = $response->viewData('flights');
        $this->assertCount(2, $flights);
        $this->assertEquals($cheapFlight->id, $flights->first()->id);
        $this->assertEquals($expensiveFlight->id, $flights->last()->id);
    }

    public function test_validation_fails_when_departure_equals_arrival(): void
    {
        $airport = Airport::factory()->create();
        $fareClass = FareClass::factory()->create();

        $response = $this->get(route('flights.search.results', $this->validParams([
            'departure_airport_id' => $airport->id,
            'arrival_airport_id' => $airport->id,
            'fare_class_id' => $fareClass->id,
        ])));

        $response->assertSessionHasErrors('departure_airport_id');
        // $this->assertDatabaseMissing('flights', []); // không chạm DB thêm gì, chỉ để chắc chắn không crash trước validation
    }

    public function test_validation_fails_when_departure_date_is_in_the_past(): void
    {
        $departureAirport = Airport::factory()->create();
        $arrivalAirport = Airport::factory()->create();
        $fareClass = FareClass::factory()->create();

        $response = $this->get(route('flights.search.results', $this->validParams([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'fare_class_id' => $fareClass->id,
            'departure_date' => now()->subDay()->toDateString(),
        ])));

        $response->assertSessionHasErrors('departure_date');
    }

    public function test_validation_fails_when_infants_exceed_adults(): void
    {
        $departureAirport = Airport::factory()->create();
        $arrivalAirport = Airport::factory()->create();
        $fareClass = FareClass::factory()->create();

        $response = $this->get(route('flights.search.results', $this->validParams([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'fare_class_id' => $fareClass->id,
            'adults' => 1,
            'infants' => 2,
        ])));

        $response->assertSessionHasErrors('infants');
    }

    public function test_returns_empty_results_without_error_when_no_flight_matches(): void
    {
        $departureAirport = Airport::factory()->create();
        $arrivalAirport = Airport::factory()->create();
        $fareClass = FareClass::factory()->create();

        // Không tạo flight nào cả

        $response = $this->get(route('flights.search.results', $this->validParams([
            'departure_airport_id' => $departureAirport->id,
            'arrival_airport_id' => $arrivalAirport->id,
            'fare_class_id' => $fareClass->id,
        ])));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('flights'));
    }

    // Tạo chuyến + 1 ghế available. Ngày bay tính theo timezone sân bay đi,
    // giống cách các test hiện có dựng dữ liệu
    private function makeFlightWithSeat(Airport $dep, Airport $arr, FareClass $fare, array $flightOverrides = [], int $hour = 12): Flight
    {
        $departure = now($dep->timezone)->addDay()->setTime($hour, 0);

        $flight = Flight::factory()->create(array_merge([
            'departure_airport_id' => $dep->id,
            'arrival_airport_id' => $arr->id,
            'departure_time' => $departure->clone()->setTimezone('UTC'),
            'arrival_time' => $departure->clone()->addHours(2)->setTimezone('UTC'),
            'status' => 'scheduled',
        ], $flightOverrides));

        FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fare->id,
            'price' => 1500000,
            'status' => 'available',
        ]);

        return $flight;
    }

    private function searchParams(Airport $dep, Airport $arr, FareClass $fare, array $overrides = []): array
    {
        return $this->validParams(array_merge([
            'departure_airport_id' => $dep->id,
            'arrival_airport_id' => $arr->id,
            'fare_class_id' => $fare->id,
            'departure_date' => now($dep->timezone)->addDay()->toDateString(),
        ], $overrides));
    }

    public function test_view_receives_search_context(): void
    {
        $dep = Airport::factory()->create();
        $arr = Airport::factory()->create();
        $fare = FareClass::factory()->create();
        $this->makeFlightWithSeat($dep, $arr, $fare);

        $params = $this->searchParams($dep, $arr, $fare, ['adults' => 2, 'children' => 1, 'infants' => 1]);
        $response = $this->get(route('flights.search.results', $params));

        $response->assertOk();
        $this->assertEquals($dep->id, $response->viewData('departureAirport')->id);
        $this->assertEquals($arr->id, $response->viewData('arrivalAirport')->id);
        $this->assertEquals($fare->id, $response->viewData('fareClass')->id);
        $this->assertEquals($params['departure_date'], $response->viewData('departureDate'));
        $this->assertEquals(2, $response->viewData('adults'));
        $this->assertEquals(1, $response->viewData('children'));
        $this->assertEquals(1, $response->viewData('infants'));
    }

    public function test_cancelled_flight_is_excluded_but_delayed_is_included(): void
    {
        $dep = Airport::factory()->create();
        $arr = Airport::factory()->create();
        $fare = FareClass::factory()->create();

        $this->makeFlightWithSeat($dep, $arr, $fare, ['status' => 'cancelled'], 9);
        $delayed = $this->makeFlightWithSeat($dep, $arr, $fare, ['status' => 'delayed'], 15);

        $response = $this->get(route('flights.search.results', $this->searchParams($dep, $arr, $fare)));

        $flights = $response->viewData('flights');
        $this->assertCount(1, $flights);
        $this->assertEquals($delayed->id, $flights->first()->id);
    }

    public function test_flight_on_other_route_is_excluded(): void
    {
        $dep = Airport::factory()->create();
        $arr = Airport::factory()->create();
        $otherArr = Airport::factory()->create();
        $fare = FareClass::factory()->create();

        $this->makeFlightWithSeat($dep, $otherArr, $fare);

        $response = $this->get(route('flights.search.results', $this->searchParams($dep, $arr, $fare)));

        $this->assertCount(0, $response->viewData('flights'));
    }

    public function test_pagination_links_keep_search_query(): void
    {
        $dep = Airport::factory()->create();
        $arr = Airport::factory()->create();
        $fare = FareClass::factory()->create();

        // 11 chuyến -> trang 1 có 10, trang 2 có 1 (paginate(10))
        for ($i = 0; $i < 11; $i++) {
            $this->makeFlightWithSeat($dep, $arr, $fare, [], $i);
        }

        $response = $this->get(route('flights.search.results', $this->searchParams($dep, $arr, $fare)));

        $flights = $response->viewData('flights');
        $this->assertSame(11, $flights->total());
        $this->assertCount(10, $flights);
        $this->assertStringContainsString('departure_airport_id='.$dep->id, $flights->url(2));
        $this->assertStringContainsString('fare_class_id='.$fare->id, $flights->url(2));
    }

    public function test_results_page_renders_precheck_form_with_flight_and_leg(): void
    {
        $dep = Airport::factory()->create();
        $arr = Airport::factory()->create();
        $fare = FareClass::factory()->create();
        $flight = $this->makeFlightWithSeat($dep, $arr, $fare);

        $response = $this->get(route(
            'flights.search.results',
            $this->searchParams($dep, $arr, $fare, ['adults' => 2, 'children' => 1])
        ));

        $response->assertSee('action="'.route('booking.precheck').'"', false);
        $response->assertSee('name="flight_id" value="'.$flight->id.'"', false);
        $response->assertSee('name="leg" value="0"', false);
        // hạng vé và số người giờ lấy từ session, không còn nằm trong form
        $response->assertDontSee('name="fare_class_id"', false);
        $response->assertDontSee('name="adults"', false);
    }

    public function test_trip_type_sent_by_home_form_does_not_break_one_way_search(): void
    {
        // Form trang chủ luôn gửi trip_type; hiện backend bỏ qua, sau refactor
        // trip_type=one-way vẫn phải cho kết quả y hệt
        $dep = Airport::factory()->create();
        $arr = Airport::factory()->create();
        $fare = FareClass::factory()->create();
        $flight = $this->makeFlightWithSeat($dep, $arr, $fare);

        $response = $this->get(route(
            'flights.search.results',
            $this->searchParams($dep, $arr, $fare, ['trip_type' => 'one-way'])
        ));

        $response->assertOk();
        $this->assertEquals($flight->id, $response->viewData('flights')->first()->id);
    }
}
