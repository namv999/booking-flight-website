<?php

// Mã ISO 3166-1 alpha-3 => tên tiếng Anh; giữ tên hiện có để không đổi nhãn trong hồ sơ đã lưu.
$countries = collect(json_decode(file_get_contents(database_path('data/countries.json')), true))
    ->pluck('name_en', 'alpha3')
    ->all();

return array_replace($countries, [
    'VNM' => 'Vietnam',
    'THA' => 'Thailand',
    'SGP' => 'Singapore',
    'MYS' => 'Malaysia',
    'IDN' => 'Indonesia',
    'PHL' => 'Philippines',
    'KHM' => 'Cambodia',
    'LAO' => 'Laos',
    'MMR' => 'Myanmar',
    'BRN' => 'Brunei',
    'JPN' => 'Japan',
    'KOR' => 'South Korea',
    'CHN' => 'China',
    'HKG' => 'Hong Kong',
    'TWN' => 'Taiwan',
    'IND' => 'India',
    'AUS' => 'Australia',
    'NZL' => 'New Zealand',
    'USA' => 'United States',
    'CAN' => 'Canada',
    'GBR' => 'United Kingdom',
    'FRA' => 'France',
    'DEU' => 'Germany',
    'ITA' => 'Italy',
    'ESP' => 'Spain',
    'RUS' => 'Russia',
    'ARE' => 'United Arab Emirates',
    'QAT' => 'Qatar',
    'TUR' => 'Turkey',
]);
