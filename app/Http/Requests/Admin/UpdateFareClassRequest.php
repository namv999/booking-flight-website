<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFareClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:255'],
            'base_price'           => ['required', 'numeric', 'min:0'],
            'seat_selection_fee'   => ['required', 'numeric', 'min:0'],
            'checked_baggage_kg'   => ['required', 'integer', 'min:0'],
            'carry_on_baggage_kg'  => ['required', 'integer', 'min:0'],
            'description'          => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên hạng vé.',
            'base_price.required' => 'Vui lòng nhập giá cơ bản.',
            'seat_selection_fee.required' => 'Vui lòng nhập phí chọn ghế.',
            'checked_baggage_kg.required' => 'Vui lòng nhập số kg hành lý ký gửi.',
            'carry_on_baggage_kg.required' => 'Vui lòng nhập số kg hành lý xách tay.',
        ];
    }
}
