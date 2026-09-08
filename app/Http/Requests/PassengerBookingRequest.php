<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PassengerBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
            'infants.*.companion_adult_index' => ['required', 'integer', 'min:0'],
        ];
    }
}