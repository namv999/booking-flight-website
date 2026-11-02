<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\PassportUpdateRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesInternationalBooking;
use Tests\TestCase;

class PassportUpdateRequestTest extends TestCase
{
    use CreatesInternationalBooking;
    use RefreshDatabase;

    private function url(Booking $booking, $passenger): string
    {
        return route('booking-history.passport-update.store', [$booking->id, is_object($passenger) ? $passenger->id : $passenger]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner);
        $passenger = $this->addPassenger($booking);

        $this->post($this->url($booking, $passenger), $this->validPassportPayload())->assertRedirect(route('login'));
        $this->assertDatabaseCount('passport_update_requests', 0);
    }

    public function test_owner_can_submit_a_valid_request_and_data_is_normalized(): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner);
        $passenger = $this->addPassenger($booking);

        $this->actingAs($owner)->post($this->url($booking, $passenger), $this->validPassportPayload())
            ->assertRedirect(route('booking-history.show', $booking->id))
            ->assertSessionHas('passport_status');

        $request = PassportUpdateRequest::firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertSame($booking->id, $request->booking_id);
        $this->assertSame($passenger->id, $request->passenger_id);
        $this->assertSame($owner->id, $request->user_id);
        $this->assertSame('Vua duoc cap ho chieu moi', $request->reason);
        $this->assertNull($request->old_data);
        $this->assertNull($request->admin_id);
        $this->assertNull($request->resolved_at);

        // Chuẩn hóa giống form đặt vé: họ tên in hoa không dấu, số hộ chiếu in hoa chỉ chữ và số
        $this->assertSame('NGUYEN VAN B', $request->new_data['full_name']);
        $this->assertSame('B7654321', $request->new_data['document_number']);
        $this->assertSame('female', $request->new_data['gender']);
        $this->assertSame('THA', $request->new_data['document_issued_country']);
        $this->assertSame('1991-02-03', $request->new_data['date_of_birth']);
    }

    public function test_submitting_does_not_change_the_passenger_until_admin_approves(): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner);
        $passenger = $this->addPassenger($booking);

        $this->actingAs($owner)->post($this->url($booking, $passenger), $this->validPassportPayload())->assertRedirect();

        $passenger->refresh();
        $this->assertSame('NGUYEN VAN A', $passenger->full_name);
        $this->assertSame('C1234567', $passenger->document_number);
    }

    public function test_accented_vietnamese_name_is_normalized_like_the_booking_form(): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner);
        $passenger = $this->addPassenger($booking);

        $this->actingAs($owner)->post($this->url($booking, $passenger), $this->validPassportPayload(['full_name' => '  Nguyễn  Văn Đạt ']))->assertRedirect();

        $this->assertSame('NGUYEN VAN DAT', PassportUpdateRequest::firstOrFail()->new_data['full_name']);
    }

    public function test_cannot_submit_for_someone_elses_booking(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $booking = $this->bookingWithLegs($owner);
        $passenger = $this->addPassenger($booking);

        $this->actingAs($stranger)->post($this->url($booking, $passenger), $this->validPassportPayload())->assertNotFound();

        $this->assertDatabaseCount('passport_update_requests', 0);
    }

    public function test_cannot_submit_for_a_passenger_of_another_booking(): void
    {
        $owner = User::factory()->create();
        $mine = $this->bookingWithLegs($owner);
        $this->addPassenger($mine);
        $other = $this->bookingWithLegs($owner);
        $otherPassenger = $this->addPassenger($other);

        $this->actingAs($owner)->post($this->url($mine, $otherPassenger), $this->validPassportPayload())->assertNotFound();
        $this->actingAs($owner)->post($this->url($mine, 999999), $this->validPassportPayload())->assertNotFound();

        $this->assertDatabaseCount('passport_update_requests', 0);
    }

    public function test_cannot_submit_for_unpaid_or_cancelled_booking(): void
    {
        $owner = User::factory()->create();

        foreach (['pending', 'cancelled'] as $status) {
            $booking = $this->bookingWithLegs($owner, [10], true, ['status' => $status]);
            $passenger = $this->addPassenger($booking);

            $this->actingAs($owner)->post($this->url($booking, $passenger), $this->validPassportPayload())
                ->assertRedirect(route('booking-history.show', $booking->id))
                ->assertSessionHas('passport_error', 'Chỉ đơn đã thanh toán mới có thể gửi yêu cầu cập nhật hộ chiếu.');
        }

        $this->assertDatabaseCount('passport_update_requests', 0);
    }

    public function test_cannot_submit_after_the_first_flight_has_departed(): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner, [-1]);
        $passenger = $this->addPassenger($booking);

        $this->actingAs($owner)->post($this->url($booking, $passenger), $this->validPassportPayload())
            ->assertSessionHas('passport_error', 'Chuyến bay đầu tiên đã cất cánh, không thể gửi yêu cầu cập nhật hộ chiếu.');

        $this->assertDatabaseCount('passport_update_requests', 0);
    }

    public function test_it_is_the_earliest_leg_that_matters_not_the_last(): void
    {
        $owner = User::factory()->create();

        // Chuyến đầu đã bay (dù chuyến về còn xa) thì không gửi được
        $departed = $this->bookingWithLegs($owner, [10, -2]); // chuyến thứ 2 tạo sau nhưng bay sớm nhất
        $p1 = $this->addPassenger($departed);
        $this->actingAs($owner)->post($this->url($departed, $p1), $this->validPassportPayload())->assertSessionHas('passport_error');

        // Chuyến đầu chưa bay thì gửi được, dù đơn có nhiều chặng
        $upcoming = $this->bookingWithLegs($owner, [5, 20]);
        $p2 = $this->addPassenger($upcoming);
        $this->actingAs($owner)->post($this->url($upcoming, $p2), $this->validPassportPayload())->assertSessionHas('passport_status');

        $this->assertDatabaseCount('passport_update_requests', 1);
    }

    public function test_domestic_booking_has_nothing_to_update(): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner, [10], false);
        $passenger = $this->addPassenger($booking, ['document_type' => 'national_id', 'document_number' => '001234567890', 'gender' => null, 'nationality' => null]);

        $this->actingAs($owner)->post($this->url($booking, $passenger), $this->validPassportPayload())
            ->assertSessionHas('passport_error');

        $this->assertDatabaseCount('passport_update_requests', 0);
    }

    public function test_only_one_pending_request_per_passenger(): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner);
        $first = $this->addPassenger($booking);
        $second = $this->addPassenger($booking, ['full_name' => 'TRAN THI C', 'document_number' => 'D1111111']);

        $this->actingAs($owner)->post($this->url($booking, $first), $this->validPassportPayload())->assertSessionHas('passport_status');
        $this->actingAs($owner)->post($this->url($booking, $first), $this->validPassportPayload(['document_number' => 'X9999999']))
            ->assertSessionHas('passport_error', 'Hành khách này đã có một yêu cầu đang chờ duyệt.');

        $this->assertSame(1, PassportUpdateRequest::where('passenger_id', $first->id)->count());

        // Hành khách khác trong cùng đơn vẫn gửi được
        $this->actingAs($owner)->post($this->url($booking, $second), $this->validPassportPayload())->assertSessionHas('passport_status');
        $this->assertDatabaseCount('passport_update_requests', 2);
    }

    public function test_passenger_can_submit_again_after_the_previous_request_was_resolved(): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner);
        $passenger = $this->addPassenger($booking);

        $this->actingAs($owner)->post($this->url($booking, $passenger), $this->validPassportPayload())->assertSessionHas('passport_status');
        PassportUpdateRequest::firstOrFail()->update(['status' => 'rejected', 'admin_note' => 'Anh mo', 'resolved_at' => now()]);

        $this->actingAs($owner)->post($this->url($booking, $passenger), $this->validPassportPayload(['document_number' => 'E2222222']))
            ->assertSessionHas('passport_status');

        $this->assertDatabaseCount('passport_update_requests', 2);
        $this->assertSame(1, PassportUpdateRequest::pending()->count());
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_data_is_rejected_with_the_booking_form_rules(array $override, string $errorField): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner);
        $passenger = $this->addPassenger($booking);

        $this->actingAs($owner)->post($this->url($booking, $passenger), $this->validPassportPayload($override))
            ->assertSessionHasErrors($errorField);

        $this->assertDatabaseCount('passport_update_requests', 0);
    }

    public static function invalidPayloads(): array
    {
        return [
            'tên có chữ số' => [['full_name' => 'NGUYEN 123'], 'full_name'],
            'tên rỗng' => [['full_name' => ''], 'full_name'],
            'số hộ chiếu quá ngắn' => [['document_number' => 'A123'], 'document_number'],
            'số hộ chiếu quá dài' => [['document_number' => 'A12345678901'], 'document_number'],
            'thiếu số hộ chiếu' => [['document_number' => ''], 'document_number'],
            'quốc tịch lạ' => [['nationality' => 'XXX'], 'nationality'],
            'nước cấp lạ' => [['document_issued_country' => 'ZZZ'], 'document_issued_country'],
            'giới tính sai' => [['gender' => 'other'], 'gender'],
            'ngày sinh tương lai' => [['date_of_birth' => '2999-01-01'], 'date_of_birth'],
            'ngày cấp tương lai' => [['document_issue_date' => '2999-01-01'], 'document_issue_date'],
            'thiếu ngày hết hạn' => [['document_expiry_date' => ''], 'document_expiry_date'],
            'thiếu lý do' => [['reason' => ''], 'reason'],
            'lý do quá dài' => [['reason' => str_repeat('a', 501)], 'reason'],
        ];
    }

    public function test_expiry_must_be_after_issue_date(): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner);
        $passenger = $this->addPassenger($booking);
        $issue = now()->subYears(2)->toDateString();

        foreach ([$issue, now()->subYears(3)->toDateString()] as $expiry) {
            $this->actingAs($owner)->post($this->url($booking, $passenger), $this->validPassportPayload([
                'document_issue_date' => $issue,
                'document_expiry_date' => $expiry,
            ]))->assertSessionHasErrors(['document_expiry_date' => 'Ngày hết hạn phải sau ngày cấp hộ chiếu.']);
        }

        $this->assertDatabaseCount('passport_update_requests', 0);
    }

    // ---------- Trang lịch sử đặt vé ----------

    public function test_history_page_shows_the_form_for_a_paid_international_booking(): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner);
        $passenger = $this->addPassenger($booking);

        $this->actingAs($owner)->get(route('booking-history.show', $booking->id))->assertOk()
            ->assertSee('Cập nhật hộ chiếu')
            ->assertSee('Gửi yêu cầu cập nhật')
            ->assertSee(e($this->url($booking, $passenger)), false)
            ->assertSee('name="document_number"', false)
            ->assertSee('value="C1234567"', false);
    }

    public function test_history_page_has_no_passport_section_for_domestic_or_unpaid_bookings(): void
    {
        $owner = User::factory()->create();

        $domestic = $this->bookingWithLegs($owner, [10], false);
        $this->addPassenger($domestic, ['document_type' => 'national_id']);
        $this->actingAs($owner)->get(route('booking-history.show', $domestic->id))->assertOk()->assertDontSee('id="passport-update"', false);

        $unpaid = $this->bookingWithLegs($owner, [10], true, ['status' => 'pending']);
        $this->addPassenger($unpaid);
        $this->actingAs($owner)->get(route('booking-history.show', $unpaid->id))->assertOk()->assertDontSee('id="passport-update"', false);
    }

    public function test_history_page_hides_the_form_and_explains_once_the_flight_departed(): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner, [-1]);
        $this->addPassenger($booking);

        $this->actingAs($owner)->get(route('booking-history.show', $booking->id))->assertOk()
            ->assertSee('id="passport-update"', false)
            ->assertSee('Chuyến bay đầu tiên đã cất cánh')
            ->assertDontSee('Gửi yêu cầu cập nhật');
    }

    public function test_history_page_shows_pending_status_and_hides_the_form_for_that_passenger_only(): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner);
        $waiting = $this->addPassenger($booking);
        $free = $this->addPassenger($booking, ['full_name' => 'TRAN THI C', 'document_number' => 'D1111111']);
        $this->actingAs($owner)->post($this->url($booking, $waiting), $this->validPassportPayload())->assertRedirect();

        $html = $this->actingAs($owner)->get(route('booking-history.show', $booking->id))->assertOk()
            ->assertSee('Đang chờ duyệt')
            ->assertSee('Lý do:')
            ->getContent();

        $this->assertStringNotContainsString(e($this->url($booking, $waiting)), $html);
        $this->assertStringContainsString(e($this->url($booking, $free)), $html);
    }

    public function test_history_page_shows_admins_note_after_rejection(): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner);
        $passenger = $this->addPassenger($booking);
        PassportUpdateRequest::create([
            'booking_id' => $booking->id, 'passenger_id' => $passenger->id, 'user_id' => $owner->id,
            'new_data' => ['full_name' => 'NGUYEN VAN B'], 'reason' => 'Nhap sai', 'status' => 'rejected',
            'admin_note' => 'Anh ho chieu bi mo, vui long gui lai', 'resolved_at' => now(),
        ]);

        $this->actingAs($owner)->get(route('booking-history.show', $booking->id))->assertOk()
            ->assertSee('Bị từ chối')
            ->assertSee('Anh ho chieu bi mo, vui long gui lai')
            ->assertSee('Gửi yêu cầu cập nhật'); // đã xử lý xong nên được gửi lại
    }

    public function test_history_page_keeps_old_input_only_in_the_failed_passengers_form(): void
    {
        $owner = User::factory()->create();
        $booking = $this->bookingWithLegs($owner);
        $failed = $this->addPassenger($booking);
        $this->addPassenger($booking, ['full_name' => 'TRAN THI C', 'document_number' => 'D1111111']);

        $this->from(route('booking-history.show', $booking->id))->actingAs($owner)
            ->post($this->url($booking, $failed), $this->validPassportPayload(['document_number' => 'A1', 'passenger_id' => $failed->id]))
            ->assertRedirect(route('booking-history.show', $booking->id))
            ->assertSessionHasErrors('document_number');

        $html = $this->actingAs($owner)->get(route('booking-history.show', $booking->id))->assertOk()
            ->assertSee('Số hộ chiếu gồm 6 đến 9 ký tự chữ và số.')
            ->getContent();

        $this->assertSame(1, substr_count($html, 'Số hộ chiếu gồm 6 đến 9 ký tự chữ và số.'));
    }
}
