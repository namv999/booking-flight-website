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

    protected function prepareForValidation(): void
    {
        // Tương thích 2 chiều: nếu gửi cccd_number hoặc passport_number mà chưa có document_number
        if ($this->filled('cccd_number') && ! $this->filled('document_number')) {
            $this->merge(['document_number' => $this->input('cccd_number')]);
        } elseif ($this->filled('passport_number') && ! $this->filled('document_number')) {
            $this->merge(['document_number' => $this->input('passport_number')]);
        }

        if ($this->filled('document_number')) {
            if ($this->input('document_type') === 'passport' && ! $this->filled('passport_number')) {
                $this->merge(['passport_number' => $this->input('document_number')]);
            } elseif (! $this->filled('cccd_number')) {
                $this->merge(['cccd_number' => $this->input('document_number')]);
            }
        }
    }

    public function rules(): array
    {
        $countries = array_keys(config('countries'));
        $passengerRouteParam = $this->route('saved_passenger');
        $passengerId = is_object($passengerRouteParam) ? $passengerRouteParam->id : $passengerRouteParam;

        return [
            'full_name' => ['required', 'string', 'max:150'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'passenger_type_default' => ['required', 'in:adult,child,infant'],
            'relationship' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'in:male,female'],
            'nationality' => ['nullable', Rule::in($countries)],

            // Thông tin CCCD (bay nội địa)
            'cccd_number' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('saved_passengers', 'cccd_number')
                    ->where('user_id', auth()->id())
                    ->ignore($passengerId),
            ],
            'cccd_issue_date' => ['nullable', 'date', 'before_or_equal:today'],

            // Thông tin Hộ chiếu (bay quốc tế)
            'passport_number' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('saved_passengers', 'passport_number')
                    ->where('user_id', auth()->id())
                    ->ignore($passengerId),
            ],
            'passport_issued_country' => ['nullable', Rule::in($countries)],
            'passport_issue_date' => ['nullable', 'date', 'before_or_equal:today'],
            'passport_expiry_date' => ['nullable', 'date'],

            // Tương thích ngược với các trường cũ
            'document_type' => ['nullable', 'in:national_id,passport'],
            'document_number' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('saved_passengers', 'document_number')
                    ->where('user_id', auth()->id())
                    ->ignore($passengerId),
            ],
            'document_issued_country' => ['nullable', Rule::in($countries)],
            'document_issue_date' => ['nullable', 'date', 'before_or_equal:today'],
            'document_expiry_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_number.unique' => 'Số giấy tờ này đã được lưu trong hồ sơ khác của bạn.',
            'cccd_number.unique' => 'Số CCCD này đã được lưu trong hồ sơ khác của bạn.',
            'passport_number.unique' => 'Số Hộ chiếu này đã được lưu trong hồ sơ khác của bạn.',
            'nationality.in' => 'Quốc tịch không hợp lệ.',
            'passport_issued_country.in' => 'Quốc gia cấp hộ chiếu không hợp lệ.',
            'document_issued_country.in' => 'Quốc gia cấp không hợp lệ.',
            'cccd_issue_date.before_or_equal' => 'Ngày cấp CCCD không thể sau ngày hiện tại.',
            'passport_issue_date.before_or_equal' => 'Ngày cấp hộ chiếu không thể sau ngày hiện tại.',
            'date_of_birth.before_or_equal' => 'Ngày sinh không thể sau ngày hiện tại.',
        ];
    }
}
