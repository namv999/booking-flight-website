<?php

namespace App\Http\Requests;

use App\Services\FlightSearchService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FlightFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $slots = array_keys(FlightSearchService::TIME_SLOTS);

        return [
            'airlines' => ['nullable', 'array', 'max:10'],
            'airlines.*' => ['string', 'max:10'],
            'price_min' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'price_max' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'dep_time' => ['nullable', 'array'],
            'dep_time.*' => ['string', Rule::in($slots)],
            'arr_time' => ['nullable', 'array'],
            'arr_time.*' => ['string', Rule::in($slots)],
            'sort' => ['nullable', Rule::in(array_keys(FlightSearchService::SORTS))],
        ];
    }

    public function messages(): array
    {
        return [
            'sort.in' => 'Kiểu sắp xếp không hợp lệ.',
            'price_min.integer' => 'Giá tối thiểu phải là số nguyên.',
            'price_max.integer' => 'Giá tối đa phải là số nguyên.',
        ];
    }

    // Không dùng rule gte:price_min vì gte lỗi khi price_min để trống
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $min = $this->input('price_min');
            $max = $this->input('price_max');

            if ($min !== null && $max !== null && (int) $min > (int) $max) {
                $validator->errors()->add('price_max', 'Giá tối đa phải lớn hơn hoặc bằng giá tối thiểu.');
            }
        }];
    }
}
