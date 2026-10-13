<?php

namespace App\Http\Requests;

use App\Services\PassportService;
use Carbon\Carbon;
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

            'children' => ['nullable', 'array'],
            'children.*.full_name' => ['required', 'string', 'max:150'],
            'children.*.document_number' => ['nullable', 'string', 'max:30'],
            'children.*.date_of_birth' => ['nullable', 'date'],

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
                $rules = array_merge($rules, $this->passportRules($group));
            }
        }

        return $rules;
    }

    private function passportRules(string $group): array
    {
        $countries = array_keys(config('countries'));

        return [
            "$group.*.full_name" => ['required', 'string', 'max:150', 'regex:/^[A-Z][A-Z \'\-]*$/'],
            "$group.*.document_type" => ['required', 'in:passport'],
            "$group.*.document_number" => ['required', 'string', 'regex:/^[A-Z0-9]{6,9}$/'],
            "$group.*.date_of_birth" => ['required', 'date', 'before_or_equal:today'],
            "$group.*.gender" => ['required', 'in:male,female'],
            "$group.*.nationality" => ['required', Rule::in($countries)],
            "$group.*.document_issued_country" => ['required', Rule::in($countries)],
            "$group.*.document_issue_date" => ['required', 'date', 'before_or_equal:today'],
            "$group.*.document_expiry_date" => ['required', 'date'],
        ];
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
                    if (Carbon::parse($row['document_expiry_date'])->lte(Carbon::parse($row['document_issue_date']))) {
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
            $messages["$group.*.full_name.regex"] = 'Họ tên phải viết không dấu theo hộ chiếu (chỉ chữ A-Z).';
            $messages["$group.*.document_type.in"] = 'Chuyến bay quốc tế yêu cầu hộ chiếu.';
            $messages["$group.*.document_number.regex"] = 'Số hộ chiếu gồm 6 đến 9 ký tự chữ và số.';
            $messages["$group.*.nationality.in"] = 'Quốc tịch không hợp lệ.';
            $messages["$group.*.document_issued_country.in"] = 'Quốc gia cấp không hợp lệ.';
        }

        return $messages;
    }
}
