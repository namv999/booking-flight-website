<?php

namespace App\Services;

use App\Models\Airport;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PassportService
{
    // Hành trình quốc tế = có ít nhất một chặng khác quốc gia giữa sân bay đi và đến
    public function isInternational(array $pending): bool
    {
        $ids = collect($pending['legs'])->flatMap(fn ($l) => [$l['from'], $l['to']])->unique();
        $countries = Airport::withTrashed()->whereIn('id', $ids)->pluck('country', 'id');

        foreach ($pending['legs'] as $leg) {
            $from = $this->normalizeCountry($countries[$leg['from']] ?? '');
            $to = $this->normalizeCountry($countries[$leg['to']] ?? '');

            if ($from !== $to) {
                return true;
            }
        }

        return false;
    }

    // Đơn đã đặt có chặng quốc tế không (so quốc gia sân bay đi và đến của từng chặng, kể cả sân bay đã xóa mềm)
    public function isInternationalBooking(Booking $booking): bool
    {
        $booking->loadMissing([
            'bookingFlights.flight.departureAirport' => fn ($q) => $q->withTrashed(),
            'bookingFlights.flight.arrivalAirport' => fn ($q) => $q->withTrashed(),
        ]);

        foreach ($booking->bookingFlights as $bookingFlight) {
            $flight = $bookingFlight->flight;

            if ($this->differentCountry($flight?->departureAirport, $flight?->arrivalAirport)) {
                return true;
            }
        }

        return false;
    }

    // Bộ rule validate dữ liệu hộ chiếu của MỘT hành khách, dùng chung cho form đặt vé và form cập nhật.
    // $prefix là tiền tố của key: 'adults.*.' cho form đặt vé, '' cho form cập nhật (field nằm ở gốc).
    public function rules(string $prefix = '', bool $withDocumentType = true): array
    {
        $countries = array_keys(config('countries'));

        $rules = [
            "{$prefix}full_name" => ['required', 'string', 'max:150', 'regex:/^[A-Z][A-Z \'\-]*$/'],
            "{$prefix}document_number" => ['required', 'string', 'regex:/^[A-Z0-9]{6,9}$/'],
            "{$prefix}date_of_birth" => ['required', 'date', 'before_or_equal:today'],
            "{$prefix}gender" => ['required', 'in:male,female'],
            "{$prefix}nationality" => ['required', Rule::in($countries)],
            "{$prefix}document_issued_country" => ['required', Rule::in($countries)],
            "{$prefix}document_issue_date" => ['required', 'date', 'before_or_equal:today'],
            "{$prefix}document_expiry_date" => ['required', 'date'],
        ];

        if ($withDocumentType) {
            $rules["{$prefix}document_type"] = ['required', 'in:passport'];
        }

        return $rules;
    }

    // Thông báo lỗi tiếng Việt tương ứng với rules()
    public function messages(string $prefix = '', bool $withDocumentType = true): array
    {
        $messages = [
            "{$prefix}full_name.regex" => 'Họ tên phải viết không dấu theo hộ chiếu (chỉ chữ A-Z).',
            "{$prefix}document_number.regex" => 'Số hộ chiếu gồm 6 đến 9 ký tự chữ và số.',
            "{$prefix}nationality.in" => 'Quốc tịch không hợp lệ.',
            "{$prefix}document_issued_country.in" => 'Quốc gia cấp không hợp lệ.',
        ];

        if ($withDocumentType) {
            $messages["{$prefix}document_type.in"] = 'Chuyến bay quốc tế yêu cầu hộ chiếu.';
        }

        return $messages;
    }

    // Ngày hết hạn phải sau ngày cấp (kiểm tra ở hook after, vì rule after:field không ổn định với wildcard)
    public function isExpiryAfterIssue(?string $issueDate, ?string $expiryDate): bool
    {
        return Carbon::parse($expiryDate)->gt(Carbon::parse($issueDate));
    }

    // Họ tên theo hộ chiếu: không dấu, chữ hoa, gom khoảng trắng
    public function normalizeName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        return trim(preg_replace('/\s+/', ' ', Str::upper(Str::ascii($name))));
    }

    // Số hộ chiếu: chỉ chữ và số, viết hoa
    public function normalizeDocumentNumber(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $number));
    }

    private function normalizeCountry(string $country): string
    {
        return Str::lower(trim($country));
    }

    // So sánh quốc gia của hai sân bay (null thì coi như không quốc tế)
    public function differentCountry(?Airport $from, ?Airport $to): bool
    {
        if (! $from || ! $to) {
            return false;
        }

        return $this->normalizeCountry($from->country ?? '') !== $this->normalizeCountry($to->country ?? '');
    }

    // "VNM" thành "Vietnam (VNM)"; mã lạ thì trả lại chính mã; null thì null
    public function countryLabel(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        $code = strtoupper($code);
        $name = config('countries.'.$code);

        return $name ? "$name ($code)" : $code;
    }
}
