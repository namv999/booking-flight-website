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
use App\Models\Ticket;
use App\Models\User;
use App\Services\BookingExpiryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiCityBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private Airport $sgn;

    private Airport $han;

    private Airport $dad;

    private Airport $pqc;

    private FareClass $fare;

    private Aircraft $aircraft;

    private $seats;

    private Carbon $day; // 00:00 UTC ngày bay chặng 1 (mọi sân bay UTC+7, 01:00-03:00 UTC vẫn cùng ngày local)

    private Flight $f0; // SGN -> HAN, ngày D

    private Flight $f1; // HAN -> DAD, ngày D+1

    private Flight $f2; // PQC -> SGN, ngày D+2 (open-jaw: không đi từ DAD)

    private User $user;

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
            ->sequence(fn ($s) => ['seat_number' => ($s->index + 1).'A'])
            ->create(['aircraft_id' => $this->aircraft->id]);
        $this->day = now('UTC')->addDays(3)->startOfDay();

        // Tạo chặng SAU CÙNG trước: flight_id (và booking_flights.id) ngược thứ tự giờ bay,
        // nên mọi trang phải sắp theo departure_time chứ không theo id
        $this->f2 = $this->makeFlight($this->pqc, $this->sgn, $this->at(1, 0, 2), $this->at(3, 0, 2), 1500000);
        $this->f1 = $this->makeFlight($this->han, $this->dad, $this->at(1, 0, 1), $this->at(3, 0, 1), 1200000);
        $this->f0 = $this->makeFlight($this->sgn, $this->han, $this->at(1), $this->at(3), 1000000);

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

    // 1 người lớn + 1 em bé; chặng 3 đi từ PQC (open-jaw)
    private function searchParams(): array
    {
        return [
            'trip_type' => 'multi-city',
            'departure_airport_id' => $this->sgn->id,
            'arrival_airport_id' => $this->han->id,
            'departure_date' => $this->day->toDateString(),
            'fare_class_id' => $this->fare->id,
            'adults' => 1,
            'children' => 0,
            'infants' => 1,
            'extra_legs' => [
                ['departure_airport_id' => $this->han->id, 'arrival_airport_id' => $this->dad->id, 'date' => $this->day->copy()->addDay()->toDateString()],
                ['departure_airport_id' => $this->pqc->id, 'arrival_airport_id' => $this->sgn->id, 'date' => $this->day->copy()->addDays(2)->toDateString()],
            ],
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

    // Đi qua wizard bằng HTTP thật đến form hành khách, chưa submit form
    private function walkWizardToPassengerForm(): void
    {
        $this->get(route('flights.search.results', $this->searchParams()))->assertOk();

        $this->post(route('booking.precheck'), ['flight_id' => $this->f0->id, 'leg' => 0])
            ->assertRedirect(route('booking.leg', 1));

        $leg1 = $this->get(route('booking.leg', 1))->assertOk();
        $this->assertSame([$this->f0->id], $leg1->viewData('chosenFlights')->pluck('id')->all());

        $this->post(route('booking.precheck'), ['flight_id' => $this->f1->id, 'leg' => 1])
            ->assertRedirect(route('booking.leg', 2));

        $leg2 = $this->get(route('booking.leg', 2))->assertOk();
        $this->assertSame([$this->f0->id, $this->f1->id], $leg2->viewData('chosenFlights')->pluck('id')->all());

        $this->post(route('booking.precheck'), ['flight_id' => $this->f2->id, 'leg' => 2])
            ->assertRedirect(route('booking.passengers.form'));

        $form = $this->get(route('booking.passengers.form'))->assertOk();
        // flights sắp theo giờ bay dù flight_id ngược
        $this->assertSame(
            [$this->f0->id, $this->f1->id, $this->f2->id],
            $form->viewData('flights')->pluck('id')->all()
        );
    }

    private function bookMultiCity(): Booking
    {
        $this->walkWizardToPassengerForm();

        $response = $this->post(route('booking.passengers.store'), $this->passengerPayload());
        $response->assertSessionHasNoErrors();

        $booking = Booking::latest('id')->firstOrFail();
        $response->assertRedirect(route('payment.show', $booking));

        return $booking;
    }

    public function test_full_flow_creates_one_booking_with_three_open_jaw_legs(): void
    {
        $booking = $this->bookMultiCity();

        $this->assertSame(1, Booking::count());
        $this->assertSame(3, BookingFlight::where('booking_id', $booking->id)->count());
        $this->assertNull(session('pending_selection'));

        // Xác nhận fixture đảo thứ tự id: booking_flight của chặng 3 có id nhỏ hơn chặng 1
        $bf0 = BookingFlight::where('booking_id', $booking->id)->where('flight_id', $this->f0->id)->firstOrFail();
        $bf2 = BookingFlight::where('booking_id', $booking->id)->where('flight_id', $this->f2->id)->firstOrFail();
        $this->assertLessThan($bf0->id, $bf2->id);

        // Mỗi chặng: 1 vé người lớn có ghế + 1 vé em bé không ghế
        foreach ([$this->f0, $this->f1, $this->f2] as $flight) {
            $bf = BookingFlight::where('booking_id', $booking->id)->where('flight_id', $flight->id)->firstOrFail();
            $this->assertSame(2, Ticket::where('booking_flight_id', $bf->id)->count());
            $this->assertSame(1, Ticket::where('booking_flight_id', $bf->id)->whereNotNull('flight_seat_id')->count());
        }

        // Em bé = 10% giá ghế người lớn trên CHÍNH chặng đó (ba chặng giá khác nhau)
        $infantPrices = Ticket::whereHas('passenger', fn ($q) => $q->where('passenger_type', 'infant'))
            ->orderBy('price')->pluck('price')->map(fn ($p) => (float) $p)->all();
        $this->assertEquals([100000.0, 120000.0, 150000.0], $infantPrices);

        // 1.000.000 + 1.200.000 + 1.500.000 + 100.000 + 120.000 + 150.000
        $this->assertEquals(4070000, $booking->fresh()->total_amount);

        // Giữ ghế cả ba chặng: mỗi chuyến đúng 1 ghế held
        foreach ([$this->f0, $this->f1, $this->f2] as $flight) {
            $this->assertSame(1, FlightSeat::where('flight_id', $flight->id)
                ->where('status', 'held')->where('held_by', $this->user->id)->count());
        }
    }

    public function test_payment_and_history_pages_show_three_legs_in_flight_order(): void
    {
        $booking = $this->bookMultiCity();

        $this->get(route('payment.show', $booking))
            ->assertOk()
            ->assertSeeInOrder(['Chặng 1', 'Chặng 2', 'Chặng 3'])
            ->assertDontSee('Chuyến về');

        $this->post(route('payment.store', $booking), ['method' => 'the_tin_dung', 'simulate_result' => 'success'])
            ->assertRedirect(route('booking-history.show', $booking));

        $this->get(route('booking-history.show', $booking))
            ->assertOk()
            ->assertSeeInOrder(['Chặng 1', 'Chặng 2', 'Chặng 3'])
            ->assertDontSee('Chuyến về')
            ->assertSee('Thành công');

        // Chặng 1 và 2 nối tiếp (SGN → HAN → DAD), chặng 3 đứt đoạn nên tách bằng dấu chấm giữa
        $this->get(route('booking-history.index'))
            ->assertOk()
            ->assertSee('SGN → HAN → DAD · PQC → SGN')
            ->assertSee('3 chặng')
            ->assertDontSee('Khứ hồi');
    }

    public function test_successful_payment_books_seats_on_all_three_legs(): void
    {
        $booking = $this->bookMultiCity();

        $this->post(route('payment.store', $booking), ['method' => 'the_tin_dung', 'simulate_result' => 'success']);

        $this->assertSame('paid', $booking->fresh()->status);
        $this->assertSame(3, FlightSeat::where('status', 'booked')->count());
        $this->assertSame(0, FlightSeat::where('status', 'held')->count());
    }

    public function test_expiry_releases_seats_on_all_three_legs(): void
    {
        $booking = $this->bookMultiCity();
        $this->assertSame(3, FlightSeat::where('status', 'held')->count());

        Booking::whereKey($booking->id)->update([
            'created_at' => now()->subMinutes(config('booking.seat_hold_minutes') + 1),
        ]);

        app(BookingExpiryService::class)->cancelIfExpired($booking->fresh());

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame(0, FlightSeat::where('status', 'held')->count());
        $this->assertSame(12, FlightSeat::where('status', 'available')->count()); // 3 chuyến x 4 ghế
        $this->assertSame(0, Ticket::whereNotNull('flight_seat_id')->count());
    }

    public function test_middle_leg_running_out_of_seats_rolls_back_every_leg(): void
    {
        $this->walkWizardToPassengerForm();

        // Chặng giữa hết ghế sau khi khách đã qua wizard
        FlightSeat::where('flight_id', $this->f1->id)->update(['status' => 'booked']);

        $this->post(route('booking.passengers.store'), $this->passengerPayload())
            ->assertSessionHas('error');

        $this->assertSame(0, Booking::count());
        $this->assertSame(0, Ticket::count());
        // Chặng 1 và 3 còn đủ ghế nhưng KHÔNG được giữ chỗ lại
        $this->assertSame(0, FlightSeat::where('status', 'held')->count());
        $this->assertSame(8, FlightSeat::where('status', 'available')->count());
    }

    public function test_store_rejects_when_middle_flight_cancelled_mid_flow(): void
    {
        $this->walkWizardToPassengerForm();

        $this->f1->update(['status' => 'cancelled']);

        $this->post(route('booking.passengers.store'), $this->passengerPayload())
            ->assertRedirect(route('booking.leg', 1))
            ->assertSessionHas('error');

        $this->assertSame(0, Booking::count());
        $this->assertSame(0, FlightSeat::where('status', 'held')->count());

        $pending = session('pending_selection');
        $this->assertSame($this->f0->id, $pending['legs'][0]['flight_id']); // chặng đi giữ nguyên
        $this->assertNull($pending['legs'][1]['flight_id']);                // chặng hỏng phải chọn lại
    }
}
