<?php

namespace Tests\Feature;

use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Payment;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\User;
use App\Services\BookingExpiryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoundTripBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private Airport $sgn;

    private Airport $han;

    private FareClass $fare;

    private Aircraft $aircraft;

    private $seats;

    private Carbon $day; // 00:00 UTC của ngày bay đi (SGN/HAN cùng UTC+7 nên 01:00-13:00 UTC vẫn cùng ngày local)

    private Flight $out;

    private Flight $ret;

    private User $user;

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

        // Chuyến VỀ tạo trước: flight_id nhỏ hơn chuyến đi, nên booking_flights.id của chặng về
        // cũng nhỏ hơn. Mọi trang phải sắp theo departure_time (ordered_legs), không theo id
        $this->ret = $this->makeFlight($this->han, $this->sgn, $this->at(1, 0, 2), $this->at(3, 0, 2), 1500000);
        $this->out = $this->makeFlight($this->sgn, $this->han, $this->at(1), $this->at(3), 1000000);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function at(int $h, int $m = 0, int $plusDays = 0): string
    {
        return $this->day->copy()->addDays($plusDays)->setTime($h, $m)->toDateTimeString();
    }

    private function makeFlight(Airport $from, Airport $to, string $depUtc, string $arrUtc, int $price): Flight
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
                'price' => $price,
                'status' => 'available',
            ]);
        }

        return $flight;
    }

    // 1 người lớn + 1 em bé, khứ hồi: đi ngày D, về ngày D+2
    private function searchParams(): array
    {
        return [
            'trip_type' => 'round-trip',
            'departure_airport_id' => $this->sgn->id,
            'arrival_airport_id' => $this->han->id,
            'departure_date' => $this->day->toDateString(),
            'return_date' => $this->day->copy()->addDays(2)->toDateString(),
            'fare_class_id' => $this->fare->id,
            'adults' => 1,
            'children' => 0,
            'infants' => 1,
        ];
    }

    private function passengerPayload(): array
    {
        return [
            'adults' => [[
                'full_name' => 'Nguyen Van A',
                'document_number' => '012345678901',
                'date_of_birth' => '1990-01-01',
            ]],
            'infants' => [[
                'full_name' => 'Nguyen Be B',
                'date_of_birth' => now()->subMonths(6)->toDateString(),
                'companion_adult_index' => 0,
            ]],
        ];
    }

    // Đi hết wizard bằng HTTP thật: tìm kiếm -> chọn chuyến đi -> chọn chuyến về -> form hành khách
    private function bookRoundTrip(): Booking
    {
        $this->get(route('flights.search.results', $this->searchParams()))->assertOk();

        $this->post(route('booking.precheck'), ['flight_id' => $this->out->id, 'leg' => 0])
            ->assertRedirect(route('booking.leg', 1));

        $legPage = $this->get(route('booking.leg', 1))->assertOk();
        $this->assertSame([$this->out->id], $legPage->viewData('chosenFlights')->pluck('id')->all());

        $this->post(route('booking.precheck'), ['flight_id' => $this->ret->id, 'leg' => 1])
            ->assertRedirect(route('booking.passengers.form'));

        $form = $this->get(route('booking.passengers.form'))->assertOk();
        // flights sắp theo giờ bay: chuyến đi trước chuyến về dù flight_id ngược
        $this->assertSame([$this->out->id, $this->ret->id], $form->viewData('flights')->pluck('id')->all());

        $response = $this->post(route('booking.passengers.store'), $this->passengerPayload());
        $response->assertSessionHasNoErrors();

        $booking = Booking::latest('id')->firstOrFail();
        $response->assertRedirect(route('payment.show', $booking));

        return $booking;
    }

    public function test_full_flow_creates_one_booking_with_two_legs(): void
    {
        $booking = $this->bookRoundTrip();

        $this->assertSame(1, Booking::count());
        $this->assertSame(2, BookingFlight::where('booking_id', $booking->id)->count());
        $this->assertNull(session('pending_selection'));

        $bfOut = BookingFlight::where('booking_id', $booking->id)->where('flight_id', $this->out->id)->firstOrFail();
        $bfRet = BookingFlight::where('booking_id', $booking->id)->where('flight_id', $this->ret->id)->firstOrFail();
        $this->assertLessThan($bfOut->id, $bfRet->id); // xác nhận fixture thật sự đảo thứ tự id

        // Mỗi chặng: 1 vé người lớn có ghế + 1 vé em bé không ghế
        foreach ([$bfOut, $bfRet] as $bf) {
            $this->assertSame(2, Ticket::where('booking_flight_id', $bf->id)->count());
            $this->assertSame(1, Ticket::where('booking_flight_id', $bf->id)->whereNotNull('flight_seat_id')->count());
        }

        // Em bé = 10% giá ghế của người lớn trên CHÍNH chặng đó (giá hai chặng khác nhau)
        $infantOut = Ticket::where('booking_flight_id', $bfOut->id)
            ->whereHas('passenger', fn ($q) => $q->where('passenger_type', 'infant'))->firstOrFail();
        $infantRet = Ticket::where('booking_flight_id', $bfRet->id)
            ->whereHas('passenger', fn ($q) => $q->where('passenger_type', 'infant'))->firstOrFail();
        $this->assertEquals(100000, $infantOut->price);
        $this->assertEquals(150000, $infantRet->price);

        // 1.000.000 + 100.000 + 1.500.000 + 150.000
        $this->assertEquals(2750000, $booking->fresh()->total_amount);

        // Giữ ghế cho cả hai chặng: mỗi chuyến đúng 1 ghế held
        foreach ([$this->out, $this->ret] as $flight) {
            $this->assertSame(1, FlightSeat::where('flight_id', $flight->id)
                ->where('status', 'held')->where('held_by', $this->user->id)->count());
        }
    }

    public function test_payment_page_and_history_show_legs_in_flight_order(): void
    {
        $booking = $this->bookRoundTrip();

        $this->get(route('payment.show', $booking))
            ->assertOk()
            ->assertSeeInOrder(['Chuyến đi', 'Chuyến về']);

        $this->post(route('payment.store', $booking), ['method' => 'the_tin_dung', 'simulate_result' => 'success'])
            ->assertRedirect(route('booking-history.show', $booking));

        $this->get(route('booking-history.show', $booking))
            ->assertOk()
            ->assertSeeInOrder(['Chuyến đi', 'Chuyến về'])
            ->assertSee('Thành công');

        $this->get(route('booking-history.index'))
            ->assertOk()
            ->assertSee('SGN → HAN → SGN')
            ->assertSee('Khứ hồi');
    }

    public function test_successful_payment_books_seats_on_both_legs(): void
    {
        $booking = $this->bookRoundTrip();

        $this->post(route('payment.store', $booking), ['method' => 'the_tin_dung', 'simulate_result' => 'success']);

        $this->assertSame('paid', $booking->fresh()->status);
        $this->assertSame(2, FlightSeat::where('status', 'booked')->count());
        $this->assertSame(0, FlightSeat::where('status', 'held')->count());

        $payment = Payment::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame('success', $payment->status);
        $this->assertEquals($booking->fresh()->total_amount, $payment->amount);
    }

    public function test_expiry_releases_seats_on_both_legs(): void
    {
        $booking = $this->bookRoundTrip();
        $this->assertSame(2, FlightSeat::where('status', 'held')->count());

        // Quá hạn thanh toán: lùi created_at hơn cửa sổ giữ ghế
        Booking::whereKey($booking->id)->update([
            'created_at' => now()->subMinutes(config('booking.seat_hold_minutes') + 1),
        ]);

        app(BookingExpiryService::class)->cancelIfExpired($booking->fresh());

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame(0, FlightSeat::where('status', 'held')->count());
        $this->assertSame(8, FlightSeat::where('status', 'available')->count()); // 2 chuyến x 4 ghế
        $this->assertSame(0, Ticket::whereNotNull('flight_seat_id')->count());
    }

    public function test_history_payment_badge_follows_payment_status(): void
    {
        $paid = Booking::factory()->paid()->create(['user_id' => $this->user->id]);
        Payment::factory()->create(['booking_id' => $paid->id, 'status' => 'success']);

        $this->get(route('booking-history.show', $paid))
            ->assertSee('Thành công')
            ->assertDontSee('Thất bại');

        $pending = Booking::factory()->create(['user_id' => $this->user->id]);
        Payment::factory()->failed()->create(['booking_id' => $pending->id]);

        $this->get(route('booking-history.show', $pending))
            ->assertSee('Thất bại')
            ->assertDontSee('Thành công');
    }
}
