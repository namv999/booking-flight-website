<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\Booking;
use App\Models\FareClass;
use App\Models\Flight;
use App\Models\FlightSeat;
use App\Models\Passenger;
use App\Models\User;
use App\Services\PassportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassportBookingTest extends TestCase
{
    use RefreshDatabase;

    // Chuyến SGN-like (Vietnam) -> Thailand, đã chọn xong trong session
    private function internationalSetup(int $adults = 1, int $children = 0, int $infants = 0): User
    {
        $user = User::factory()->create();
        $vn = Airport::factory()->create();
        $th = Airport::factory()->foreign()->create();
        $flight = Flight::factory()->create([
            'departure_airport_id' => $vn->id,
            'arrival_airport_id' => $th->id,
        ]);
        $fareClass = FareClass::factory()->create();

        FlightSeat::factory()->count(3)->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'price' => 1500000,
            'status' => 'available',
        ]);

        session(['pending_selection' => $this->pendingSelection($flight, $fareClass, $adults, $children, $infants)]);

        return $user;
    }

    private function passportRow(array $override = []): array
    {
        return array_merge([
            'full_name' => 'NGUYEN VAN A',
            'document_type' => 'passport',
            'document_number' => 'C1234567',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'nationality' => 'VNM',
            'document_issued_country' => 'VNM',
            'document_issue_date' => now()->subYears(2)->toDateString(),
            'document_expiry_date' => now()->addYears(8)->toDateString(),
        ], $override);
    }

    public function test_international_booking_saves_normalized_passport_fields(): void
    {
        $user = $this->internationalSetup();

        $response = $this->actingAs($user)->post(route('booking.passengers.store'), [
            'adults' => [$this->passportRow([
                'full_name' => 'Nguyễn  Văn Á',
                'document_number' => 'c 123-4567',
            ])],
        ]);

        $response->assertSessionHasNoErrors();
        $passenger = Passenger::firstOrFail();

        $this->assertSame('NGUYEN VAN A', $passenger->full_name);
        $this->assertSame('C1234567', $passenger->document_number);
        $this->assertSame('passport', $passenger->document_type);
        $this->assertSame('male', $passenger->gender);
        $this->assertSame('VNM', $passenger->nationality);
        $this->assertSame('VNM', $passenger->document_issued_country);
        $this->assertSame(now()->addYears(8)->toDateString(), $passenger->document_expiry_date->toDateString());
        $this->assertSame(now()->subYears(2)->toDateString(), $passenger->document_issue_date->toDateString());
    }

    public function test_international_requires_all_passport_fields(): void
    {
        $user = $this->internationalSetup();

        $response = $this->actingAs($user)->post(route('booking.passengers.store'), [
            'adults' => [['full_name' => 'Nguyen Van A']],
        ]);

        $response->assertSessionHasErrors([
            'adults.0.document_type',
            'adults.0.document_number',
            'adults.0.date_of_birth',
            'adults.0.gender',
            'adults.0.nationality',
            'adults.0.document_issued_country',
            'adults.0.document_issue_date',
            'adults.0.document_expiry_date',
        ]);
        $this->assertSame(0, Booking::count());
    }

    public function test_international_rejects_national_id(): void
    {
        $user = $this->internationalSetup();

        $this->actingAs($user)->post(route('booking.passengers.store'), [
            'adults' => [$this->passportRow(['document_type' => 'national_id'])],
        ])->assertSessionHasErrors('adults.0.document_type');
    }

    public function test_unknown_country_code_is_rejected(): void
    {
        $user = $this->internationalSetup();

        $this->actingAs($user)->post(route('booking.passengers.store'), [
            'adults' => [$this->passportRow(['nationality' => 'XXX'])],
        ])->assertSessionHasErrors('adults.0.nationality');
    }

    public function test_expiry_date_must_be_after_issue_date(): void
    {
        $user = $this->internationalSetup();

        $this->actingAs($user)->post(route('booking.passengers.store'), [
            'adults' => [$this->passportRow([
                'document_issue_date' => now()->subDay()->toDateString(),
                'document_expiry_date' => now()->subDays(2)->toDateString(),
            ])],
        ])->assertSessionHasErrors('adults.0.document_expiry_date');
    }

    public function test_name_with_invalid_characters_is_rejected(): void
    {
        $user = $this->internationalSetup();

        $this->actingAs($user)->post(route('booking.passengers.store'), [
            'adults' => [$this->passportRow(['full_name' => '12345'])],
        ])->assertSessionHasErrors('adults.0.full_name');
    }

    public function test_infant_must_also_provide_passport_data(): void
    {
        $user = $this->internationalSetup(1, 0, 1);

        $this->actingAs($user)->post(route('booking.passengers.store'), [
            'adults' => [$this->passportRow()],
            'infants' => [['full_name' => 'Nguyen Be B', 'companion_adult_index' => 0]],
        ])->assertSessionHasErrors(['infants.0.document_number', 'infants.0.gender']);
    }

    public function test_domestic_booking_ignores_passport_fields_and_keeps_name(): void
    {
        $user = User::factory()->create();
        $flight = Flight::factory()->create(); // cả hai sân bay đều Vietnam
        $fareClass = FareClass::factory()->create();
        FlightSeat::factory()->create([
            'flight_id' => $flight->id,
            'fare_class_id' => $fareClass->id,
            'price' => 1500000,
            'status' => 'available',
        ]);
        session(['pending_selection' => $this->pendingSelection($flight, $fareClass, 1, 0, 0)]);

        $this->actingAs($user)->post(route('booking.passengers.store'), [
            'adults' => [['full_name' => 'Nguyen Van A', 'gender' => 'male', 'nationality' => 'VNM']],
        ])->assertSessionHasNoErrors();

        $passenger = Passenger::firstOrFail();
        $this->assertSame('Nguyen Van A', $passenger->full_name); // không bị chuẩn hóa
        $this->assertNull($passenger->gender);                    // field Passport bị bỏ qua khi nội địa
        $this->assertNull($passenger->nationality);
    }

    public function test_is_international_detects_country_difference(): void
    {
        $vn1 = Airport::factory()->create();
        $vn2 = Airport::factory()->create();
        $th = Airport::factory()->foreign()->create();
        $service = app(PassportService::class);

        $domestic = ['legs' => [['from' => $vn1->id, 'to' => $vn2->id]]];
        $international = ['legs' => [['from' => $vn1->id, 'to' => $th->id]]];
        // Chỉ một chặng quốc tế trong hành trình nhiều chặng cũng tính là quốc tế
        $mixed = ['legs' => [
            ['from' => $vn1->id, 'to' => $vn2->id],
            ['from' => $vn2->id, 'to' => $th->id],
        ]];

        $this->assertFalse($service->isInternational($domestic));
        $this->assertTrue($service->isInternational($international));
        $this->assertTrue($service->isInternational($mixed));
    }

    public function test_normalize_name_and_document_number(): void
    {
        $service = app(PassportService::class);

        $this->assertSame('DANG THI BAO NGOC', $service->normalizeName('Đặng Thị Bảo Ngọc'));
        $this->assertSame('TRAN VAN B', $service->normalizeName('  tran   van  b '));
        $this->assertSame('C1234567', $service->normalizeDocumentNumber(' c-123 4567 '));
        $this->assertNull($service->normalizeName(null));
    }

    public function test_passenger_form_receives_international_flag_and_countries(): void
    {
        $user = $this->internationalSetup();

        $response = $this->actingAs($user)->get(route('booking.passengers.form'))->assertOk();

        $this->assertTrue($response->viewData('isInternational'));
        $this->assertArrayHasKey('VNM', $response->viewData('countries'));
    }
}
