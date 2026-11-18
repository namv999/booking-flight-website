<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeatSelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seats' => ['nullable', 'array'],
            'seats.*' => ['array'],
            'seats.*.adults' => ['nullable', 'array'],
            'seats.*.adults.*' => ['nullable', 'integer'],
            'seats.*.children' => ['nullable', 'array'],
            'seats.*.children.*' => ['nullable', 'integer'],
        ];
    }
}
