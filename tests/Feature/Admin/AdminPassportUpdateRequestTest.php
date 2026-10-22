<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Passenger;
use App\Models\PassportUpdateRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\PassportUpdateService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesInternationalBooking;
use Tests\TestCase;

class AdminPassportUpdateRequestTest extends TestCase
{
    use CreatesInternationalBooking;
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function newData(array $override = []): array
    {
        return array_merge([
            'full_name' => 'NGUYEN VAN B',
            'document_number' => 'B7654321',
            'date_of_birth' => '1991-02-03',
            'gender' => 'female',
            'nationality' => 'VNM',
            'document_issued_country' => 'THA',
            'document_issue_date' => '2025-06-01',
            'document_expiry_date' => '2035-05-31',
        ], $override);
    }

    private function pendingRequest(Booking $booking, Passenger $passenger, array $override = []): PassportUpdateRequest
    {
        return PassportUpdateRequest::create(array_merge([
            'booking_id' => $booking->id,
            'passenger_id' => $passenger->id,
            'user_id' => $booking->user_id,
            'new_data' => $this->newData(),
            'reason' => 'Vua duoc cap ho chieu moi',
            'status' => 'pending',
        ], $override));
    }

    private function approve(User $admin, PassportUpdateRequest $request, array $data = [])
    {
        return $this->actingAs($admin)->post(route('admin.passport-update-requests.approve', $request), $data);
    }

    private function reject(User $admin, PassportUpdateRequest $request, array $data = [])
    {
        return $this->actingAs($admin)->post(route('admin.passport-update-requests.reject', $request), $data);
    }

    // ---------- Duyệt ----------

    public function test_approve_updates_the_passenger_and_snapshots_the_old_data(): void
    {
        $admin = $this->admin();
        $booking = $this->bookingWithLegs(User::factory()->create());
        $passenger = $this->addPassenger($booking);
        $request = $this->pendingRequest($booking, $passenger);

        $this->approve($admin, $request, ['admin_note' => 'Da doi chieu ho chieu moi'])
            ->assertRedirect(route('admin.bookings.show', $booking->id))
            ->assertSessionHas('passport_status');

        $passenger->refresh();
        $this->assertSame('NGUYEN VAN B', $passenger->full_name);
        $this->assertSame('B7654321', $passenger->document_number);
        $this->assertSame('female', $passenger->gender);
        $this->assertSame('THA', $passenger->document_issued_country);
        $this->assertSame('1991-02-03', $passenger->date_of_birth->toDateString());
        $this->assertSame('2025-06-01', $passenger->document_issue_date->toDateString());
        $this->assertSame('2035-05-31', $passenger->document_expiry_date->toDateString());
        $this->assertSame('passport', $passenger->document_type);

        $request->refresh();
        $this->assertSame('approved', $request->status);
        $this->assertSame($admin->id, $request->admin_id);
        $this->assertSame('Da doi chieu ho chieu moi', $request->admin_note);
        $this->assertNotNull($request->resolved_at);
        // old_data chụp dữ liệu TRƯỚC khi sửa
        $this->assertSame('NGUYEN VAN A', $request->old_data['full_name']);
        $this->assertSame('C1234567', $request->old_data['document_number']);
        $this->assertSame('male', $request->old_data['gender']);
        $this->assertSame('VNM', $request->old_data['document_issued_country']);
        $this->assertSame('2020-01-15', $request->old_data['document_issue_date']);
        $this->assertSame('2030-01-14', $request->old_data['document_expiry_date']);
    }

    public function test_approve_without_a_note_is_allowed(): void
    {
        $booking = $this->bookingWithLegs(User::factory()->create());
        $request = $this->pendingRequest($booking, $this->addPassenger($booking));

        $this->approve($this->admin(), $request)->assertSessionHas('passport_status');

        $this->assertNull($request->fresh()->admin_note);
        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_approving_updates_the_tickets_of_every_leg_because_passengers_are_shared(): void
    {
        $booking = $this->bookingWithLegs(User::factory()->create(), [10, 20, 30]);
        $passenger = $this->addPassenger($booking);
        $bystander = $this->addPassenger($booking, ['full_name' => 'TRAN THI C', 'document_number' => 'D1111111']);
        $request = $this->pendingRequest($booking, $passenger);

        $this->approve($this->admin(), $request)->assertSessionHas('passport_status');

        $tickets = Ticket::where('passenger_id', $passenger->id)->get();
        $this->assertCount(3, $tickets);
        foreach ($tickets as $ticket) {
            $this->assertSame('B7654321', $ticket->passenger->document_number);
        }
        $this->assertDatabaseCount('passengers', 2); // không tạo hành khách mới
        $this->assertSame(6, Ticket::count());

        // Hành khách khác trong đơn không bị đụng tới
        $this->assertSame('D1111111', $bystander->fresh()->document_number);
        $this->assertSame('TRAN THI C', $bystander->fresh()->full_name);
    }

    public function test_second_approval_does_not_run_again_or_overwrite_old_data(): void
    {
        $admin = $this->admin();
        $booking = $this->bookingWithLegs(User::factory()->create());
        $passenger = $this->addPassenger($booking);
        $request = $this->pendingRequest($booking, $passenger);

        $this->approve($admin, $request)->assertSessionHas('passport_status');
        $oldData = $request->fresh()->old_data;

        // Hộ chiếu bị sửa tay sau đó; bấm duyệt lần hai không được ghi đè old_data hay đổi lại dữ liệu
        $passenger->update(['document_number' => 'Z0000000']);
        $this->approve($admin, $request)->assertSessionHas('passport_error', 'Yêu cầu này đã được xử lý trước đó.');

        $this->assertSame('Z0000000', $passenger->fresh()->document_number);
        $this->assertSame($oldData, $request->fresh()->old_data);
    }

    public function test_cannot_approve_a_request_that_was_rejected(): void
    {
        $admin = $this->admin();
        $booking = $this->bookingWithLegs(User::factory()->create());
        $passenger = $this->addPassenger($booking);
        $request = $this->pendingRequest($booking, $passenger);

        $this->reject($admin, $request, ['admin_note' => 'Anh mo'])->assertSessionHas('passport_status');
        $this->approve($admin, $request)->assertSessionHas('passport_error');

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('C1234567', $passenger->fresh()->document_number);
    }

    public function test_cannot_approve_when_the_booking_is_no_longer_paid(): void
    {
        $booking = $this->bookingWithLegs(User::factory()->create());
        $passenger = $this->addPassenger($booking);
        $request = $this->pendingRequest($booking, $passenger);
        $booking->update(['status' => 'cancelled']);

        $this->approve($this->admin(), $request)
            ->assertSessionHas('passport_error', 'Đơn không còn ở trạng thái đã thanh toán nên không thể duyệt yêu cầu này.');

        $this->assertSame('pending', $request->fresh()->status);
        $this->assertSame('C1234567', $passenger->fresh()->document_number);
    }

    public function test_approval_rolls_back_the_passenger_update_if_saving_the_request_fails(): void
    {
        $booking = $this->bookingWithLegs(User::factory()->create());
        $passenger = $this->addPassenger($booking);
        $request = $this->pendingRequest($booking, $passenger);

        // Admin không tồn tại: bước cuối (ghi admin_id vào yêu cầu) vi phạm khóa ngoại SAU KHI passengers đã được sửa
        $ghost = new User;
        $ghost->id = 999999;

        try {
            app(PassportUpdateService::class)->approve($request, $ghost);
            $this->fail('Đáng ra phải lỗi khóa ngoại admin_id');
        } catch (QueryException $e) {
            // mong đợi
        }

        // Cả transaction được rollback: hành khách còn nguyên, yêu cầu vẫn chờ duyệt, không có old_data
        $this->assertSame('NGUYEN VAN A', $passenger->fresh()->full_name);
        $this->assertSame('C1234567', $passenger->fresh()->document_number);
        $request->refresh();
        $this->assertSame('pending', $request->status);
        $this->assertNull($request->old_data);
    }

    // ---------- Từ chối ----------

    public function test_reject_keeps_the_passenger_unchanged_and_stores_the_note(): void
    {
        $admin = $this->admin();
        $booking = $this->bookingWithLegs(User::factory()->create());
        $passenger = $this->addPassenger($booking);
        $request = $this->pendingRequest($booking, $passenger);

        $this->reject($admin, $request, ['admin_note' => 'Anh ho chieu bi mo'])
            ->assertRedirect(route('admin.bookings.show', $booking->id))
            ->assertSessionHas('passport_status');

        $request->refresh();
        $this->assertSame('rejected', $request->status);
        $this->assertSame('Anh ho chieu bi mo', $request->admin_note);
        $this->assertSame($admin->id, $request->admin_id);
        $this->assertNotNull($request->resolved_at);
        $this->assertNull($request->old_data);

        $passenger->refresh();
        $this->assertSame('NGUYEN VAN A', $passenger->full_name);
        $this->assertSame('C1234567', $passenger->document_number);
        $this->assertSame('2030-01-14', $passenger->document_expiry_date->toDateString());
    }

    public function test_reject_requires_a_note(): void
    {
        $booking = $this->bookingWithLegs(User::factory()->create());
        $request = $this->pendingRequest($booking, $this->addPassenger($booking));

        $this->reject($this->admin(), $request, ['admin_note' => ''])->assertSessionHasErrors('admin_note');
        $this->reject($this->admin(), $request)->assertSessionHasErrors('admin_note');

        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_note_length_is_limited(): void
    {
        $booking = $this->bookingWithLegs(User::factory()->create());
        $request = $this->pendingRequest($booking, $this->addPassenger($booking));

        $this->reject($this->admin(), $request, ['admin_note' => str_repeat('a', 501)])->assertSessionHasErrors('admin_note');
        $this->approve($this->admin(), $request, ['admin_note' => str_repeat('a', 501)])->assertSessionHasErrors('admin_note');

        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_cannot_reject_a_request_that_was_already_approved(): void
    {
        $admin = $this->admin();
        $booking = $this->bookingWithLegs(User::factory()->create());
        $request = $this->pendingRequest($booking, $this->addPassenger($booking));

        $this->approve($admin, $request)->assertSessionHas('passport_status');
        $this->reject($admin, $request, ['admin_note' => 'Nham'])->assertSessionHas('passport_error');

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertNull($request->fresh()->admin_note);
    }

    // ---------- Phân quyền ----------

    public function test_regular_user_cannot_approve_or_reject(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $booking = $this->bookingWithLegs($owner);
        $passenger = $this->addPassenger($booking);
        $request = $this->pendingRequest($booking, $passenger);

        // Kể cả chủ đơn cũng không tự duyệt được
        $this->approve($owner, $request)->assertForbidden();
        $this->reject($owner, $request, ['admin_note' => 'x'])->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status);
        $this->assertSame('C1234567', $passenger->fresh()->document_number);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $booking = $this->bookingWithLegs(User::factory()->create());
        $request = $this->pendingRequest($booking, $this->addPassenger($booking));

        $this->post(route('admin.passport-update-requests.approve', $request))->assertRedirect(route('login'));
        $this->post(route('admin.passport-update-requests.reject', $request), ['admin_note' => 'x'])->assertRedirect(route('login'));

        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_unknown_request_returns_404(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/passport-update-requests/999999/approve')->assertNotFound();
        $this->actingAs($admin)->post('/admin/passport-update-requests/999999/reject', ['admin_note' => 'x'])->assertNotFound();
    }

    public function test_admin_cannot_use_get_on_the_decision_routes(): void
    {
        $booking = $this->bookingWithLegs(User::factory()->create());
        $request = $this->pendingRequest($booking, $this->addPassenger($booking));

        $this->actingAs($this->admin())->get('/admin/passport-update-requests/'.$request->id.'/approve')->assertStatus(405);
    }

    // ---------- Trang chi tiết đơn ----------

    public function test_booking_detail_lists_a_pending_request_with_old_and_new_values_and_forms(): void
    {
        $booking = $this->bookingWithLegs(User::factory()->create());
        $passenger = $this->addPassenger($booking);
        $request = $this->pendingRequest($booking, $passenger);

        $this->actingAs($this->admin())->get(route('admin.bookings.show', $booking))->assertOk()
            ->assertSee('Yêu cầu cập nhật hộ chiếu (1)')
            ->assertSee('1 đang chờ duyệt')
            ->assertSee('Vua duoc cap ho chieu moi')
            ->assertSee('C1234567')           // giá trị hiện tại
            ->assertSee('B7654321')           // giá trị mới
            ->assertSee('Thailand (THA)')
            ->assertSee('01/06/2025')
            ->assertSee(route('admin.passport-update-requests.approve', $request), false)
            ->assertSee(route('admin.passport-update-requests.reject', $request), false);
    }

    public function test_booking_detail_shows_before_and_after_for_an_approved_request(): void
    {
        $admin = $this->admin();
        $booking = $this->bookingWithLegs(User::factory()->create());
        $request = $this->pendingRequest($booking, $this->addPassenger($booking));
        $this->approve($admin, $request, ['admin_note' => 'Da doi chieu'])->assertRedirect();

        $this->actingAs($admin)->get(route('admin.bookings.show', $booking))->assertOk()
            ->assertSee('Đã duyệt')
            ->assertSee('Trước khi duyệt')
            ->assertSee('C1234567')           // old_data
            ->assertSee('B7654321')           // new_data
            ->assertSee('Da doi chieu')
            ->assertSee($admin->name)
            ->assertDontSee(route('admin.passport-update-requests.approve', $request), false); // đã xử lý nên không còn nút
    }

    public function test_booking_detail_shows_a_rejected_request_without_decision_buttons(): void
    {
        $admin = $this->admin();
        $booking = $this->bookingWithLegs(User::factory()->create());
        $request = $this->pendingRequest($booking, $this->addPassenger($booking));
        $this->reject($admin, $request, ['admin_note' => 'Anh ho chieu bi mo'])->assertRedirect();

        $this->actingAs($admin)->get(route('admin.bookings.show', $booking))->assertOk()
            ->assertSee('Đã từ chối')
            ->assertSee('Anh ho chieu bi mo')
            ->assertDontSee(route('admin.passport-update-requests.reject', $request), false);
    }

    public function test_booking_detail_without_requests_has_no_passport_update_card(): void
    {
        $booking = $this->bookingWithLegs(User::factory()->create());
        $this->addPassenger($booking);

        $this->actingAs($this->admin())->get(route('admin.bookings.show', $booking))->assertOk()
            ->assertDontSee('Yêu cầu cập nhật hộ chiếu');
    }

    public function test_reject_validation_error_is_shown_on_the_detail_page(): void
    {
        $admin = $this->admin();
        $booking = $this->bookingWithLegs(User::factory()->create());
        $request = $this->pendingRequest($booking, $this->addPassenger($booking));

        $this->from(route('admin.bookings.show', $booking))->actingAs($admin)
            ->post(route('admin.passport-update-requests.reject', $request), ['admin_note' => '', 'request_id' => $request->id])
            ->assertRedirect(route('admin.bookings.show', $booking));

        $this->actingAs($admin)->get(route('admin.bookings.show', $booking))->assertOk()
            ->assertSee('Vui lòng nhập ghi chú khi từ chối yêu cầu.');
    }

    public function test_booking_detail_does_not_run_n_plus_one_queries_for_requests(): void
    {
        $admin = $this->admin();
        $count = function (Booking $booking) use ($admin): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($admin)->get(route('admin.bookings.show', $booking))->assertOk();
            $n = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $n;
        };

        // Cả hai đơn đều có một yêu cầu đã xử lý (có admin): Laravel bỏ qua query của quan hệ admin khi không có admin_id nào
        $few = $this->bookingWithLegs(User::factory()->create());
        $this->pendingRequest($few, $this->addPassenger($few), ['status' => 'approved', 'admin_id' => $admin->id, 'resolved_at' => now()]);

        $many = $this->bookingWithLegs(User::factory()->create(), [10, 20]);
        foreach (range(1, 4) as $n) {
            $passenger = $this->addPassenger($many, ['document_number' => 'P000000'.$n]);
            $this->pendingRequest($many, $passenger);
        }
        $this->pendingRequest($many, $this->addPassenger($many, ['document_number' => 'P0000009']), ['status' => 'rejected', 'admin_id' => $admin->id, 'admin_note' => 'x', 'resolved_at' => now()]);

        $this->assertSame($count($few), $count($many));
    }

    public function test_admin_still_has_no_write_routes_on_the_booking_itself(): void
    {
        $booking = $this->bookingWithLegs(User::factory()->create());
        $this->addPassenger($booking);

        $this->actingAs($this->admin())->delete('/admin/bookings/'.$booking->id)->assertStatus(405);
        $this->actingAs($this->admin())->put('/admin/bookings/'.$booking->id)->assertStatus(405);
    }
}
