<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlightSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'departure_airport_id' => ['required', 'exists:airports,id', 'different:arrival_airport_id'],
            'arrival_airport_id'   => ['required', 'exists:airports,id'],
            'departure_date'       => ['required', 'date', 'after_or_equal:today'],
            'fare_class_id'        => ['required', 'exists:fare_classes,id'],
            'adults'               => ['required', 'integer', 'min:1', 'max:9'],
            'children'             => ['nullable', 'integer', 'min:0', 'max:9'],
            'infants'              => ['nullable', 'integer', 'min:0', 'max:9', 'lte:adults'], // infant bắt buộc companion adult
        ];
    }

    public function messages(): array
    {
        return [
            'departure_airport_id.different' => 'Điểm đi và điểm đến phải khác nhau.',
        ];
    }
}