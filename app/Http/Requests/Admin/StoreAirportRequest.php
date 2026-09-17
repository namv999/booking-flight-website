<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAirportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'iata_code' => ['required', 'string', 'max:10', 'unique:airports,iata_code'],
            'name'      => ['required', 'string', 'max:200'],
            'city'      => ['required', 'string', 'max:100'],
            'country'   => ['required', 'string', 'max:100'],
            'timezone'  => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'iata_code.required' => 'Vui lòng nhập mã IATA sân bay.',
            'iata_code.max'      => 'Mã IATA không được vượt quá 10 ký tự. Vui lòng nhập lại!',
            'iata_code.unique'   => 'Mã IATA này đã tồn tại trong hệ thống.',
            'name.required'      => 'Vui lòng nhập tên sân bay.',
            'name.max'           => 'Tên sân bay tối đa 200 ký tự. Vui lòng nhập lại!',
            'city.required'      => 'Vui lòng nhập tên thành phố.',
            'city.max'           => 'Tên thành phố tối đa 100 ký tự.',
            'country.required'   => 'Vui lòng nhập tên quốc gia.',
            'country.max'        => 'Tên quốc gia tối đa 100 ký tự.',
            'timezone.required'  => 'Vui lòng nhập múi giờ.',
            'timezone.max'       => 'Múi giờ tối đa 50 ký tự.',
        ];
    }
}
