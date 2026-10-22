<?php

namespace App\Http\Requests;

use App\Services\PassportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

// Form khách gửi yêu cầu cập nhật hộ chiếu: dùng ĐÚNG bộ rule và chuẩn hóa của form đặt vé (PassportService)
class StorePassportUpdateRequest extends FormRequest
{
    // Chỉ chủ đơn mới gửi được; đơn hoặc hành khách không thuộc về mình thì 404 (giống trang chi tiết đơn)
    public function authorize(): bool
    {
        $booking = $this->user()->bookings()->find($this->route('id'));

        if (! $booking || ! $booking->passengers()->whereKey($this->route('passenger'))->exists()) {
            abort(404);
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $service = app(PassportService::class);
        $merge = [];

        if (is_string($this->input('full_name'))) {
            $merge['full_name'] = $service->normalizeName($this->input('full_name'));
        }
        if (is_string($this->input('document_number'))) {
            $merge['document_number'] = $service->normalizeDocumentNumber($this->input('document_number'));
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        // Không có document_type: yêu cầu này luôn là hộ chiếu
        return app(PassportService::class)->rules('', false) + [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! app(PassportService::class)->isExpiryAfterIssue($this->input('document_issue_date'), $this->input('document_expiry_date'))) {
                $validator->errors()->add('document_expiry_date', 'Ngày hết hạn phải sau ngày cấp hộ chiếu.');
            }
        }];
    }

    public function messages(): array
    {
        return app(PassportService::class)->messages('', false) + [
            'reason.required' => 'Vui lòng cho biết lý do cập nhật hộ chiếu.',
            'reason.max' => 'Lý do tối đa 500 ký tự.',
        ];
    }
}
