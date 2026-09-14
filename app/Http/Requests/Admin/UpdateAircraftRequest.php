<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAircraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $aircraftId = $this->route('aircraft')->id ?? $this->route('aircraft');

        return [
            'airline_id'           => ['required', 'exists:airlines,id'],
            'model'                => ['required', 'string', 'max:255'],
            'registration_number'  => ['required', 'string', 'max:50', 'unique:aircrafts,registration_number,' . $aircraftId],
            'total_seats'          => ['required', 'integer', 'min:1'],
        ];
    }
}
