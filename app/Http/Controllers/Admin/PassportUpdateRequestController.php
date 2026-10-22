<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PassportUpdateRequest;
use App\Services\PassportUpdateService;
use Illuminate\Http\Request;

// Thao tác GHI duy nhất trong phần admin quản lý đơn (vốn chỉ xem): duyệt hoặc từ chối yêu cầu cập nhật hộ chiếu
class PassportUpdateRequestController extends Controller
{
    public function approve(Request $request, PassportUpdateRequest $passportUpdateRequest, PassportUpdateService $service)
    {
        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:500']]);

        try {
            $service->approve($passportUpdateRequest, $request->user(), $data['admin_note'] ?? null);
        } catch (\DomainException $e) {
            return $this->back($passportUpdateRequest)->with('passport_error', $e->getMessage());
        }

        return $this->back($passportUpdateRequest)
            ->with('passport_status', 'Đã duyệt yêu cầu và cập nhật thông tin hộ chiếu của hành khách.');
    }

    public function reject(Request $request, PassportUpdateRequest $passportUpdateRequest, PassportUpdateService $service)
    {
        $data = $request->validate(
            ['admin_note' => ['required', 'string', 'max:500']],
            ['admin_note.required' => 'Vui lòng nhập ghi chú khi từ chối yêu cầu.']
        );

        try {
            $service->reject($passportUpdateRequest, $request->user(), $data['admin_note']);
        } catch (\DomainException $e) {
            return $this->back($passportUpdateRequest)->with('passport_error', $e->getMessage());
        }

        return $this->back($passportUpdateRequest)->with('passport_status', 'Đã từ chối yêu cầu cập nhật hộ chiếu.');
    }

    private function back(PassportUpdateRequest $passportUpdateRequest)
    {
        return redirect()->route('admin.bookings.show', $passportUpdateRequest->booking_id);
    }
}
