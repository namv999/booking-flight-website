<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAircraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'airline_id'           => ['required', 'exists:airlines,id'],
            'model'                => ['required', 'string', 'max:255'],
            'registration_number'  => ['required', 'string', 'max:50', 'unique:aircrafts,registration_number'],
            'total_seats'          => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'airline_id.required'          => 'Vui lòng chọn hãng hàng không.',
            'model.required'               => 'Vui lòng nhập model máy bay.',
            'registration_number.required' => 'Vui lòng nhập số hiệu đăng ký.',
            'registration_number.unique'   => 'Số hiệu đăng ký này đã tồn tại trong hệ thống.',
            'total_seats.required'         => 'Vui lòng nhập tổng số ghế.',
            'total_seats.integer'          => 'Tổng số ghế phải là số nguyên.',
        ];
    }
}