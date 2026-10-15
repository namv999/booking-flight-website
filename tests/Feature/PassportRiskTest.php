<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\Booking;
use App\Models\BookingFlight;
use App\Models\Flight;
use App\Models\Passenger;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\PassportRiskService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassportRiskTest extends TestCase
{
    use RefreshDatabase;

    private Airport $vn;

    private Airport $th;

    private Carbon $ref; // ngày hạ cánh chặng quốc tế (00:00 UTC, hạ cánh 05:00 UTC nên cùng ngày local UTC+7)

    protected function setUp(): void
    {
        parent::setUp();

        $this->vn = Airport::factory()->create();
        $this->th = Airport::factory()->foreign()->create();
        $this->ref = now('UTC')->addDays(10)->startOfDay();
    }

    // $legs: [[from, to, dayOffset], ...]; $expiries: ngày hết hạn từng hành khách (Y-m-d)
    private function bookingWith(array $legs, array $expiries, ?User $user = null): Booking
    {
        $user ??= User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        foreach ($legs as [$from, $to, $offset]) {
            $day = $this->ref->copy()->addDays($offset);
            $flight = Flight::factory()->create([
                'departure_airport_id' => $from->id,
                'arrival_airport_id' => $to->id,
                'departure_time' => $day->copy()->setTime(1, 0)->toDateTimeString(),
                'arrival_time' => $day->copy()->setTime(5, 0)->toDateTimeString(),
                'status' => 'scheduled',
            ]);
            BookingFlight::factory()->create(['booking_id' => $booking->id, 'flight_id' => $flight->id]);
        }

        foreach (array_values($expiries) as $i => $expiry) {
            Passenger::factory()->create([
                'booking_id' => $booking->id,
                'full_name' => 'PAX '.($i + 1),
                'passenger_type' => 'adult',
                'document_type' => 'passport',
                'document_expiry_date' => $expiry,
            ]);
        }

        return $booking;
    }

    private function assess(Booking $booking): array
    {
        return app(PassportRiskService::class)->assess($booking);
    }

    private function monthsAfterRef(int $m, int $days = 0): string
    {
        return $this->ref->copy()->addMonthsNoOverflow($m)->addDays($days)->toDateString();
    }

    public function test_passport_valid_for_more_than_six_months_has_no_risk(): void
    {
        $booking = $this->bookingWith([[$this->vn, $this->th, 0]], [$this->monthsAfterRef(12)]);

        $result = $this->assess($booking);

        $this->assertNull($result['level']);
        $this->assertSame([], $result['items']);
    }

    public function test_passport_under_six_months_is_yellow(): void
    {
        $booking = $this->bookingWith([[$this->vn, $this->th, 0]], [$this->monthsAfterRef(3)]);

        $result = $this->assess($booking);

        $this->assertSame('yellow', $result['level']);
        $this->assertSame('yellow', $result['items'][0]['level']);
        $this->assertSame($this->ref->toDateString(), $result['ref_date']);
    }

    public function test_passport_expired_before_arrival_is_red(): void
    {
        $booking = $this->bookingWith([[$this->vn, $this->th, 0]], [$this->ref->copy()->subDay()->toDateString()]);

        $this->assertSame('red', $this->assess($booking)['level']);
    }

    public function test_boundaries_between_levels(): void
    {
        $cases = [
            [$this->ref->copy()->subDay()->toDateString(), 'red'],
            [$this->ref->toDateString(), 'yellow'],              // hết hạn đúng ngày hạ cánh: chưa red
            [$this->monthsAfterRef(6, -1), 'yellow'],
            [$this->monthsAfterRef(6), null],                    // đủ đúng 6 tháng: không cảnh báo
        ];

        foreach ($cases as [$expiry, $expected]) {
            $booking = $this->bookingWith([[$this->vn, $this->th, 0]], [$expiry]);
            $this->assertSame($expected, $this->assess($booking)['level'], "expiry $expiry");
        }
    }

    public function test_six_month_date_is_expiry_minus_six_months(): void
    {
        $expiry = $this->monthsAfterRef(3);
        $booking = $this->bookingWith([[$this->vn, $this->th, 0]], [$expiry]);

        $this->assertSame(
            Carbon::parse($expiry)->subMonthsNoOverflow(6)->toDateString(),
            $this->assess($booking)['items'][0]['six_month_date']
        );
    }

    public function test_domestic_booking_never_has_risk(): void
    {
        $vn2 = Airport::factory()->create();
        $booking = $this->bookingWith([[$this->vn, $vn2, 0]], [$this->ref->copy()->subYear()->toDateString()]);

        $this->assertNull($this->assess($booking)['level']);
    }

    public function test_reference_date_is_arrival_of_last_international_leg(): void
    {
        // đi ngày 0, về ngày 10; hộ chiếu hết hạn sau ngày đi 6 tháng 5 ngày
        $booking = $this->bookingWith(
            [[$this->vn, $this->th, 0], [$this->th, $this->vn, 10]],
            [$this->monthsAfterRef(6, 5)]
        );

        $result = $this->assess($booking);

        $this->assertSame($this->ref->copy()->addDays(10)->toDateString(), $result['ref_date']);
        $this->assertSame('yellow', $result['level']); // nếu chỉ tính chặng đi thì không có cảnh báo
    }

    public function test_worst_level_wins_and_every_flagged_passenger_is_listed(): void
    {
        $booking = $this->bookingWith(
            [[$this->vn, $this->th, 0]],
            [$this->monthsAfterRef(12), $this->monthsAfterRef(3), $this->ref->copy()->subDay()->toDateString()]
        );

        $result = $this->assess($booking);

        $this->assertSame('red', $result['level']);
        $this->assertCount(2, $result['items']);
        $this->assertSame(['PAX 2', 'PAX 3'], collect($result['items'])->pluck('passenger.full_name')->all());
    }

    public function test_payment_page_receives_risk(): void
    {
        $user = User::factory()->create();
        $booking = $this->bookingWith([[$this->vn, $this->th, 0]], [$this->monthsAfterRef(3)], $user);

        $response = $this->actingAs($user)->get(route('payment.show', $booking))->assertOk();

        $this->assertSame('yellow', $response->viewData('passportRisk')['level']);
    }

    public function test_payment_requires_confirmation_when_risk_exists(): void
    {
        $user = User::factory()->create();
        $booking = $this->bookingWith([[$this->vn, $this->th, 0]], [$this->monthsAfterRef(3)], $user);
        $payload = ['method' => 'the_tin_dung', 'simulate_result' => 'success'];

        $this->actingAs($user)->post(route('payment.store', $booking), $payload)
            ->assertSessionHasErrors('confirm_passport_risk');

        $this->assertSame(0, Payment::count());
        $this->assertSame('pending', $booking->fresh()->status);

        $this->post(route('payment.store', $booking), $payload + ['confirm_passport_risk' => '1'])
            ->assertRedirect(route('booking-history.show', $booking));

        $this->assertSame('paid', $booking->fresh()->status);
    }

    public function test_payment_without_risk_needs_no_confirmation(): void
    {
        $user = User::factory()->create();
        $booking = $this->bookingWith([[$this->vn, $this->th, 0]], [$this->monthsAfterRef(12)], $user);

        $this->actingAs($user)
            ->post(route('payment.store', $booking), ['method' => 'the_tin_dung', 'simulate_result' => 'success'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('booking-history.show', $booking));
    }

    public function test_retry_after_failed_payment_still_requires_confirmation(): void
    {
        $user = User::factory()->create();
        $booking = $this->bookingWith([[$this->vn, $this->th, 0]], [$this->monthsAfterRef(3)], $user);

        $this->actingAs($user)->post(route('payment.store', $booking), [
            'method' => 'the_tin_dung',
            'simulate_result' => 'failed',
            'confirm_passport_risk' => '1',
        ])->assertRedirect(route('payment.show', $booking));

        // lần thử lại không tick: server vẫn phải chặn
        $this->post(route('payment.store', $booking), ['method' => 'the_tin_dung', 'simulate_result' => 'success'])
            ->assertSessionHasErrors('confirm_passport_risk');

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_payment_page_shows_yellow_warning_and_checkbox(): void
    {
        $user = User::factory()->create();
        $booking = $this->bookingWith([[$this->vn, $this->th, 0]], [$this->monthsAfterRef(3)], $user);

        $this->actingAs($user)->get(route('payment.show', $booking))
            ->assertOk()
            ->assertSee('alert-warning', false)
            ->assertSee('confirm_passport_risk', false)
            ->assertSee('PAX 1');
    }

    public function test_payment_page_shows_red_warning_for_expired_passport(): void
    {
        $user = User::factory()->create();
        $booking = $this->bookingWith([[$this->vn, $this->th, 0]], [$this->ref->copy()->subDay()->toDateString()], $user);

        $this->actingAs($user)->get(route('payment.show', $booking))
            ->assertOk()
            ->assertSee('alert-danger', false)
            ->assertSee('confirm_passport_risk', false);
    }

    public function test_payment_page_has_no_warning_or_checkbox_without_risk(): void
    {
        $user = User::factory()->create();
        $booking = $this->bookingWith([[$this->vn, $this->th, 0]], [$this->monthsAfterRef(12)], $user);

        $this->actingAs($user)->get(route('payment.show', $booking))
            ->assertOk()
            ->assertDontSee('confirm_passport_risk', false);
    }

    public function test_history_shows_passport_details_on_ticket(): void
    {
        $user = User::factory()->create();
        $booking = $this->bookingWith([[$this->vn, $this->th, 0]], [$this->monthsAfterRef(12)], $user);
        $passenger = Passenger::where('booking_id', $booking->id)->firstOrFail();
        $passenger->update(['document_number' => 'C1234567', 'nationality' => 'VNM']);
        $bookingFlight = BookingFlight::where('booking_id', $booking->id)->firstOrFail();

        Ticket::factory()->create([
            'booking_flight_id' => $bookingFlight->id,
            'passenger_id' => $passenger->id,
            'flight_seat_id' => null,
            'price' => 1000000,
        ]);

        $this->actingAs($user)->get(route('booking-history.show', $booking))
            ->assertOk()
            ->assertSee('Hộ chiếu C1234567')
            ->assertSee('Vietnam (VNM)');
    }
}
