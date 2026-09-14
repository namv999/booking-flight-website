<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'aircraft_id'          => ['required', 'exists:aircrafts,id'],
            'departure_airport_id' => ['required', 'exists:airports,id', 'different:arrival_airport_id'],
            'arrival_airport_id'   => ['required', 'exists:airports,id'],
            'departure_time'       => ['required', 'date'],
            'arrival_time'         => ['required', 'date', 'after:departure_time'],
            'status'               => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'departure_airport_id.different' => 'Sân bay đi và sân bay đến phải khác nhau.',
            'arrival_time.after'             => 'Thời gian đến phải sau thời gian đi.',
        ];
    }
}
