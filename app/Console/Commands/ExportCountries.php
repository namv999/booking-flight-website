<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use League\ISO3166\ISO3166;

class ExportCountries extends Command
{
    protected $signature = 'countries:export';

    protected $description = 'Xuất danh sách quốc gia ISO 3166-1 ra database/data/countries.json';

    public function handle(): int
    {
        $rows = [];

        foreach ((new ISO3166)->all() as $country) {
            $rows[] = [
                'alpha2' => $country['alpha2'],
                'alpha3' => $country['alpha3'],
                'numeric' => $country['numeric'],
                'name_en' => $country['name'],
                'name_vi' => $this->vietnamese($country['alpha2'], $country['name']),
            ];
        }

        usort($rows, fn ($a, $b) => strcmp($a['name_en'], $b['name_en']));

        File::ensureDirectoryExists(database_path('data'));
        File::put(
            database_path('data/countries.json'),
            json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );

        $this->info(count($rows).' quốc gia đã được xuất.');

        return self::SUCCESS;
    }

    private function vietnamese(string $alpha2, string $fallback): string
    {
        if (! class_exists(\Locale::class)) {
            return $fallback;
        }

        $name = \Locale::getDisplayRegion('-'.$alpha2, 'vi');

        return ($name === '' || $name === $alpha2) ? $fallback : $name;
    }
}
