<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAirlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'code'     => ['required', 'string', 'max:50', 'unique:airlines,code'],
            'logo_url' => ['nullable', 'string', 'max:255'],
            'country'  => ['nullable', 'string', 'max:100'],
        ];
    }
}
