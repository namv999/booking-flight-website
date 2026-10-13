<?php

namespace App\Services;

use App\Models\Airport;
use Illuminate\Support\Str;

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
}
