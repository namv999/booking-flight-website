<?php

namespace App\Http\Requests;

use App\Services\PassportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PassengerBookingRequest extends FormRequest
{
    private ?bool $international = null;

    private function isInternational(): bool
    {
        if ($this->international === null) {
            $pending = session('pending_selection');
            $this->international = $pending ? app(PassportService::class)->isInternational($pending) : false;
        }

        return $this->international;
    }

    public function authorize(): bool
    {
        return true;
    }

    // Chỉ chuẩn hóa khi hành trình quốc tế; nội địa giữ nguyên dữ liệu người dùng nhập
    protected function prepareForValidation(): void
    {
        if (! $this->isInternational()) {
            return;
        }

        $service = app(PassportService::class);
        $merge = [];

        foreach (['adults', 'children', 'infants'] as $group) {
            $rows = $this->input($group);

            if (! is_array($rows)) {
                continue;
            }

            foreach ($rows as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (isset($row['full_name']) && is_string($row['full_name'])) {
                    $rows[$i]['full_name'] = $service->normalizeName($row['full_name']);
                }
                if (isset($row['document_number']) && is_string($row['document_number'])) {
                    $rows[$i]['document_number'] = $service->normalizeDocumentNumber($row['document_number']);
                }
            }

            $merge[$group] = $rows;
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $rules = [
            'adults' => ['required', 'array', 'min:1'],
            'adults.*.full_name' => ['required', 'string', 'max:150'],
            'adults.*.document_number' => ['nullable', 'string', 'max:30'],
            'adults.*.date_of_birth' => ['nullable', 'date'],
            'adults.*.baggage_addon_id' => ['nullable', 'integer', Rule::exists('baggage_addons', 'id')->whereNull('deleted_at')],

            'children' => ['nullable', 'array'],
            'children.*.full_name' => ['required', 'string', 'max:150'],
            'children.*.document_number' => ['nullable', 'string', 'max:30'],
            'children.*.date_of_birth' => ['nullable', 'date'],
            'children.*.baggage_addon_id' => ['nullable', 'integer', Rule::exists('baggage_addons', 'id')->whereNull('deleted_at')],

            // Em bé KHÔNG có rule baggage_addon_id: validated() bỏ qua field này nên em bé không thể mua gói
            'infants' => ['nullable', 'array'],
            'infants.*.full_name' => ['required', 'string', 'max:150'],
            'infants.*.document_number' => ['nullable', 'string', 'max:30'],
            'infants.*.date_of_birth' => ['nullable', 'date'],
            'infants.*.companion_adult_index' => [
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) {
                    $adultCount = count($this->input('adults', []));

                    if ($value >= $adultCount) {
                        $fail('Người đi cùng của em bé không hợp lệ.');
                    }
                },
            ],
        ];

        // Quốc tế: ghi đè rule của họ tên, số giấy tờ, ngày sinh bằng bộ Passport (đủ mọi hành khách)
        if ($this->isInternational()) {
            foreach (['adults', 'children', 'infants'] as $group) {
                $rules = array_merge($rules, app(PassportService::class)->rules("$group.*."));
            }
        }

        return $rules;
    }

    // So ngày hết hạn với ngày cấp trong hook (rule after:field không ổn định với wildcard)
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || ! $this->isInternational()) {
                return;
            }

            foreach (['adults', 'children', 'infants'] as $group) {
                foreach ($this->input($group, []) as $i => $row) {
                    if (! app(PassportService::class)->isExpiryAfterIssue($row['document_issue_date'], $row['document_expiry_date'])) {
                        $validator->errors()->add("$group.$i.document_expiry_date", 'Ngày hết hạn phải sau ngày cấp hộ chiếu.');
                    }
                }
            }
        }];
    }

    public function messages(): array
    {
        $messages = [];

        foreach (['adults', 'children', 'infants'] as $group) {
            $messages = array_merge($messages, app(PassportService::class)->messages("$group.*."));
            $messages["$group.*.baggage_addon_id.exists"] = 'Gói hành lý không tồn tại hoặc đã ngừng cung cấp.';
            $messages["$group.*.baggage_addon_id.integer"] = 'Gói hành lý không hợp lệ.';
        }

        return $messages;
    }
}
