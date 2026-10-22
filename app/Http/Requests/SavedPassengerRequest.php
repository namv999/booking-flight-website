<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavedPassengerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ownership check nằm ở Controller
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'document_number' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('saved_passengers', 'document_number')
                    ->where('user_id', auth()->id())
                    ->ignore($this->route('saved_passenger')),
            ],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'passenger_type_default' => ['required', 'in:adult,child,infant'],
            'relationship' => ['nullable', 'string', 'max:30'],
            'document_type' => ['nullable', 'in:national_id,passport'],
            'gender' => ['nullable', 'in:male,female'],
            'nationality' => ['nullable', Rule::in(array_keys(config('countries')))],
            'document_issued_country' => ['nullable', Rule::in(array_keys(config('countries')))],
            'document_issue_date' => ['nullable', 'date', 'before_or_equal:today'],
            'document_expiry_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_number.unique' => 'Số giấy tờ này đã được lưu trong hồ sơ khác của bạn.',
        ];
    }
}
