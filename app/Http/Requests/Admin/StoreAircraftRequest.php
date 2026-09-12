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
            'airline_id'     => ['required', 'exists:airlines,id'],
            'model_name'     => ['required', 'string', 'max:255'],
            'capacity'       => ['required', 'integer', 'min:1'],
        ];
    }
}