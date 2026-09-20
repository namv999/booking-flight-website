<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BaggageAddonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // đã chặn qua middleware 'admin' ở route
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'weight_kg' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }
}