<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
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
            'trip_type' => ['nullable', 'in:one-way,round-trip,multi-city'],
            'return_date' => ['required_if:trip_type,round-trip', 'nullable', 'date', 'after_or_equal:departure_date'],
            'extra_legs' => ['exclude_unless:trip_type,multi-city', 'required', 'array', 'min:1', 'max:3'],
            'extra_legs.*.departure_airport_id' => ['exclude_unless:trip_type,multi-city', 'required', 'exists:airports,id', 'different:extra_legs.*.arrival_airport_id'],
            'extra_legs.*.arrival_airport_id' => ['exclude_unless:trip_type,multi-city', 'required', 'exists:airports,id'],
            'extra_legs.*.date' => ['exclude_unless:trip_type,multi-city', 'required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'departure_airport_id.different' => 'Điểm đi và điểm đến phải khác nhau.',
            'extra_legs.required' => 'Vui lòng thêm ít nhất một chặng tiếp theo.',
            'extra_legs.max' => 'Tối đa 4 chặng cho mỗi hành trình.',
            'extra_legs.*.departure_airport_id.required' => 'Vui lòng chọn điểm đi cho chặng tiếp theo.',
            'extra_legs.*.departure_airport_id.different' => 'Điểm đi và điểm đến của mỗi chặng phải khác nhau.',
            'extra_legs.*.arrival_airport_id.required' => 'Vui lòng chọn điểm đến cho chặng tiếp theo.',
            'extra_legs.*.date.required' => 'Vui lòng chọn ngày bay cho chặng tiếp theo.',
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

        if ($this->tripType() === 'multi-city') {
            foreach ($this->validated('extra_legs') as $extra) {
                $legs[] = [
                    'from' => (int) $extra['departure_airport_id'],
                    'to' => (int) $extra['arrival_airport_id'],
                    'date' => $extra['date'],
                ];
            }
        }

        return $legs;
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || $this->input('trip_type') !== 'multi-city') {
                return;
            }

            $prevDate = Carbon::parse($this->input('departure_date'))->toDateString();

            foreach ($this->input('extra_legs', []) as $i => $leg) {
                $date = Carbon::parse($leg['date'])->toDateString();

                if ($date < $prevDate) {
                    $validator->errors()->add(
                        "extra_legs.$i.date",
                        'Chặng '.($i + 2).': ngày bay không được trước ngày bay của chặng trước.'
                    );
                }

                $prevDate = $date;
            }
        }];
    }
}
