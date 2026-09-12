<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAirlineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $airlineId = $this->route('airline')->id ?? $this->route('airline');

        return [
            'name'     => ['required', 'string', 'max:255'],
            'code'     => ['required', 'string', 'max:50', 'unique:airlines,code,' . $airlineId],
            'logo_url' => ['nullable', 'string', 'max:255'],
            'country'  => ['nullable', 'string', 'max:100'],
        ];
    }
}
