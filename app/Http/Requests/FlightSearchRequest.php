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
            'arrival_airport_id' => ['required', 'exists:airports,id'],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'fare_class_id' => ['required', 'exists:fare_classes,id'],
            'adults' => ['required', 'integer', 'min:1', 'max:9'],
            'children' => ['nullable', 'integer', 'min:0', 'max:9'],
            'infants' => ['nullable', 'integer', 'min:0', 'max:9', 'lte:adults'], // infant bắt buộc companion adult
            'trip_type' => ['nullable', 'in:one-way,round-trip'],
            'return_date' => ['required_if:trip_type,round-trip', 'nullable', 'date', 'after_or_equal:departure_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'departure_airport_id.different' => 'Điểm đi và điểm đến phải khác nhau.',
        ];
    }

    public function tripType(): string
    {
        return $this->validated('trip_type') ?? 'one-way';
    }

    // Chặng 2 của khứ hồi = đảo chiều chặng 1 với ngày về
    public function legs(): array
    {
        $from = (int) $this->validated('departure_airport_id');
        $to = (int) $this->validated('arrival_airport_id');

        $legs = [['from' => $from, 'to' => $to, 'date' => $this->validated('departure_date')]];

        if ($this->tripType() === 'round-trip') {
            $legs[] = ['from' => $to, 'to' => $from, 'date' => $this->validated('return_date')];
        }

        return $legs;
    }
}
